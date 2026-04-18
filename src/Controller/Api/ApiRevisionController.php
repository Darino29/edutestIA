<?php

namespace App\Controller\Api;

use App\Entity\SavedRevision;
use App\Repository\SavedRevisionRepository;
use App\Service\GroqService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/ai')]
class ApiRevisionController extends AbstractController
{
    #[Route('/revision', name: 'api_ai_revision', methods: ['POST'])]
    public function generate(Request $request, GroqService $groq): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $topic = trim($data['topic'] ?? '');

        if (!$topic) {
            return $this->json(['error' => 'Sujet manquant'], 400);
        }

        $result = $groq->generateRevisionNotes($topic);

        return $this->json(['topic' => $topic, 'content' => $result]);
    }

    #[Route('/revisions', name: 'api_ai_revisions_list', methods: ['GET'])]
    public function list(SavedRevisionRepository $repo): JsonResponse
    {
        $revisions = $repo->findByUser($this->getUser());

        $data = array_map(fn(SavedRevision $r) => [
            'id'        => $r->getId(),
            'topic'     => $r->getTopic(),
            'content'   => $r->getContent(),
            'createdAt' => $r->getCreatedAt()->format('c'),
        ], $revisions);

        return $this->json($data);
    }

    #[Route('/revisions', name: 'api_ai_revisions_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $topic = trim($data['topic'] ?? '');
        $content = trim($data['content'] ?? '');

        if (!$topic || !$content) {
            return $this->json(['error' => 'Données manquantes'], 400);
        }

        $saved = new SavedRevision($topic, $content, $this->getUser());
        $em->persist($saved);
        $em->flush();

        return $this->json(['id' => $saved->getId(), 'topic' => $saved->getTopic()], 201);
    }

    #[Route('/revisions/{id}', name: 'api_ai_revisions_delete', methods: ['DELETE'])]
    public function delete(SavedRevision $savedRevision, EntityManagerInterface $em): JsonResponse
    {
        if ($savedRevision->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $em->remove($savedRevision);
        $em->flush();

        return $this->json(['success' => true]);
    }
}
