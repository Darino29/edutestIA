<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cet email.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $fullName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $studentNumber = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isApproved = false;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $apiToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\ManyToOne(targetEntity: Classe::class, inversedBy: 'students')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Classe $classe = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: Assignment::class, mappedBy: 'student', cascade: ['remove'])]
    private Collection $assignments;

    public function __construct()
    {
        $this->assignments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->fullName ?? $this->email ?? 'Utilisateur';
    }

    // 🧩 Identité & sécurité
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Aucune donnée sensible temporaire
    }

    // 🧑‍🎓 Informations personnelles
    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getStudentNumber(): ?string
    {
        return $this->studentNumber;
    }

    public function setStudentNumber(?string $studentNumber): static
    {
        $this->studentNumber = $studentNumber;
        return $this;
    }

    // 🟢 Validation & approbation
    public function isApproved(): bool
    {
        return $this->isApproved;
    }

    public function setIsApproved(bool $isApproved): static
    {
        $this->isApproved = $isApproved;
        return $this;
    }

    // 🕓 Création
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getApiToken(): ?string { return $this->apiToken; }
    public function setApiToken(?string $apiToken): static { $this->apiToken = $apiToken; return $this; }

    public function getProfilePicture(): ?string { return $this->profilePicture; }
    public function setProfilePicture(?string $profilePicture): static { $this->profilePicture = $profilePicture; return $this; }

    public function getClasse(): ?Classe { return $this->classe; }
    public function setClasse(?Classe $classe): static { $this->classe = $classe; return $this; }
    public function regenerateApiToken(): string
    {
        $this->apiToken = bin2hex(random_bytes(32));
        return $this->apiToken;
    }

    // 🎓 Helpers de rôles
    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles, true);
    }

    public function isTeacher(): bool
    {
        return in_array('ROLE_TEACHER', $this->roles, true);
    }

    public function isStudent(): bool
    {
        return in_array('ROLE_STUDENT', $this->roles, true);
    }

    // 📚 Relation avec les affectations
    /**
     * @return Collection<int, Assignment>
     */
    public function getAssignments(): Collection
    {
        return $this->assignments;
    }

    public function addAssignment(Assignment $assignment): static
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments->add($assignment);
            $assignment->setStudent($this);
        }
        return $this;
    }

    public function removeAssignment(Assignment $assignment): static
    {
        if ($this->assignments->removeElement($assignment)) {
            if ($assignment->getStudent() === $this) {
                $assignment->setStudent(null);
            }
        }
        return $this;
    }
}
