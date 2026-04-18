<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\AssignmentRepository;
use App\Repository\UserRepository;

class ProgressService
{
    public function __construct(
        private AssignmentRepository $assignmentRepository,
        private UserRepository $userRepository,
        private GroqService $groqService,
    ) {}

    /**
     * Calcule la progression d'un étudiant par sujet (titre d'examen).
     * Retourne 3 listes : mastered (≥75%), inProgress (50–74%), toWork (<50%).
     */
    public function getStudentProgress(User $student): array
    {
        $assignments = $this->assignmentRepository->findBy([
            'student' => $student,
            'status'  => 'SUBMITTED',
        ]);

        $topicStats = [];

        foreach ($assignments as $assignment) {
            $grade = $assignment->getFinalGrade();
            if ($grade === null) {
                continue;
            }

            $topic      = $assignment->getExam()->getTitle();
            $percentage = ($grade / 20) * 100;

            if (!isset($topicStats[$topic])) {
                $topicStats[$topic] = ['scores' => [], 'count' => 0];
            }
            $topicStats[$topic]['scores'][] = $percentage;
            $topicStats[$topic]['count']++;
        }

        $mastered   = [];
        $inProgress = [];
        $toWork     = [];

        foreach ($topicStats as $topic => $stats) {
            $avgScore = array_sum($stats['scores']) / count($stats['scores']);
            $entry = [
                'topic'      => $topic,
                'score'      => round($avgScore, 1),
                'examsCount' => $stats['count'],
            ];

            if ($avgScore >= 75) {
                $mastered[] = $entry;
            } elseif ($avgScore >= 50) {
                $inProgress[] = $entry;
            } else {
                $toWork[] = $entry;
            }
        }

        usort($mastered,   fn($a, $b) => $b['score'] <=> $a['score']);
        usort($inProgress, fn($a, $b) => $b['score'] <=> $a['score']);
        usort($toWork,     fn($a, $b) => $a['score'] <=> $b['score']); // pires en premier

        return compact('mastered', 'inProgress', 'toWork');
    }

    /**
     * Génère des recommandations IA personnalisées basées sur la progression.
     */
    public function getPersonalizedRecommendations(User $student): string
    {
        $progress = $this->getStudentProgress($student);

        if (
            empty($progress['mastered']) &&
            empty($progress['inProgress']) &&
            empty($progress['toWork'])
        ) {
            return '';
        }

        return $this->groqService->generatePersonalizedRecommendations(
            array_column($progress['toWork'],     'topic'),
            array_column($progress['inProgress'], 'topic'),
            array_column($progress['mastered'],   'topic')
        );
    }

    /**
     * Retourne la progression de tous les étudiants approuvés.
     * Utilisé par la vue enseignant.
     */
    public function getAllStudentsProgress(): array
    {
        $students = $this->userRepository->findByRole('ROLE_STUDENT');
        $result   = [];

        foreach ($students as $student) {
            if (!$student->isApproved()) {
                continue;
            }

            $progress = $this->getStudentProgress($student);
            $total    = count($progress['mastered']) + count($progress['inProgress']) + count($progress['toWork']);

            if ($total === 0) {
                continue; // Pas encore d'examens soumis
            }

            $result[] = [
                'student'        => $student,
                'masteredCount'  => count($progress['mastered']),
                'inProgressCount'=> count($progress['inProgress']),
                'toWorkCount'    => count($progress['toWork']),
                'totalTopics'    => $total,
                'progress'       => $progress,
            ];
        }

        return $result;
    }
}
