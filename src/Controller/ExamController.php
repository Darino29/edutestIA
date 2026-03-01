<?php

namespace App\Controller;

use App\Entity\Exam;
use App\Form\ExamType;
use App\Repository\ExamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/exam')]
final class ExamController extends AbstractController
{
    #[Route(name: 'app_exam_index', methods: ['GET'])]
    public function index(ExamRepository $examRepository): Response
    {
        $user = $this->getUser();

        // si prof connecté, on filtre
        if ($this->isGranted('ROLE_TEACHER')) {
            return $this->render('exam/index.html.twig', [
                'exams' => $examRepository->findBy(['teacher' => $user], ['id' => 'DESC']),
            ]);
        }

        // sinon (admin) tu peux garder tout
        return $this->render('exam/index.html.twig', [
            'exams' => $examRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_exam_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $exam = new Exam();
        $form = $this->createForm(ExamType::class, $exam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $exam->setTeacher($this->getUser()); // le user connecté (teacher)
            $entityManager->persist($exam);
            $entityManager->flush();

            return $this->redirectToRoute('app_exam_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('exam/new.html.twig', [
            'exam' => $exam,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_exam_show', methods: ['GET'])]
    public function show(Exam $exam): Response
    {
        return $this->render('exam/show.html.twig', [
            'exam' => $exam,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_exam_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Exam $exam, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ExamType::class, $exam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_exam_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('exam/edit.html.twig', [
            'exam' => $exam,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_exam_delete', methods: ['POST'])]
    public function delete(Request $request, Exam $exam, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$exam->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($exam);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_exam_index', [], Response::HTTP_SEE_OTHER);
    }
}
