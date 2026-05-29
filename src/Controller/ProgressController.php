<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ProgressService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $student  = $this->getUser();
        $progress = $this->progressService->getStudentProgress($student);

        return $this->render('progress/student.html.twig', [
            'progress' => $progress,
        ]);
    }

    #[Route('/student/progress/recommendations', name: 'student_progress_recommendations')]
    #[IsGranted('ROLE_STUDENT')]
    public function studentProgressRecommendations(): JsonResponse
    {
        /** @var User $student */
        $student = $this->getUser();
        $text    = $this->progressService->getPersonalizedRecommendations($student);

        return $this->json(['text' => $text]);
    }

    /**
     * Vue enseignant : progression des étudiants pour ses propres examens.
     */
    #[Route('/teacher/students/progress', name: 'teacher_students_progress')]
    #[IsGranted('ROLE_TEACHER')]
    public function teacherOverview(): Response
    {
        /** @var User $teacher */
        $teacher     = $this->getUser();
        $allProgress = $this->progressService->getAllStudentsProgressForTeacher($teacher);

        return $this->render('progress/teacher_overview.html.twig', [
            'allProgress' => $allProgress,
            'teacher'     => $teacher,
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
