<?php

namespace App\Controller;

use App\Entity\Exam;
use App\Entity\Assignment;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_TEACHER')]
#[Route('/teacher')]
class TeacherDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'teacher_dashboard')]
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        // 🧮 Repositories
        $examRepo = $em->getRepository(Exam::class);
        $assignRepo = $em->getRepository(Assignment::class);

        // 🔹 Pagination des examens
        $teacher = $this->getUser();

        $query = $examRepo->createQueryBuilder('e')
            ->where('e.teacher = :teacher')      // <-- adapte le champ si besoin
            ->setParameter('teacher', $teacher)
            ->orderBy('e.id', 'DESC')
            ->getQuery();


        $exams = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 // 10 examens par page
        );

        // 📊 Récupération des affectations
        $assignments = $assignRepo->createQueryBuilder('a')
        ->leftJoin('a.exam', 'e')->addSelect('e')
        ->where('e.teacher = :teacher')      // <-- adapte le champ si besoin
        ->setParameter('teacher', $teacher)
        ->getQuery()
        ->getResult();

        $totalAssignments = count($assignments);

        // 🔹 Calcul du taux de soumission
        $submitted = count(array_filter($assignments, fn($a) => $a->getStatus() === 'SUBMITTED'));
        $submissionRate = $totalAssignments > 0
            ? round(($submitted / $totalAssignments) * 100, 2)
            : 0;

        // 👩‍🎓 Nombre d’étudiants distincts ayant rendu au moins un examen
        $uniqueStudentsCount = (int) $em->createQueryBuilder()
            ->select('COUNT(DISTINCT s.id)')
            ->from(Assignment::class, 'a')
            ->leftJoin('a.exam', 'e')
            ->leftJoin('a.student', 's')
            ->where('a.status = :status')
            ->andWhere('e.teacher = :teacher')   // <-- adapte le champ si besoin
            ->setParameter('status', 'SUBMITTED')
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getSingleScalarResult();


        // 📈 Statistiques par examen
        $stats = [];
        foreach ($exams as $exam) {
            $examAssignments = array_filter(
                $assignments,
                fn($a) => $a->getExam() === $exam && $a->getFinalGrade() !== null
            );

            $grades = array_map(fn($a) => $a->getFinalGrade(), $examAssignments);

            $mean = !empty($grades) ? array_sum($grades) / count($grades) : null;
            $std = null;

            if ($mean !== null && count($grades) > 1) {
                $variance = array_sum(array_map(fn($g) => pow($g - $mean, 2), $grades)) / count($grades);
                $std = round(sqrt($variance), 2);
            }

            $stats[] = [
                'exam' => $exam,
                'count' => count($examAssignments),
                'mean' => $mean !== null ? round($mean, 2) : null,
                'std'  => $std,
            ];
        }

        $recentAssignments = $assignRepo->createQueryBuilder('a')
            ->leftJoin('a.exam', 'e')->addSelect('e')
            ->leftJoin('a.student', 's')->addSelect('s')
            ->where('a.status = :status')
            ->andWhere('e.teacher = :teacher')   // <-- adapte le champ si besoin
            ->setParameter('status', 'SUBMITTED')
            ->setParameter('teacher', $teacher)
            ->orderBy('a.submittedAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // 🧾 Rendu de la vue
        return $this->render('teacher/dashboard.html.twig', [
            'exams' => $exams,
            'totalAssignments' => $totalAssignments,
            'submissionRate' => $submissionRate,
            'uniqueStudentsCount' => $uniqueStudentsCount,
            'stats' => $stats,
            'recentAssignments' => $recentAssignments,
        ]);
    }
}
