<?php

namespace App\Controller;

use App\Entity\Assignment;
use App\Entity\Classe;
use App\Entity\Exam;
use App\Entity\User;
use App\Form\AssignmentType;
use App\Repository\AssignmentRepository;
use App\Repository\ClasseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_TEACHER')]
#[Route('/teacher/assignment')]
final class AssignmentController extends AbstractController
{
    #[Route('/', name: 'teacher_assignment_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $teacher = $this->getUser();
        $examId = $request->query->getInt('exam');

        $qb = $em->getRepository(Assignment::class)
            ->createQueryBuilder('a')
            ->leftJoin('a.exam', 'e')->addSelect('e')
            ->leftJoin('a.student', 's')->addSelect('s')
            ->where('e.teacher = :t')
            ->setParameter('t', $teacher)
            ->orderBy('a.id', 'DESC');

        $exam = null;
        if ($examId) {
            $exam = $em->getRepository(Exam::class)->find($examId);

            if ($exam && $exam->getTeacher() !== $teacher) {
                throw $this->createAccessDeniedException();
            }

            if ($exam) {
                $qb->andWhere('e = :exam')->setParameter('exam', $exam);
            }
        }

        $assignments = $qb->getQuery()->getResult();

        return $this->render('assignment/index.html.twig', [
            'assignments' => $assignments,
            'exam' => $exam,
        ]);
    }

    #[Route('/new', name: 'teacher_assignment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $assignment = new Assignment();

        $options = [];

        if ($this->isGranted('ROLE_TEACHER')) {
            $options['teacher'] = $this->getUser();
        }

        $examId = $request->query->getInt('exam');
        if ($examId) {
            $exam = $em->getRepository(Exam::class)->find($examId);

            if ($exam && $exam->getTeacher() !== $this->getUser()) {
                throw $this->createAccessDeniedException();
            }

            if ($exam) {
                $assignment->setExam($exam);
                $options['exam'] = $exam;
            }
        }


        $form = $this->createForm(AssignmentType::class, $assignment, $options);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $assignment->setStatus('ASSIGNED');
            $assignment->setAssignedAt(new \DateTimeImmutable());

            $em->persist($assignment);
            $em->flush();

            $this->addFlash('success', '✅ Examen affecté à l’étudiant avec succès.');

            return $this->redirectToRoute('teacher_assignment_index', $examId ? ['exam' => $examId] : []);
        }

        return $this->render('assignment/new.html.twig', [
            'assignment' => $assignment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'teacher_assignment_show', methods: ['GET'])]
    public function show(Assignment $assignment): Response
    {
        return $this->render('assignment/show.html.twig', [
            'assignment' => $assignment,
        ]);
    }

    /* #[Route('/teacher/exam/{id}/assignments', name: 'teacher_exam_assignments')]
    public function byExam(Exam $exam, AssignmentRepository $repo): Response
    {
        // sécurité : empêcher accès à un exam d’un autre prof
        if ($exam->getTeacher() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $assignments = $repo->findBy(
            ['exam' => $exam],
            ['assignedAt' => 'DESC']
        );

        return $this->render('assignment/index.html.twig', [
            'assignments' => $assignments,
            'exam' => $exam,
        ]);
    } */

    #[Route('/assign-class', name: 'teacher_assignment_assign_class', methods: ['GET', 'POST'])]
    public function assignClass(Request $request, EntityManagerInterface $em, ClasseRepository $classeRepo): Response
    {
        $exams   = $em->getRepository(Exam::class)->findBy(['teacher' => $this->getUser()]);
        $classes = $classeRepo->findAllOrderedByLevel();
        $created = 0;
        $skipped = 0;

        if ($request->isMethod('POST')) {
            $examId   = (int) $request->request->get('exam');
            $classeId = (int) $request->request->get('classe');

            $exam   = $em->getRepository(Exam::class)->find($examId);
            $classe = $classeRepo->find($classeId);

            if (!$exam || $exam->getTeacher() !== $this->getUser()) {
                $this->addFlash('danger', 'Examen invalide.');
                return $this->redirectToRoute('teacher_assignment_assign_class');
            }

            if (!$classe || $classe->getStudents()->isEmpty()) {
                $this->addFlash('warning', 'Cette classe ne contient aucun étudiant.');
                return $this->redirectToRoute('teacher_assignment_assign_class');
            }

            foreach ($classe->getStudents() as $student) {
                // Vérifier si déjà affecté
                $exists = $em->getRepository(Assignment::class)->findOneBy([
                    'exam'    => $exam,
                    'student' => $student,
                ]);

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $assignment = new Assignment();
                $assignment->setExam($exam);
                $assignment->setStudent($student);
                $assignment->setStatus('ASSIGNED');
                $assignment->setAssignedAt(new \DateTimeImmutable());
                $em->persist($assignment);
                $created++;
            }

            $em->flush();

            if ($created > 0) {
                $this->addFlash('success', "✅ {$created} affectation(s) créée(s) pour la classe « {$classe->getFullLabel()} »." . ($skipped > 0 ? " {$skipped} déjà affecté(s), ignoré(s)." : ''));
            } else {
                $this->addFlash('warning', 'Tous les étudiants de cette classe avaient déjà cet examen affecté.');
            }

            return $this->redirectToRoute('teacher_assignment_index');
        }

        return $this->render('assignment/assign_class.html.twig', [
            'exams'   => $exams,
            'classes' => $classes,
        ]);
    }

    #[Route('/{id}/delete', name: 'teacher_assignment_delete', methods: ['POST'])]
    public function delete(Request $request, Assignment $assignment, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $assignment->getId(), $request->request->get('_token'))) {
            $em->remove($assignment);
            $em->flush();
            $this->addFlash('info', '🗑️ Affectation supprimée avec succès.');
        }

        return $this->redirectToRoute('teacher_assignment_index');
    }
}
