<?php

namespace App\Controller;

use App\Entity\Assignment;
use App\Entity\Exam;
use App\Entity\User;
use App\Form\AssignmentType;
use App\Repository\AssignmentRepository;
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
