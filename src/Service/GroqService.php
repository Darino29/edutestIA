<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GroqService
{
    private const API_URL = "https://api.groq.com/openai/v1/chat/completions";
    // private const MODEL = "llama-3.3-70b-versatile"; // Modèle rapide et performant (très couteux en tokens)
    private const MODEL = "llama-3.1-8b-instant"; // Modèle plus rapide et moins coûteux
    private const SPEECH_MODEL = "canopylabs/orpheus-v1-english";

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
                        'temperature' => 1, //0.7
                        'max_tokens' => 1024, //2048
                        'top_p' => 1,
                        'stream' => false,
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

    public function generateSpeech(string $text): ?string
    {
        error_log("generateSpeech called with text: " . $text);

        $escapedText = escapeshellarg($text);
        $command = "python " . __DIR__ . "/tts.py $escapedText";
        $output = shell_exec($command);
        error_log("Python command output: " . $output);

        $outputFile = __DIR__ . "/output.mp3";
        if (file_exists($outputFile)) {
            $audio = file_get_contents($outputFile);
            unlink($outputFile);
            error_log("Audio file generated and read successfully.");
            return $audio;
        }
        error_log("Audio file not found after Python execution.");
        return null;
    }

    public function generateRevisionNotes(string $topic): string
    {
        $prompt = "Tu es un assistant pédagogique expert. Crée une fiche de révision complète, claire et structurée pour un étudiant sur le sujet : '{$topic}'. " .
                  "Utilise le format Markdown avec des titres, des points clés, et des exemples si nécessaire. " .
                  "Fais en sorte que ce soit visuellement agréable et facile à apprendre.";
        return $this->generateCompletion($prompt);
    }

    public function chatCompletion(array $messages): string
    {
        try {
            $allMessages = array_merge([
                ['role' => 'system', 'content' => 'Tu es EduBot, assistant pédagogique bienveillant de la plateforme EduTest. Tu aides les étudiants et enseignants avec leurs cours, révisions et questions sur la plateforme. Réponds toujours en français, de façon concise (3-4 phrases max) et encourageante.'],
            ], $messages);

            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => self::MODEL,
                    'messages'    => $allMessages,
                    'temperature' => 0.7,
                    'max_tokens'  => 512,
                    'stream'      => false,
                ],
            ]);

            if ($response->getStatusCode() === 429) {
                return 'Trop de requêtes. Veuillez patienter quelques secondes.';
            }

            $data = $response->toArray();
            return $data['choices'][0]['message']['content'] ?? '';
        } catch (\Exception $e) {
            return 'Désolé, une erreur est survenue. Réessayez dans un moment.';
        }
    }

    public function generatePlatformInsights(array $stats): string
    {
        $weakTopics = !empty($stats['weakTopics']) ? implode(', ', $stats['weakTopics']) : 'N/A';
        $prompt = "Tu es un expert en analyse pédagogique. Voici les statistiques de la plateforme EduTest :\n\n" .
                  "- Étudiants inscrits : {$stats['totalStudents']}\n" .
                  "- Enseignants : {$stats['totalTeachers']}\n" .
                  "- Examens créés : {$stats['totalExams']}\n" .
                  "- Taux de soumission des examens : {$stats['submissionRate']}%\n" .
                  "- Étudiants en difficulté (score moyen < 50%) : {$stats['strugglingStudents']}\n" .
                  "- Score moyen global : {$stats['avgScore']}%\n" .
                  "- Sujets les plus échoués : {$weakTopics}\n\n" .
                  "Génère un rapport d'analyse en Markdown avec :\n" .
                  "## Bilan global\n" .
                  "## Points d'attention\n" .
                  "## Recommandations pour l'administrateur\n" .
                  "## Actions prioritaires\n" .
                  "Sois précis, objectif et orienté action.";
        return $this->generateCompletion($prompt);
    }

    public function generateLessonExplanation(string $topic, string $level = 'intermédiaire'): string
    {
        $prompt = "Tu es un professeur expert et pédagogue. Explique le sujet '{$topic}' de manière claire et engageante pour un niveau {$level}. " .
                  "Utilise le format Markdown avec les sections suivantes :\n" .
                  "## Introduction\n" .
                  "## Concepts clés (avec analogies du quotidien)\n" .
                  "## Exemples concrets et détaillés\n" .
                  "## Erreurs courantes à éviter\n" .
                  "## Points essentiels à retenir\n" .
                  "Sois précis, pédagogique et adapté au niveau {$level}. Utilise des exemples concrets et des métaphores pour faciliter la compréhension.";
        return $this->generateCompletion($prompt);
    }

    public function generatePersonalizedRecommendations(array $weakTopics, array $inProgressTopics, array $strongTopics): string
    {
        if (empty($weakTopics) && empty($inProgressTopics) && empty($strongTopics)) {
            return '';
        }

        $weakStr = !empty($weakTopics) ? implode(', ', $weakTopics) : 'aucun';
        $inProgressStr = !empty($inProgressTopics) ? implode(', ', $inProgressTopics) : 'aucun';
        $strongStr = !empty($strongTopics) ? implode(', ', $strongTopics) : 'aucun';

        $prompt = "Tu es un conseiller pédagogique bienveillant et expert. Voici le profil de progression d'un étudiant :\n" .
                  "- Compétences maîtrisées (score ≥ 75%) : {$strongStr}\n" .
                  "- En cours d'apprentissage (score 50-74%) : {$inProgressStr}\n" .
                  "- À travailler en priorité (score < 50%) : {$weakStr}\n\n" .
                  "Génère un plan de recommandations personnalisées en Markdown avec :\n" .
                  "## Diagnostic de ton profil\n" .
                  "## Priorités de révision (domaines faibles)\n" .
                  "## Stratégies pour progresser (domaines en cours)\n" .
                  "## Comment consolider tes points forts\n" .
                  "## Plan de révision hebdomadaire suggéré\n" .
                  "Sois encourageant, précis et propose des actions concrètes.";

        return $this->generateCompletion($prompt);
    }

    public function generateQuiz(string $topic, string $difficulty): array
    {
        $prompt = "Génère un quiz de 5 questions uniquement de type QCM sur '{$topic}' (difficulté '{$difficulty}').
            Chaque question doit avoir exactement 4 choix, dont un seul correct.
            Réponds avec un objet JSON contenant une clé 'questions' avec un tableau de questions, chacune au format :
            {
            \"question\": \"...\",
            \"choices\": [
                {\"text\": \"...\", \"isCorrect\": true/false},
                ...
            ]
            }
            Exemple :
            {
            \"questions\": [
                {
                \"question\": \"Quelle est la capitale de la France ?\",
                \"choices\": [
                    {\"text\": \"Paris\", \"isCorrect\": true},
                    {\"text\": \"Lyon\", \"isCorrect\": false},
                    {\"text\": \"Marseille\", \"isCorrect\": false},
                    {\"text\": \"Nice\", \"isCorrect\": false}
                ]
                }
            ]
            }";
        
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
