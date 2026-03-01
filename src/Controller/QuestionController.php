<?php

namespace App\Controller;

use App\Entity\Exam;
use App\Entity\Question;
use App\Form\QuestionType;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_TEACHER')]
#[Route('/question')]
final class QuestionController extends AbstractController
{
    #[Route(name: 'app_question_index', methods: ['GET'])]
    public function index(QuestionRepository $questionRepository): Response
    {
        return $this->render('question/index.html.twig', [
            'questions' => $questionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_question_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $question = new Question();

        $examId = $request->query->get('exam');
        if ($examId) {
            $exam = $em->getRepository(Exam::class)->find($examId);

            if ($exam) {
                if ($exam->getTeacher() !== $this->getUser()) {
                    throw $this->createAccessDeniedException();
                }
                $question->setExam($exam);
            }
        }

        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($question);
            $em->flush();

            // Redirection vers l’examen associé (si défini)
            if ($question->getExam()) {
                return $this->redirectToRoute('app_exam_show', [
                    'id' => $question->getExam()->getId(),
                ]);
            }

            return $this->redirectToRoute('app_question_index');
        }

        return $this->render('question/new.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_question_show', methods: ['GET'])]
    public function show(Question $question): Response
    {
        return $this->render('question/show.html.twig', [
            'question' => $question,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_question_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Question $question, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            if ($question->getExam()) {
                return $this->redirectToRoute('app_exam_show', [
                    'id' => $question->getExam()->getId(),
                ]);
            }

            return $this->redirectToRoute('app_question_index');
        }

        return $this->render('question/edit.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_question_delete', methods: ['POST'])]
    public function delete(Request $request, Question $question, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $question->getId(), $request->request->get('_token'))) {
            $exam = $question->getExam(); // sauvegarde avant suppression
            $em->remove($question);
            $em->flush();

            $this->addFlash('info', '🗑️ Question supprimée avec succès.');

            if ($exam) {
                return $this->redirectToRoute('app_exam_show', ['id' => $exam->getId()]);
            }
        }

        return $this->redirectToRoute('app_question_index');
    }
}
