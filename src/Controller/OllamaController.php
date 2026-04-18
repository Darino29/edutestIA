<?php

namespace App\Controller;

use App\Entity\Choice;
use App\Entity\Exam;
use App\Entity\Question;
use App\Entity\SavedRevision;
use App\Form\ExamType;
use App\Repository\SavedRevisionRepository;
use App\Service\GroqService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

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
    public function revision(Request $request, SavedRevisionRepository $repo): Response
    {
        $topic = null;
        $result = null;

        if ($request->isMethod('POST')) {
            $topic = $request->request->get('topic');
            if ($topic) {
                $result = $this->aiService->generateRevisionNotes($topic);
            }
        } elseif ($loadId = $request->query->getInt('load')) {
            $saved = $repo->find($loadId);
            if ($saved && $saved->getUser() === $this->getUser()) {
                $topic = $saved->getTopic();
                $result = $saved->getContent();
            }
        }

        $savedRevisions = $this->getUser() ? $repo->findByUser($this->getUser()) : [];

        return $this->render('ollama/revision.html.twig', [
            'result' => $result,
            'topic' => $topic ?? '',
            'savedRevisions' => $savedRevisions,
        ]);
    }

    #[Route('/revision/save', name: 'app_ai_revision_save', methods: ['POST'])]
    public function saveRevision(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data = json_decode($request->getContent(), true);
        $topic = trim($data['topic'] ?? '');
        $content = trim($data['content'] ?? '');

        if (!$topic || !$content) {
            return $this->json(['error' => 'Données manquantes'], 400);
        }

        $saved = new SavedRevision($topic, $content, $this->getUser());
        $em->persist($saved);
        $em->flush();

        return $this->json(['id' => $saved->getId(), 'topic' => $saved->getTopic()]);
    }

    #[Route('/revision/saved/{id}/delete', name: 'app_ai_revision_delete', methods: ['POST'])]
    public function deleteRevision(SavedRevision $savedRevision, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($savedRevision->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $em->remove($savedRevision);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/groq/speech', name: 'groq_speech', methods: ['POST'])]
    public function speech(Request $request, GroqService $groqService, LoggerInterface $logger): Response
    {
        $data = json_decode($request->getContent(), true);
        $logger->info('Speech request payload', ['data' => $data]);

        $text = $data['text'] ?? '';
        $logger->info('Speech text', ['text' => $text]);

        $audio = $groqService->generateSpeech($text);

        if (!$audio) {
            $logger->error('Speech generation failed');
            return new Response('Erreur lors de la génération audio', 500);
        }

        $logger->info('Speech generation succeeded');
        return new Response($audio, 200, [
            'Content-Type' => 'audio/mpeg'
        ]);
    }

    #[Route('/chat', name: 'app_ai_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data     = json_decode($request->getContent(), true);
        $messages = $data['messages'] ?? [];

        if (empty($messages)) {
            return $this->json(['error' => 'Messages manquants'], 400);
        }

        // Garder uniquement les 10 derniers échanges pour limiter les tokens
        $messages = array_slice($messages, -10);

        $reply = $this->aiService->chatCompletion($messages);

        return $this->json(['reply' => $reply]);
    }

    #[Route('/explain', name: 'app_ai_explain')]
    public function explain(Request $request): Response
    {
        $topic = null;
        $level = 'intermédiaire';
        $result = null;

        if ($request->isMethod('POST')) {
            $topic = $request->request->get('topic');
            $level = $request->request->get('level', 'intermédiaire');
            if ($topic) {
                $result = $this->aiService->generateLessonExplanation($topic, $level);
            }
        }

        return $this->render('ollama/explain.html.twig', [
            'result' => $result,
            'topic' => $topic ?? '',
            'level' => $level,
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
