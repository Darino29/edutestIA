<?php

namespace App\Entity;

use App\Repository\ClasseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClasseRepository::class)]
class Classe
{
    public const LEVELS = [
        'BTS'         => 'BTS',
        'BUT'         => 'BUT / DUT',
        'Licence'     => 'Licence',
        'LicencePro'  => 'Licence Pro',
        'Master'      => 'Master',
        'Autre'       => 'Autre',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(length: 50)]
    private string $level = 'BTS';

    #[ORM\Column]
    private int $year = 1;

    #[ORM\Column(length: 9)]
    private string $academicYear = '';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'classe')]
    private Collection $students;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'classe_teachers')]
    private Collection $teachers;

    public function __construct()
    {
        $this->students    = new ArrayCollection();
        $this->teachers    = new ArrayCollection();
        $this->createdAt   = new \DateTimeImmutable();
        $this->academicYear = $this->buildAcademicYear();
    }

    private function buildAcademicYear(): string
    {
        $month = (int) date('m');
        $year  = (int) date('Y');
        $start = $month >= 9 ? $year : $year - 1;
        return $start . '-' . ($start + 1);
    }

    public function __toString(): string
    {
        return $this->getFullLabel();
    }

    public function getFullLabel(): string
    {
        return sprintf('%s %s – Année %d (%s)', $this->level, $this->name, $this->year, $this->academicYear);
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getLevel(): string { return $this->level; }
    public function setLevel(string $level): static { $this->level = $level; return $this; }

    public function getYear(): int { return $this->year; }
    public function setYear(int $year): static { $this->year = $year; return $this; }

    public function getAcademicYear(): string { return $this->academicYear; }
    public function setAcademicYear(string $academicYear): static { $this->academicYear = $academicYear; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, User> */
    public function getStudents(): Collection { return $this->students; }

    public function addStudent(User $student): static
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
            $student->setClasse($this);
        }
        return $this;
    }

    public function removeStudent(User $student): static
    {
        if ($this->students->removeElement($student)) {
            if ($student->getClasse() === $this) {
                $student->setClasse(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, User> */
    public function getTeachers(): Collection { return $this->teachers; }

    public function addTeacher(User $teacher): static
    {
        if (!$this->teachers->contains($teacher)) {
            $this->teachers->add($teacher);
        }
        return $this;
    }

    public function removeTeacher(User $teacher): static
    {
        $this->teachers->removeElement($teacher);
        return $this;
    }
}
