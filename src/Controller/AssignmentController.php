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
        $teacher  = $this->getUser();
        $examId   = $request->query->getInt('exam');
        $classeId = $request->query->get('classe', '');
        $statusFilter = $request->query->get('status', '');

        $qb = $em->getRepository(Assignment::class)
            ->createQueryBuilder('a')
            ->leftJoin('a.exam', 'e')->addSelect('e')
            ->leftJoin('a.student', 's')->addSelect('s')
            ->leftJoin('s.classe', 'c')->addSelect('c')
            ->where('e.teacher = :t')
            ->setParameter('t', $teacher)
            ->orderBy('c.level', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->addOrderBy('s.fullName', 'ASC');

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

        if ($classeId === 'none') {
            $qb->andWhere('s.classe IS NULL');
        } elseif ($classeId !== '') {
            $qb->andWhere('c.id = :classeId')->setParameter('classeId', (int) $classeId);
        }

        if ($statusFilter !== '') {
            $qb->andWhere('a.status = :status')->setParameter('status', strtoupper($statusFilter));
        }

        $assignments = $qb->getQuery()->getResult();

        // Regroup by class, then by student
        $grouped = [];
        foreach ($assignments as $assignment) {
            $classe    = $assignment->getStudent()->getClasse();
            $key       = $classe ? $classe->getId() : 0;
            $student   = $assignment->getStudent();
            $studentId = $student->getId();

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'classe'    => $classe,
                    'students'  => [],
                    'assigned'  => 0,
                    'started'   => 0,
                    'submitted' => 0,
                    'grades'    => [],
                ];
            }
            if (!isset($grouped[$key]['students'][$studentId])) {
                $grouped[$key]['students'][$studentId] = [
                    'student'     => $student,
                    'assignments' => [],
                ];
            }
            $grouped[$key]['students'][$studentId]['assignments'][] = $assignment;

            $s = strtolower($assignment->getStatus() ?? '');
            if ($s === 'assigned')  $grouped[$key]['assigned']++;
            if ($s === 'started')   $grouped[$key]['started']++;
            if ($s === 'submitted') $grouped[$key]['submitted']++;
            if ($assignment->getFinalGrade() !== null) {
                $grouped[$key]['grades'][] = $assignment->getFinalGrade();
            }
        }

        // Sort: named classes first, "no class" group (key=0) last
        uksort($grouped, fn($a, $b) => $a === 0 ? 1 : ($b === 0 ? -1 : 0));

        $exams = $em->getRepository(Exam::class)->findBy(['teacher' => $teacher], ['title' => 'ASC']);

        return $this->render('assignment/index.html.twig', [
            'grouped'         => $grouped,
            'exam'            => $exam,
            'exams'           => $exams,
            'currentExamId'   => $examId,
            'currentClasseId' => $classeId,
            'currentStatus'   => $statusFilter,
            'total'           => count($assignments),
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

            $this->addFlash('success', "✅ Examen affecté à l'étudiant avec succès.");

            return $this->redirectToRoute('teacher_assignment_index', $examId ? ['exam' => $examId] : []);
        }

        return $this->render('assignment/new.html.twig', [
            'assignment' => $assignment,
            'form' => $form,
        ]);
    }

    #[Route('/assign-class', name: 'teacher_assignment_assign_class', methods: ['GET', 'POST'])]
    public function assignClass(Request $request, EntityManagerInterface $em, ClasseRepository $classeRepo): Response
    {
        $exams   = $em->getRepository(Exam::class)->findBy(['teacher' => $this->getUser()]);
        $classes = $classeRepo->findAllOrderedByLevel();
        $created = 0;
        $skipped = 0;

        if ($request->isMethod('POST')) {
            $examId    = (int) $request->request->get('exam');
            $classeIds = $request->request->all('classes');

            $exam = $em->getRepository(Exam::class)->find($examId);

            if (!$exam || $exam->getTeacher() !== $this->getUser()) {
                $this->addFlash('danger', 'Examen invalide.');
                return $this->redirectToRoute('teacher_assignment_assign_class');
            }

            if (empty($classeIds)) {
                $this->addFlash('warning', 'Veuillez sélectionner au moins une classe.');
                return $this->redirectToRoute('teacher_assignment_assign_class');
            }

            // Charger tous les IDs d'étudiants déjà affectés en UNE seule requête
            $existingRaw = $em->createQueryBuilder()
                ->select('IDENTITY(a.student) as sid')
                ->from(Assignment::class, 'a')
                ->where('a.exam = :exam')
                ->setParameter('exam', $exam)
                ->getQuery()
                ->getScalarResult();
            $alreadyAssigned = array_flip(array_column($existingRaw, 'sid'));

            $classesLabels = [];
            foreach ($classeIds as $classeId) {
                $classe = $classeRepo->find((int) $classeId);
                if (!$classe) {
                    continue;
                }

                foreach ($classe->getStudents() as $student) {
                    if (isset($alreadyAssigned[$student->getId()])) {
                        $skipped++;
                        continue;
                    }

                    $assignment = new Assignment();
                    $assignment->setExam($exam);
                    $assignment->setStudent($student);
                    $assignment->setStatus('ASSIGNED');
                    $assignment->setAssignedAt(new \DateTimeImmutable());
                    $em->persist($assignment);
                    $alreadyAssigned[$student->getId()] = true;
                    $created++;
                }
                $classesLabels[] = $classe->getFullLabel();
            }

            $em->flush();

            if ($created > 0) {
                $classesStr = implode(', ', $classesLabels);
                $this->addFlash('success', "✅ {$created} affectation(s) créée(s) pour : {$classesStr}." . ($skipped > 0 ? " {$skipped} déjà affecté(s), ignoré(s)." : ''));
            } else {
                $this->addFlash('warning', 'Tous les étudiants sélectionnés avaient déjà cet examen affecté.');
            }

            return $this->redirectToRoute('teacher_assignment_index');
        }

        return $this->render('assignment/assign_class.html.twig', [
            'exams'   => $exams,
            'classes' => $classes,
        ]);
    }

    #[Route('/{id}', name: 'teacher_assignment_show', methods: ['GET'])]
    public function show(Assignment $assignment): Response
    {
        return $this->render('assignment/show.html.twig', [
            'assignment' => $assignment,
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
