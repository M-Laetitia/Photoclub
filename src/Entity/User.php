<?php

namespace App\Entity;

use App\Entity\Contact;
use App\Entity\Photo;
use App\Entity\EventParticipation;
use Cocur\Slugify\Slugify;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = ["ROLE_USER"];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 50)]
    private ?string $pseudo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $avatar = null;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\OneToMany(targetEntity: Contact::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $contacts;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $registration_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $last_login_date = null;

    #[ORM\Column(nullable: true)]
    private ?array $author_infos = null;

    #[ORM\Column(nullable: true)]
    private ?int $is_published = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $last_profil_edit_time = null;

    /**
     * @var Collection<int, Photo>
     */
    #[ORM\OneToMany(targetEntity: Photo::class, mappedBy: 'user')]
    private Collection $photos;

    /**
     * @var Collection<int, EventParticipation>
     */
    #[ORM\OneToMany(targetEntity: EventParticipation::class, mappedBy: 'user')]
    private Collection $eventParticipations;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->photos = new ArrayCollection();
        $this->eventParticipations = new ArrayCollection();
    }

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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getSimpleRoles(): array
    {
        // fonction utiliser pour filtrer les éléments du tableau. Elle prend deux arguments : le tableau à filtrer ($this->getRoles() dans ce cas) et une fonction de rappel qui définit la condition de filtrage.
        $filteredRoles = array_filter($this->getRoles(), function ($roles) {
            // retourne true pour conserver un rôle et false pour l'exclure. Dans ce cas, on exclut le rôle "ROLE_USER".
            return $roles !== 'ROLE_USER';
        });

        // applique une fonction donnée à chaque élément d'un tableau et retourne un nouveau tableau avec les résultats.
        $simpleRoles = array_map(function ($roles) {

            // utilisée pour formater chaque rôle restant. Elle convertit d'abord le rôle en minuscules (strtolower), puis retire le préfixe "ROLE_" et remplace les underscores par des espaces.
            
            return strtolower(str_replace(['ROLE_', '_'], ['', ' '], $roles));
        }, $filteredRoles);

        return $simpleRoles;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function generateSlug(): string
    {
        $slugify = new Slugify();
        return $slugify->slugify($this->getUsername());
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Contact $contact): static
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
            $contact->setUser($this);
        }

        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        if ($this->contacts->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getUser() === $this) {
                $contact->setUser(null);
            }
        }

        return $this;
    }

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registration_date;
    }

    public function setRegistrationDate(?\DateTimeInterface $registration_date): static
    {
        $this->registration_date = $registration_date;

        return $this;
    }

    public function getLastLoginDate(): ?\DateTimeInterface
    {
        return $this->last_login_date;
    }

    public function setLastLoginDate(?\DateTimeInterface $last_login_date): static
    {
        $this->last_login_date = $last_login_date;

        return $this;
    }

    public function getAuthorInfos(): ?array
    {
        return $this->author_infos;
    }

    public function setAuthorInfos(?array $author_infos): static
    {
        $this->author_infos = $author_infos;

        return $this;
    }

    public function getIsPublished(): ?int
    {
        return $this->is_published;
    }

    public function setIsPublished(?int $is_published): static
    {
        $this->is_published = $is_published;

        return $this;
    }

    public function getLastProfilEditTime(): ?\DateTimeInterface
    {
        return $this->last_profil_edit_time;
    }

    public function setLastProfilEditTime(?\DateTimeInterface $last_profil_edit_time): static
    {
        $this->last_profil_edit_time = $last_profil_edit_time;

        return $this;
    }

    /**
     * @return Collection<int, Photo>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setUser($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): static
    {
        if ($this->photos->removeElement($photo)) {
            // set the owning side to null (unless already changed)
            if ($photo->getUser() === $this) {
                $photo->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EventParticipation>
     */
    public function getEventParticipations(): Collection
    {
        return $this->eventParticipations;
    }

    public function addEventParticipation(EventParticipation $eventParticipation): static
    {
        if (!$this->eventParticipations->contains($eventParticipation)) {
            $this->eventParticipations->add($eventParticipation);
            $eventParticipation->setUser($this);
        }

        return $this;
    }

    public function removeEventParticipation(EventParticipation $eventParticipation): static
    {
        if ($this->eventParticipations->removeElement($eventParticipation)) {
            // set the owning side to null (unless already changed)
            if ($eventParticipation->getUser() === $this) {
                $eventParticipation->setUser(null);
            }
        }

        return $this;
    }
}
