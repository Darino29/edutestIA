<?php

namespace App\Controller;

use App\Entity\Assignment;
use App\Entity\Answer;
use App\Entity\Choice;
use App\Service\AutoGrader;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STUDENT')]
#[Route('/student')]
class ExamPassController extends AbstractController
{
    #[Route('/exams', name: 'student_exams')]
    public function list(EntityManagerInterface $em): Response
    {
        $student = $this->getUser();
        if (!$student) {
            return $this->redirectToRoute('app_login');
        }

        $repo = $em->getRepository(Assignment::class);
        $assignments = $repo->createQueryBuilder('a')
            ->leftJoin('a.exam', 'e')->addSelect('e')
            ->where('a.student = :student')
            ->setParameter('student', $student)
            ->orderBy('e.startAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('exam_pass/list.html.twig', [
            'assignments' => $assignments ?? [],
        ]);
    }

    #[Route('/exam/{id}/start', name: 'student_exam_start')]
    public function start(Assignment $assignment, EntityManagerInterface $em): Response
    {
        if ($assignment->getStudent() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($assignment->getStatus() === 'ASSIGNED') {
            $assignment->setStatus('STARTED');
            $assignment->setStartedAt(new \DateTimeImmutable());

            // Créer une réponse vide pour chaque question
            foreach ($assignment->getExam()->getQuestions() as $question) {
                $answer = new Answer();
                $answer->setAssignment($assignment);
                $answer->setQuestion($question);
                $answer->setAnswerText('');
                $em->persist($answer);
            }

            $em->flush();
        }

        return $this->redirectToRoute('student_exam_run', ['id' => $assignment->getId()]);
    }

    #[Route('/exam/{id}/run', name: 'student_exam_run')]
    public function run(Assignment $assignment, EntityManagerInterface $em): Response
    {
        if ($assignment->getStudent() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $exam = $assignment->getExam();

        // ✅ Initialise le début s’il n’existe pas encore
        if (!$assignment->getStartedAt()) {
            $assignment->setStartedAt(new \DateTimeImmutable());
            $assignment->setStatus('STARTED');
            $em->flush();
        }

        // ✅ Calcul du deadline
        $deadline = $assignment->getStartedAt()->add(
            new \DateInterval('PT' . $exam->getDurationMinutes() . 'M')
        );

        // 🧭 Si le temps est écoulé, on redirige
        if ($deadline <= new \DateTimeImmutable()) {
            $this->addFlash('warning', '⏰ Le temps imparti est écoulé. Votre examen a été soumis automatiquement.');
            return $this->redirectToRoute('student_results');
        }

        return $this->render('exam_pass/run.html.twig', [
            'assignment' => $assignment,
            'exam' => $exam,
            'questions' => $exam->getQuestions(),
            'deadline' => $deadline->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/exam/{id}/submit', name: 'student_exam_submit', methods: ['POST'])]
    public function submit(Request $request, Assignment $assignment, EntityManagerInterface $em, AutoGrader $grader): Response
    {
        if ($assignment->getStudent() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $answersData = $request->request->all('answers');

        foreach ($assignment->getExam()->getQuestions() as $question) {
            $answer = $em->getRepository(Answer::class)->findOneBy([
                'assignment' => $assignment,
                'question' => $question,
            ]);

            if (!$answer) {
                $answer = new Answer();
                $answer->setAssignment($assignment);
                $answer->setQuestion($question);
                $em->persist($answer);
            }

            if ($question->getType() === 'QCM') {
                $selectedChoices = $answersData[$question->getId()] ?? [];
                $answer->getSelectedChoices()->clear();

                foreach ($selectedChoices as $choiceId) {
                    $choice = $em->getRepository(Choice::class)->find($choiceId);
                    if ($choice) {
                        $answer->addSelectedChoice($choice);
                    }
                }
            } else {
                $text = $answersData[$question->getId()]['text'] ?? '';
                $answer->setAnswerText($text);
            }
        }

        $proctorRaw = (string) $request->request->get('proctor', '{}');
        $proctor = json_decode($proctorRaw, true);
        if (!is_array($proctor)) {
            $proctor = [];
        }

        $tabHidden = (int) ($proctor['tabHiddenCount'] ?? 0);
        $copy = (int) ($proctor['copyCount'] ?? 0);
        $paste = (int) ($proctor['pasteCount'] ?? 0);

        // règle simple
        $isFlagged = ($tabHidden >= 2) || ($copy + $paste >= 2);

        $assignment->setProctoringReport($proctor);
        $assignment->setIsFlagged($isFlagged);


        $em->flush();

        $finalGrade = $grader->grade($assignment);
        $assignment->setStatus('SUBMITTED');
        $assignment->setSubmittedAt(new \DateTimeImmutable());
        $assignment->setFinalGrade($finalGrade);
        $em->flush();

        $this->addFlash('success', "Examen soumis avec succès ! Note : $finalGrade / 20");

        return $this->redirectToRoute('student_results');
    }

    #[Route('/results', name: 'student_results')]
    public function results(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        $student = $this->getUser();

        $query = $em->getRepository(Assignment::class)
            ->createQueryBuilder('a')
            ->leftJoin('a.exam', 'e')->addSelect('e')
            ->where('a.student = :student')
            ->setParameter('student', $student)
            ->orderBy('a.submittedAt', 'DESC')
            ->getQuery();

        $assignments = $paginator->paginate($query, $request->query->getInt('page', 1), 8);

        return $this->render('exam_pass/results.html.twig', [
            'assignments' => $assignments,
        ]);
    }

    #[Route('/transcript', name: 'student_transcript')]
    public function transcript(EntityManagerInterface $em): Response
    {
        $student = $this->getUser();

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

    #[Route('/result/{id}', name: 'student_result_detail')]
    public function resultDetail(Assignment $assignment): Response
    {
        if ($assignment->getStudent() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('exam_pass/result_detail.html.twig', [
            'assignment' => $assignment,
            'exam' => $assignment->getExam(),
            'answers' => $assignment->getAnswers(),
        ]);
    }
}
