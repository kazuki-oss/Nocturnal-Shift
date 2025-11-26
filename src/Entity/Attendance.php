<?php

namespace App\Entity;

use App\Repository\AttendanceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttendanceRepository::class)]
#[ORM\Table(name: 'attendances')]
class Attendance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $attendanceDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $clockInAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $clockOutAt = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $breakMinutes = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $locationData = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastModifiedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $lastModifiedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $modificationReason = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getAttendanceDate(): ?\DateTimeImmutable
    {
        return $this->attendanceDate;
    }

    public function setAttendanceDate(\DateTimeImmutable $attendanceDate): static
    {
        $this->attendanceDate = $attendanceDate;
        return $this;
    }

    public function getClockInAt(): ?\DateTimeImmutable
    {
        return $this->clockInAt;
    }

    public function setClockInAt(?\DateTimeImmutable $clockInAt): static
    {
        $this->clockInAt = $clockInAt;
        return $this;
    }

    public function getClockOutAt(): ?\DateTimeImmutable
    {
        return $this->clockOutAt;
    }

    public function setClockOutAt(?\DateTimeImmutable $clockOutAt): static
    {
        $this->clockOutAt = $clockOutAt;
        return $this;
    }

    public function getBreakMinutes(): ?int
    {
        return $this->breakMinutes;
    }

    public function setBreakMinutes(?int $breakMinutes): static
    {
        $this->breakMinutes = $breakMinutes;
        return $this;
    }

    public function getLocationData(): ?array
    {
        return $this->locationData;
    }

    public function setLocationData(?array $locationData): static
    {
        $this->locationData = $locationData;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getLastModifiedAt(): ?\DateTimeImmutable
    {
        return $this->lastModifiedAt;
    }

    public function setLastModifiedAt(?\DateTimeImmutable $lastModifiedAt): static
    {
        $this->lastModifiedAt = $lastModifiedAt;
        return $this;
    }

    public function getLastModifiedBy(): ?User
    {
        return $this->lastModifiedBy;
    }

    public function setLastModifiedBy(?User $lastModifiedBy): static
    {
        $this->lastModifiedBy = $lastModifiedBy;
        return $this;
    }

    public function getModificationReason(): ?string
    {
        return $this->modificationReason;
    }

    public function setModificationReason(?string $modificationReason): static
    {
        $this->modificationReason = $modificationReason;
        return $this;
    }
}
