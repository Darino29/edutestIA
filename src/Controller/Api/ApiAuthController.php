<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ApiAuthController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user || !$hasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Identifiants invalides'], 401);
        }

        if (!$user->isApproved()) {
            return $this->json(['error' => 'Compte en attente d\'approbation'], 403);
        }

        $token = $user->regenerateApiToken();
        $em->flush();

        return $this->json([
            'token' => $token,
            'user' => [
                'id'       => $user->getId(),
                'email'    => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'roles'    => $user->getRoles(),
            ],
        ]);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if ($user) {
            $user->setApiToken(null);
            $em->flush();
        }

        return $this->json(['success' => true]);
    }
}
