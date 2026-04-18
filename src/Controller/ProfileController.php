<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/profile')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig');
    }

    #[Route('/upload-photo', name: 'app_profile_upload_photo', methods: ['POST'])]
    public function uploadPhoto(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $file = $request->files->get('photo');

        if (!$file) {
            return $this->json(['error' => 'Aucun fichier reçu.'], 400);
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowed, true)) {
            return $this->json(['error' => 'Format non supporté. Utilisez JPG, PNG ou WebP.'], 400);
        }

        if ($file->getSize() > 2 * 1024 * 1024) {
            return $this->json(['error' => 'Fichier trop volumineux (max 2 Mo).'], 400);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';

        // Supprimer l'ancienne photo
        if ($user->getProfilePicture()) {
            $old = $uploadDir . '/' . $user->getProfilePicture();
            if (file_exists($old)) {
                unlink($old);
            }
        }

        $filename = 'avatar_' . $user->getId() . '_' . uniqid() . '.' . $file->guessExtension();
        $file->move($uploadDir, $filename);

        $user->setProfilePicture($filename);
        $em->flush();

        return $this->json([
            'success' => true,
            'url'     => '/uploads/avatars/' . $filename,
        ]);
    }

    #[Route('/delete-photo', name: 'app_profile_delete_photo', methods: ['POST'])]
    public function deletePhoto(EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getProfilePicture()) {
            $path = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $user->getProfilePicture();
            if (file_exists($path)) {
                unlink($path);
            }
            $user->setProfilePicture(null);
            $em->flush();
        }

        return $this->json(['success' => true]);
    }
}
