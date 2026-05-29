<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\User;
use App\Repository\ClasseRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/classes')]
class ClasseController extends AbstractController
{
    #[Route('', name: 'admin_classes_index')]
    public function index(ClasseRepository $repo): Response
    {
        return $this->render('classe/index.html.twig', [
            'classes' => $repo->findAllOrderedByLevel(),
        ]);
    }

    #[Route('/new', name: 'admin_classes_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $classe = new Classe();
            $classe->setName(trim($request->request->get('name', '')));
            $classe->setLevel($request->request->get('level', 'BTS'));
            $classe->setYear((int) $request->request->get('year', 1));
            $classe->setAcademicYear($request->request->get('academicYear', ''));

            if (!$classe->getName()) {
                $this->addFlash('danger', 'Le nom de la classe est obligatoire.');
                return $this->redirectToRoute('admin_classes_new');
            }

            $em->persist($classe);
            $em->flush();

            $this->addFlash('success', 'Classe "' . $classe->getFullLabel() . '" créée avec succès.');
            return $this->redirectToRoute('admin_classes_show', ['id' => $classe->getId()]);
        }

        return $this->render('classe/new.html.twig', [
            'levels'       => Classe::LEVELS,
            'academicYear' => $this->buildAcademicYear(),
        ]);
    }

    #[Route('/{id}', name: 'admin_classes_show')]
    public function show(Classe $classe, UserRepository $userRepo): Response
    {
        $currentStudentIds = $classe->getStudents()->map(fn(User $u) => $u->getId())->toArray();
        $currentTeacherIds = $classe->getTeachers()->map(fn(User $u) => $u->getId())->toArray();

        $availableStudents = array_filter(
            $userRepo->findByRole('ROLE_STUDENT'),
            fn(User $u) => $u->isApproved() && !in_array($u->getId(), $currentStudentIds, true)
        );

        $availableTeachers = array_filter(
            $userRepo->findByRole('ROLE_TEACHER'),
            fn(User $u) => $u->isApproved() && !in_array($u->getId(), $currentTeacherIds, true)
        );

        return $this->render('classe/show.html.twig', [
            'classe'             => $classe,
            'availableStudents'  => array_values($availableStudents),
            'availableTeachers'  => array_values($availableTeachers),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_classes_edit', methods: ['GET', 'POST'])]
    public function edit(Classe $classe, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $classe->setName(trim($request->request->get('name', '')));
            $classe->setLevel($request->request->get('level', 'BTS'));
            $classe->setYear((int) $request->request->get('year', 1));
            $classe->setAcademicYear($request->request->get('academicYear', ''));
            $em->flush();

            $this->addFlash('success', 'Classe mise à jour.');
            return $this->redirectToRoute('admin_classes_show', ['id' => $classe->getId()]);
        }

        return $this->render('classe/edit.html.twig', [
            'classe'       => $classe,
            'levels'       => Classe::LEVELS,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_classes_delete', methods: ['POST'])]
    public function delete(Classe $classe, EntityManagerInterface $em): Response
    {
        // Les étudiants auront classe = NULL (onDelete: SET NULL)
        $em->remove($classe);
        $em->flush();

        $this->addFlash('info', 'Classe supprimée.');
        return $this->redirectToRoute('admin_classes_index');
    }

    #[Route('/{id}/add-student', name: 'admin_classes_add_student', methods: ['POST'])]
    public function addStudent(Classe $classe, Request $request, EntityManagerInterface $em, UserRepository $userRepo): JsonResponse
    {
        $studentId = (int) $request->request->get('studentId');
        $student   = $userRepo->find($studentId);

        if (!$student || !in_array('ROLE_STUDENT', $student->getRoles(), true)) {
            return $this->json(['error' => 'Étudiant introuvable.'], 404);
        }

        $student->setClasse($classe);
        $em->flush();

        return $this->json([
            'success' => true,
            'student' => [
                'id'       => $student->getId(),
                'fullName' => $student->getFullName(),
                'email'    => $student->getEmail(),
            ],
        ]);
    }

    #[Route('/{id}/remove-student/{userId}', name: 'admin_classes_remove_student', methods: ['POST'])]
    public function removeStudent(Classe $classe, int $userId, EntityManagerInterface $em, UserRepository $userRepo): JsonResponse
    {
        $student = $userRepo->find($userId);

        if (!$student || $student->getClasse() !== $classe) {
            return $this->json(['error' => 'Étudiant non trouvé dans cette classe.'], 404);
        }

        $student->setClasse(null);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/add-teacher', name: 'admin_classes_add_teacher', methods: ['POST'])]
    public function addTeacher(Classe $classe, Request $request, EntityManagerInterface $em, UserRepository $userRepo): JsonResponse
    {
        $teacherId = (int) $request->request->get('teacherId');
        $teacher   = $userRepo->find($teacherId);

        if (!$teacher || !in_array('ROLE_TEACHER', $teacher->getRoles(), true)) {
            return $this->json(['error' => 'Enseignant introuvable.'], 404);
        }

        $classe->addTeacher($teacher);
        $em->flush();

        return $this->json([
            'success' => true,
            'teacher' => [
                'id'       => $teacher->getId(),
                'fullName' => $teacher->getFullName(),
                'email'    => $teacher->getEmail(),
            ],
        ]);
    }

    #[Route('/{id}/remove-teacher/{userId}', name: 'admin_classes_remove_teacher', methods: ['POST'])]
    public function removeTeacher(Classe $classe, int $userId, EntityManagerInterface $em, UserRepository $userRepo): JsonResponse
    {
        $teacher = $userRepo->find($userId);

        if (!$teacher) {
            return $this->json(['error' => 'Enseignant introuvable.'], 404);
        }

        $classe->removeTeacher($teacher);
        $em->flush();

        return $this->json(['success' => true]);
    }

    private function buildAcademicYear(): string
    {
        $month = (int) date('m');
        $year  = (int) date('Y');
        $start = $month >= 9 ? $year : $year - 1;
        return $start . '-' . ($start + 1);
    }
}
