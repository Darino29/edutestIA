<?php

namespace App\Controller\Api;

use App\Entity\Answer;
use App\Entity\Assignment;
use App\Entity\Choice;
use App\Service\AutoGrader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/student')]
#[IsGranted('ROLE_STUDENT')]
class ApiStudentController extends AbstractController
{
    #[Route('/exams', name: 'api_student_exams', methods: ['GET'])]
    public function exams(EntityManagerInterface $em): JsonResponse
    {
        $assignments = $em->getRepository(Assignment::class)
            ->createQueryBuilder('a')
            ->leftJoin('a.exam', 'e')->addSelect('e')
            ->where('a.student = :student')
            ->setParameter('student', $this->getUser())
            ->orderBy('e.startAt', 'DESC')
            ->getQuery()
            ->getResult();

        $data = array_map(fn(Assignment $a) => [
            'assignmentId' => $a->getId(),
            'status'       => $a->getStatus(),
            'exam' => [
                'id'              => $a->getExam()->getId(),
                'title'           => $a->getExam()->getTitle(),
                'description'     => $a->getExam()->getDescription(),
                'durationMinutes' => $a->getExam()->getDurationMinutes(),
                'startAt'         => $a->getExam()->getStartAt()?->format('c'),
                'endAt'           => $a->getExam()->getEndAt()?->format('c'),
            ],
        ], $assignments);

        return $this->json($data);
    }

    #[Route('/exam/{id}', name: 'api_student_exam', methods: ['GET'])]
    public function exam(Assignment $assignment): JsonResponse
    {
        if ($assignment->getStudent() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $exam = $assignment->getExam();
        $questions = array_map(fn($q) => [
            'id'      => $q->getId(),
            'text'    => $q->getText(),
            'type'    => $q->getType(),
            'points'  => $q->getPoints(),
            'choices' => array_map(fn($c) => [
                'id'   => $c->getId(),
                'text' => method_exists($c, 'getText') ? $c->getText()
                        : (method_exists($c, 'getLabel') ? $c->getLabel() : $c->getContent()),
            ], $q->getChoices()->toArray()),
        ], $exam->getQuestions()->toArray());

        return $this->json([
            'assignmentId'   => $assignment->getId(),
            'status'         => $assignment->getStatus(),
            'startedAt'      => $assignment->getStartedAt()?->format('c'),
            'exam' => [
                'id'              => $exam->getId(),
                'title'           => $exam->getTitle(),
                'durationMinutes' => $exam->getDurationMinutes(),
                'questions'       => $questions,
            ],
        ]);
    }

    #[Route('/exam/{id}/start', name: 'api_student_exam_start', methods: ['POST'])]
    public function start(Assignment $assignment, EntityManagerInterface $em): JsonResponse
    {
        if ($assignment->getStudent() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        if ($assignment->getStatus() === 'ASSIGNED') {
            $assignment->setStatus('STARTED');
            $assignment->setStartedAt(new \DateTimeImmutable());

            foreach ($assignment->getExam()->getQuestions() as $question) {
                $answer = new Answer();
                $answer->setAssignment($assignment);
                $answer->setQuestion($question);
                $answer->setAnswerText('');
                $em->persist($answer);
            }
            $em->flush();
        }

        $deadline = $assignment->getStartedAt()->add(
            new \DateInterval('PT' . $assignment->getExam()->getDurationMinutes() . 'M')
        );

        return $this->json([
            'startedAt' => $assignment->getStartedAt()->format('c'),
            'deadline'  => $deadline->format('c'),
        ]);
    }

    #[Route('/exam/{id}/submit', name: 'api_student_exam_submit', methods: ['POST'])]
    public function submit(
        Assignment $assignment,
        Request $request,
        EntityManagerInterface $em,
        AutoGrader $grader
    ): JsonResponse {
        if ($assignment->getStudent() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        // answers: { "questionId": ["choiceId", ...] or "text": "..." }
        $answersData = $data['answers'] ?? [];

        foreach ($assignment->getExam()->getQuestions() as $question) {
            $answer = $em->getRepository(Answer::class)->findOneBy([
                'assignment' => $assignment,
                'question'   => $question,
            ]);

            if (!$answer) {
                $answer = new Answer();
                $answer->setAssignment($assignment);
                $answer->setQuestion($question);
                $em->persist($answer);
            }

            $qid = (string) $question->getId();

            if ($question->getType() === 'QCM') {
                $answer->getSelectedChoices()->clear();
                foreach (($answersData[$qid] ?? []) as $choiceId) {
                    $choice = $em->getRepository(Choice::class)->find($choiceId);
                    if ($choice) {
                        $answer->addSelectedChoice($choice);
                    }
                }
            } else {
                $answer->setAnswerText($answersData[$qid] ?? '');
            }
        }

        $em->flush();

        $finalGrade = $grader->grade($assignment);
        $assignment->setStatus('SUBMITTED');
        $assignment->setSubmittedAt(new \DateTimeImmutable());
        $assignment->setFinalGrade($finalGrade);
        $em->flush();

        return $this->json([
            'finalGrade' => $finalGrade,
            'message'    => "Examen soumis ! Note : $finalGrade / 20",
        ]);
    }

    #[Route('/results', name: 'api_student_results', methods: ['GET'])]
    public function results(EntityManagerInterface $em): JsonResponse
    {
        $assignments = $em->getRepository(Assignment::class)
            ->createQueryBuilder('a')
            ->leftJoin('a.exam', 'e')->addSelect('e')
            ->where('a.student = :student')
            ->setParameter('student', $this->getUser())
            ->orderBy('a.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();

        $data = array_map(fn(Assignment $a) => [
            'assignmentId' => $a->getId(),
            'status'       => $a->getStatus(),
            'finalGrade'   => $a->getFinalGrade(),
            'submittedAt'  => $a->getSubmittedAt()?->format('c'),
            'exam' => [
                'id'    => $a->getExam()->getId(),
                'title' => $a->getExam()->getTitle(),
            ],
        ], $assignments);

        return $this->json($data);
    }

    #[Route('/result/{id}', name: 'api_student_result_detail', methods: ['GET'])]
    public function resultDetail(Assignment $assignment, EntityManagerInterface $em): JsonResponse
    {
        if ($assignment->getStudent() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $answers = array_map(fn($ans) => [
            'questionText' => $ans->getQuestion()->getText(),
            'questionType' => $ans->getQuestion()->getType(),
            'points'       => $ans->getQuestion()->getPoints(),
            'answerText'   => $ans->getAnswerText(),
            'selectedChoices' => array_map(fn($c) => [
                'id'        => $c->getId(),
                'text'      => method_exists($c, 'getText') ? $c->getText()
                             : (method_exists($c, 'getLabel') ? $c->getLabel() : $c->getContent()),
                'isCorrect' => method_exists($c, 'isIsCorrect') ? $c->isIsCorrect() : false,
            ], $ans->getSelectedChoices()->toArray()),
        ], $assignment->getAnswers()->toArray());

        return $this->json([
            'assignmentId' => $assignment->getId(),
            'finalGrade'   => $assignment->getFinalGrade(),
            'submittedAt'  => $assignment->getSubmittedAt()?->format('c'),
            'exam'         => ['title' => $assignment->getExam()->getTitle()],
            'answers'      => $answers,
        ]);
    }
}
