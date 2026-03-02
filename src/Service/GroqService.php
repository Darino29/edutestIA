<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GroqService
{
    private const API_URL = "https://api.groq.com/openai/v1/chat/completions";
    private const MODEL = "llama-3.3-70b-versatile"; // Modèle rapide et performant

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        #[Autowire(env: 'GROQ_API_KEY')]
        private string $apiKey,
    ) {}

    public function generateCompletion(string $prompt, bool $isJson = false): string
    {
        $cacheKey = 'groq_' . md5($prompt);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($prompt, $isJson) {
            $item->expiresAfter(3600);

            try {
                $messages = [
                    ['role' => 'user', 'content' => $prompt]
                ];

                $options = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => self::MODEL,
                        'messages' => $messages,
                        'temperature' => 0.7,
                        'max_tokens' => 2048,
                    ],
                ];

                // Forcer le format JSON si nécessaire
                if ($isJson) {
                    $options['json']['response_format'] = ['type' => 'json_object'];
                }

                $response = $this->httpClient->request('POST', self::API_URL, $options);

                $statusCode = $response->getStatusCode();

                if (429 === $statusCode) {
                    return "ERREUR_QUOTA : Trop de requêtes. Veuillez patienter.";
                }

                $data = $response->toArray();
                return $data['choices'][0]['message']['content'] ?? '';

            } catch (\Exception $e) {
                $message = $e->getMessage();
                if (str_contains($message, $this->apiKey)) {
                    $message = str_replace($this->apiKey, '********', $message);
                }
                return 'ERREUR_API : ' . $message;
            }
        });
    }

    public function generateRevisionNotes(string $topic): string
    {
        $prompt = "Tu es un assistant pédagogique expert. Crée une fiche de révision complète, claire et structurée pour un étudiant sur le sujet : '{$topic}'. " .
                  "Utilise le format Markdown avec des titres, des points clés, et des exemples si nécessaire. " .
                  "Fais en sorte que ce soit visuellement agréable et facile à apprendre.";
        return $this->generateCompletion($prompt);
    }

    public function generateQuiz(string $topic, string $difficulty): array
    {
        $prompt = "Crée un quiz de 5 questions (QCM) sur '{$topic}' (difficulté '{$difficulty}'). " .
                  "Réponds avec un objet JSON contenant une clé 'questions' avec un tableau de 5 questions. " .
                  "Format: {\"questions\": [{\"question\": \"...\", \"options\": [\"A\", \"B\", \"C\", \"D\"], \"answer\": \"A\"}]}";
        
        $responseContent = $this->generateCompletion($prompt, true);

        if (str_starts_with($responseContent, 'ERREUR_')) {
            return ['error' => $responseContent];
        }

        $data = json_decode($responseContent, true);
        
        // Groq retourne parfois avec une clé "questions", on l'extrait
        if (isset($data['questions'])) {
            return $data['questions'];
        }

        return $data ?? [];
    }
}
