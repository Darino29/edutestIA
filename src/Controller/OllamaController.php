<?php

namespace App\Controller;

use App\Entity\Choice;
use App\Entity\Exam;
use App\Entity\Question;
use App\Form\ExamType;
use App\Service\GroqService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ai')]
class OllamaController extends AbstractController
{
    public function __construct(private GroqService $aiService)
    {
    }

    // #[Route('/dashboard', name: 'app_ai_dashboard')]
    // public function index(): Response
    // {
    //     return $this->render('ollama/index.html.twig');
    // }

    #[Route('/revision', name: 'app_ai_revision')]
    public function revision(Request $request): Response
    {
        $topic = $request->request->get('topic');
        $result = null;

        if ($request->isMethod('POST') && $topic) {
            $result = $this->aiService->generateRevisionNotes($topic);
        }

        return $this->render('ollama/revision.html.twig', [
            'result' => $result,
            'topic' => $topic,
        ]);
    }

    #[Route('/quiz', name: 'app_ai_quiz')]
    public function quiz(Request $request, EntityManagerInterface $em): Response
    {
        // 1. Gestion via le formulaire de création d'examen (Générer IA)
        $exam = new Exam();
        $form = $this->createForm(ExamType::class, $exam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $topic = $exam->getTitle() . ' ' . ($exam->getDescription() ?? '');
            $difficulty = 'Moyen';

            $quizData = $this->aiService->generateQuiz($topic, $difficulty);

            // Vérifie si une erreur est retournée par l'IA
            if (isset($quizData['error'])) {
                $this->addFlash('danger', $quizData['error']);
                return $this->redirectToRoute('app_exam_new');
            }

            if ($quizData && is_array($quizData)) {
                $exam->setTeacher($this->getUser());
                $em->persist($exam);

                foreach ($quizData as $qData) {
                    $question = new Question();
                    $question->setExam($exam);
                    $question->setText($qData['question'] ?? '');
                    $question->setType('QCM');
                    $question->setPoints(1);
                    $em->persist($question);

                    // Sécurise l'accès à 'choices'
                    if (isset($qData['choices']) && is_array($qData['choices'])) {
                        foreach ($qData['choices'] as $cData) {
                            $choice = new Choice();
                            $choice->setQuestion($question);

                            // Utilise la bonne méthode pour le texte du choix
                            if (method_exists($choice, 'setText')) {
                                $choice->setText($cData['text'] ?? '');
                            } elseif (method_exists($choice, 'setLabel')) {
                                $choice->setLabel($cData['text'] ?? '');
                            } elseif (method_exists($choice, 'setContent')) {
                                $choice->setContent($cData['text'] ?? '');
                            }

                            // Pour la correction
                            if (method_exists($choice, 'setIsCorrect')) {
                                $choice->setIsCorrect($cData['isCorrect'] ?? false);
                            }

                            $em->persist($choice);
                        }
                    }
                }

                $em->flush();
                $this->addFlash('success', 'Examen généré automatiquement avec succès !');

                return $this->redirectToRoute('app_exam_show', ['id' => $exam->getId()]);
            }
        }
        // 2. Gestion classique (page dédiée)
        $topic = $request->request->get('topic');
        $difficulty = $request->request->get('difficulty', 'Moyen');
        $quiz = null;

        if ($request->isMethod('POST') && $topic) {
            $quiz = $this->aiService->generateQuiz($topic, $difficulty);
        }

        return $this->render('ollama/quiz.html.twig', [
            'quiz' => $quiz,
            'topic' => $topic,
            'difficulty' => $difficulty,
        ]);
    }
}
