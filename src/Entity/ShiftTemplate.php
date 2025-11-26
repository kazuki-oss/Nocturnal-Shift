<?php

namespace App\Entity;

use App\Repository\ShiftTemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShiftTemplateRepository::class)]
class ShiftTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tenant $tenant = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(type: Types::JSON)]
    private array $applicableDays = []; // [0, 1, 2, 3, 4, 5, 6] (Sun-Sat)

    #[ORM\Column(type: Types::INTEGER)]
    private int $requiredStaffCount = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function setTenant(?Tenant $tenant): static
    {
        $this->tenant = $tenant;
        return $this;
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

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getApplicableDays(): array
    {
        return $this->applicableDays;
    }

    public function setApplicableDays(array $applicableDays): static
    {
        $this->applicableDays = $applicableDays;
        return $this;
    }

    public function getRequiredStaffCount(): int
    {
        return $this->requiredStaffCount;
    }

    public function setRequiredStaffCount(int $requiredStaffCount): static
    {
        $this->requiredStaffCount = $requiredStaffCount;
        return $this;
    }
}
