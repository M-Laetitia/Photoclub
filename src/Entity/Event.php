<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $detail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $quote = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $start_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $end_time = null;

    #[ORM\Column]
    private ?int $nb_rooms = null;

    #[ORM\Column(length: 30)]
    private ?string $type = null;

    #[ORM\Column(length: 30)]
    private ?string $status = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $access = null;

    /**
     * @var Collection<int, Photo>
     */
    #[ORM\OneToMany(targetEntity: Photo::class, mappedBy: 'event')]
    private Collection $photos;

    /**
     * @var Collection<int, EventCategory>
     */
    #[ORM\ManyToMany(targetEntity: EventCategory::class, mappedBy: 'events')]
    private Collection $eventCategories;

    /**
     * @var Collection<int, EventParticipation>
     */
    #[ORM\OneToMany(targetEntity: EventParticipation::class, mappedBy: 'event', orphanRemoval: true)]
    private Collection $eventParicipations;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->eventCategories = new ArrayCollection();
        $this->eventParicipations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function setDetail(?string $detail): static
    {
        $this->detail = $detail;

        return $this;
    }

    public function getQuote(): ?string
    {
        return $this->quote;
    }

    public function setQuote(?string $quote): static
    {
        $this->quote = $quote;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStartDate(\DateTimeInterface $start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->end_time;
    }

    public function setEndTime(\DateTimeInterface $end_time): static
    {
        $this->end_time = $end_time;

        return $this;
    }

    public function getNbRooms(): ?int
    {
        return $this->nb_rooms;
    }

    public function setNbRooms(int $nb_rooms): static
    {
        $this->nb_rooms = $nb_rooms;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAccess(): ?string
    {
        return $this->access;
    }

    public function setAccess(?string $access): static
    {
        $this->access = $access;

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
            $photo->setEvent($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): static
    {
        if ($this->photos->removeElement($photo)) {
            // set the owning side to null (unless already changed)
            if ($photo->getEvent() === $this) {
                $photo->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EventCategory>
     */
    public function getEventCategories(): Collection
    {
        return $this->eventCategories;
    }

    public function addEventCategory(EventCategory $eventCategory): static
    {
        if (!$this->eventCategories->contains($eventCategory)) {
            $this->eventCategories->add($eventCategory);
            $eventCategory->addEvent($this);
        }

        return $this;
    }

    public function removeEventCategory(EventCategory $eventCategory): static
    {
        if ($this->eventCategories->removeElement($eventCategory)) {
            $eventCategory->removeEvent($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, EventParticipation>
     */
    public function getEventParicipations(): Collection
    {
        return $this->eventParicipations;
    }

    public function addEventParicipation(EventParticipation $eventParicipation): static
    {
        if (!$this->eventParicipations->contains($eventParicipation)) {
            $this->eventParicipations->add($eventParicipation);
            $eventParicipation->setEvent($this);
        }

        return $this;
    }

    public function removeEventParicipation(EventParticipation $eventParicipation): static
    {
        if ($this->eventParicipations->removeElement($eventParicipation)) {
            // set the owning side to null (unless already changed)
            if ($eventParicipation->getEvent() === $this) {
                $eventParicipation->setEvent(null);
            }
        }

        return $this;
    }
}
