<?php

namespace App\Controller;

use App\Service\GroqService;
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

    #[Route('/dashboard', name: 'app_ai_dashboard')]
    public function index(): Response
    {
        return $this->render('ollama/index.html.twig');
    }

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
    public function quiz(Request $request): Response
    {
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
