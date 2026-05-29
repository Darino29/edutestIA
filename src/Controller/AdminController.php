<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Exam;
use App\Entity\Assignment;
use App\Service\GroqService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        PaginatorInterface $paginator
    ): Response {
        $userRepo = $em->getRepository(User::class);
        $examRepo = $em->getRepository(Exam::class);
        $assignRepo = $em->getRepository(Assignment::class);

        $teacherQuery = $em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_TEACHER%')
            ->orderBy('u.fullName', 'ASC')
            ->getQuery();

        $studentQuery = $em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_STUDENT%')
            ->orderBy('u.fullName', 'ASC')
            ->getQuery();

        $teachers = $paginator->paginate(
            $teacherQuery,
            $request->query->getInt('teacherPage', 1),
            10,
            ['pageParameterName' => 'teacherPage']
        );

        $students = $paginator->paginate(
            $studentQuery,
            $request->query->getInt('studentPage', 1),
            10,
            ['pageParameterName' => 'studentPage']
        );

        // COUNT queries (très rapides) au lieu de findAll()
        $totalTeachers = (int) $em->createQueryBuilder()
            ->select('COUNT(u.id)')->from(User::class, 'u')
            ->where('u.roles LIKE :role')->setParameter('role', '%ROLE_TEACHER%')
            ->getQuery()->getSingleScalarResult();

        $totalStudents = (int) $em->createQueryBuilder()
            ->select('COUNT(u.id)')->from(User::class, 'u')
            ->where('u.roles LIKE :role')->setParameter('role', '%ROLE_STUDENT%')
            ->getQuery()->getSingleScalarResult();

        $totalExams = (int) $em->createQueryBuilder()
            ->select('COUNT(e.id)')->from(Exam::class, 'e')
            ->getQuery()->getSingleScalarResult();

        $totalAssignments = (int) $em->createQueryBuilder()
            ->select('COUNT(a.id)')->from(Assignment::class, 'a')
            ->getQuery()->getSingleScalarResult();

        $totalSubmitted = (int) $em->createQueryBuilder()
            ->select('COUNT(a.id)')->from(Assignment::class, 'a')
            ->where('a.status = :status')->setParameter('status', 'SUBMITTED')
            ->getQuery()->getSingleScalarResult();

        $submissionRate = $totalAssignments > 0
            ? round(($totalSubmitted / $totalAssignments) * 100, 1)
            : 0;

        // Liste limitée aux 20 derniers examens pour le tableau
        $exams = $examRepo->findBy([], ['id' => 'DESC'], 20);

        // 🧑‍💻 Création utilisateur rapide (optionnelle)
        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email'));
            $fullName = trim($request->request->get('fullName'));
            $password = trim($request->request->get('password'));
            $role = $request->request->get('role');

            if (!$email || !$password || !$role) {
                $this->addFlash('danger', 'Veuillez remplir tous les champs.');
            } elseif ($userRepo->findOneBy(['email' => $email])) {
                $this->addFlash('warning', 'Un compte avec cet email existe déjà.');
            } else {
                $user = new User();
                $user->setEmail($email);
                $user->setFullName($fullName ?: 'Utilisateur');
                $user->setRoles([$role]);
                $user->setPassword($hasher->hashPassword($user, $password));
                $user->setIsApproved(true);

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', '✅ Utilisateur créé avec succès !');
                return $this->redirectToRoute('admin_dashboard');
            }
        }

        $recentQuery = $assignRepo->createQueryBuilder('a')
            ->leftJoin('a.exam', 'e')->addSelect('e')
            ->leftJoin('a.student', 's')->addSelect('s')
            ->where('a.status = :status')
            ->setParameter('status', 'SUBMITTED')
            ->orderBy('a.submittedAt', 'DESC')
            ->getQuery();

        $recentAssignments = $paginator->paginate(
            $recentQuery,
            $request->query->getInt('proctorPage', 1),
            10,
            ['pageParameterName' => 'proctorPage']
        );

        return $this->render('admin/dashboard.html.twig', [
            'teachers'          => $teachers,
            'students'          => $students,
            'exams'             => $exams,
            'totalTeachers'     => $totalTeachers,
            'totalStudents'     => $totalStudents,
            'totalExams'        => $totalExams,
            'submissionRate'    => $submissionRate,
            'recentAssignments' => $recentAssignments,
        ]);
    }

    #[Route('/ai-insights', name: 'admin_ai_insights')]
    public function aiInsights(EntityManagerInterface $em): Response
    {
        $stats = $this->buildAiInsightsStats($em);

        return $this->render('admin/ai_insights.html.twig', ['stats' => $stats]);
    }

    #[Route('/ai-insights/report', name: 'admin_ai_insights_report')]
    public function aiInsightsReport(EntityManagerInterface $em, GroqService $groqService): JsonResponse
    {
        $stats  = $this->buildAiInsightsStats($em);
        $report = $groqService->generatePlatformInsights($stats);

        return $this->json(['text' => $report]);
    }

    private function buildAiInsightsStats(EntityManagerInterface $em): array
    {
        // COUNT queries (très rapides)
        $totalStudents = (int) $em->createQueryBuilder()
            ->select('COUNT(u.id)')->from(User::class, 'u')
            ->where('u.roles LIKE :role')->setParameter('role', '%ROLE_STUDENT%')
            ->getQuery()->getSingleScalarResult();

        $totalTeachers = (int) $em->createQueryBuilder()
            ->select('COUNT(u.id)')->from(User::class, 'u')
            ->where('u.roles LIKE :role')->setParameter('role', '%ROLE_TEACHER%')
            ->getQuery()->getSingleScalarResult();

        $totalExams = (int) $em->createQueryBuilder()
            ->select('COUNT(e.id)')->from(Exam::class, 'e')
            ->getQuery()->getSingleScalarResult();

        $totalAssignments = (int) $em->createQueryBuilder()
            ->select('COUNT(a.id)')->from(Assignment::class, 'a')
            ->getQuery()->getSingleScalarResult();

        // UNE seule requête pour tous les assignments soumis (exam + student eager-loaded)
        $submitted = $em->createQueryBuilder()
            ->select('a', 'e', 's')
            ->from(Assignment::class, 'a')
            ->join('a.exam', 'e')
            ->join('a.student', 's')
            ->where('a.status = :status')
            ->setParameter('status', 'SUBMITTED')
            ->getQuery()
            ->getResult();

        $submissionRate = $totalAssignments > 0
            ? round(count($submitted) / $totalAssignments * 100, 1)
            : 0;

        // Score moyen global
        $grades = array_filter(
            array_map(fn($a) => $a->getFinalGrade(), $submitted),
            fn($g) => $g !== null
        );
        $avgScore = count($grades) > 0
            ? round(array_sum($grades) / count($grades) / 20 * 100, 1)
            : 0;

        // Étudiants en difficulté + sujets faibles — tout en PHP, sans requête supplémentaire
        $studentTopics = [];
        foreach ($submitted as $a) {
            $grade = $a->getFinalGrade();
            if ($grade === null) {
                continue;
            }
            $sid   = $a->getStudent()->getId();
            $topic = $a->getExam()->getTitle();
            $studentTopics[$sid][$topic][] = ($grade / 20) * 100;
        }

        $strugglingCount = 0;
        $topicFailCount  = [];
        foreach ($studentTopics as $topics) {
            $hasWeak = false;
            foreach ($topics as $topic => $scores) {
                if (array_sum($scores) / count($scores) < 50) {
                    $hasWeak = true;
                    $topicFailCount[$topic] = ($topicFailCount[$topic] ?? 0) + 1;
                }
            }
            if ($hasWeak) {
                $strugglingCount++;
            }
        }

        arsort($topicFailCount);

        return [
            'totalStudents'      => $totalStudents,
            'totalTeachers'      => $totalTeachers,
            'totalExams'         => $totalExams,
            'submissionRate'     => $submissionRate,
            'strugglingStudents' => $strugglingCount,
            'avgScore'           => $avgScore,
            'weakTopics'         => array_slice(array_keys($topicFailCount), 0, 5),
        ];
    }

    #[Route('/student/{id}/transcript', name: 'admin_student_transcript')]
    public function studentTranscript(User $student, EntityManagerInterface $em): Response
    {
        $assignments = $em->getRepository(Assignment::class)
            ->createQueryBuilder('a')
            ->leftJoin('a.exam', 'e')->addSelect('e')
            ->where('a.student = :student')
            ->andWhere('a.status = :status')
            ->setParameter('student', $student)
            ->setParameter('status', 'SUBMITTED')
            ->orderBy('a.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('exam_pass/transcript.html.twig', [
            'student'     => $student,
            'assignments' => $assignments,
            'generatedAt' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/approve/{id}', name: 'admin_approve_user')]
    public function approveUser(User $user, EntityManagerInterface $em): Response
    {
        if (!$user->isApproved()) {
            $user->setIsApproved(true);
            $em->flush();
            $this->addFlash('success', sprintf('✅ %s a été approuvé.', $user->getFullName()));
        } else {
            $this->addFlash('info', 'Cet utilisateur est déjà approuvé.');
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/ban/{id}', name: 'admin_ban_user')]
    public function banUser(User $user, EntityManagerInterface $em): Response
    {
        $user->setIsApproved(false);
        $em->flush();
        $this->addFlash('warning', sprintf('🚫 %s a été désactivé.', $user->getFullName()));

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/delete/{id}', name: 'admin_delete_user')]
    public function deleteUser(User $user, EntityManagerInterface $em): Response
    {
        $name = $user->getFullName();
        $em->remove($user);
        $em->flush();

        $this->addFlash('danger', sprintf('🗑️ L’utilisateur "%s" a été supprimé avec succès.', $name));
        return $this->redirectToRoute('admin_dashboard');
    }
}
