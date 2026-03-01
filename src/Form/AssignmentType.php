<?php

namespace App\Form;

use App\Entity\Assignment;
use App\Entity\Exam;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    $teacher = $options['teacher'] ?? null;
    $exam = $options['exam'] ?? null;

    $builder->add('exam', EntityType::class, [
        'class' => Exam::class,
        'choice_label' => 'title',
        'placeholder' => 'Sélectionner un examen',
        'attr' => ['class' => 'form-select'],
        'query_builder' => function (EntityRepository $er) use ($teacher) {
            $qb = $er->createQueryBuilder('e')->orderBy('e.id', 'DESC');

            if ($teacher) {
                $qb->where('e.teacher = :t')->setParameter('t', $teacher);
            }

            return $qb;
        },
        ])

        // Sélection de l'étudiant

        ->add('student', EntityType::class, [
            'class' => User::class,
            'choice_label' => fn(User $u) =>
                sprintf('%s (%s)', $u->getFullName() ?: 'Utilisateur', $u->getEmail()),
            'query_builder' => function (EntityRepository $er) use ($exam) {
                $qb = $er->createQueryBuilder('u')
                    ->where('u.roles LIKE :role')
                    ->setParameter('role', '%ROLE_STUDENT%')
                    ->orderBy('u.fullName', 'ASC');

                if ($exam) {
                    $qb->andWhere('u.id NOT IN (
                        SELECT IDENTITY(a.student)
                        FROM App\Entity\Assignment a
                        WHERE a.exam = :exam
                    )')
                    ->setParameter('exam', $exam);
                }

                return $qb;
            },
            'label' => 'Étudiant',
            'attr' => ['class' => 'form-select'],
            'placeholder' => 'Sélectionner un étudiant',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
    $resolver->setDefaults([
        'data_class' => Assignment::class,
        'teacher' => null, 
        'exam' => null, 
    ]);
    }
}
