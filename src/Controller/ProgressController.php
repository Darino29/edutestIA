<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ProgressService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProgressController extends AbstractController
{
    public function __construct(private ProgressService $progressService) {}

    /**
     * Dashboard de progression personnalisé pour l'étudiant.
     */
    #[Route('/student/progress', name: 'student_progress')]
    #[IsGranted('ROLE_STUDENT')]
    public function studentProgress(): Response
    {
        /** @var User $student */
        $student         = $this->getUser();
        $progress        = $this->progressService->getStudentProgress($student);
        $recommendations = $this->progressService->getPersonalizedRecommendations($student);

        return $this->render('progress/student.html.twig', [
            'progress'        => $progress,
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * Vue enseignant : progression de tous les étudiants.
     */
    #[Route('/teacher/students/progress', name: 'teacher_students_progress')]
    #[IsGranted('ROLE_TEACHER')]
    public function teacherOverview(): Response
    {
        $allProgress = $this->progressService->getAllStudentsProgress();

        return $this->render('progress/teacher_overview.html.twig', [
            'allProgress' => $allProgress,
        ]);
    }

    /**
     * Vue enseignant : progression détaillée d'un étudiant.
     */
    #[Route('/teacher/students/{id}/progress', name: 'teacher_student_progress')]
    #[IsGranted('ROLE_TEACHER')]
    public function teacherStudentDetail(User $student): Response
    {
        $progress = $this->progressService->getStudentProgress($student);

        return $this->render('progress/teacher_student_detail.html.twig', [
            'student'  => $student,
            'progress' => $progress,
        ]);
    }
}
