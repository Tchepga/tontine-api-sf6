<?php

namespace App\Entity;

use App\Repository\TontineConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Conts\GroupConst;

#[ORM\Entity(repositoryClass: TontineConfigRepository::class)]
class TontineConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(GroupConst::GROUP_TONTINE_READ)]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(GroupConst::GROUP_TONTINE_READ)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Groups(GroupConst::GROUP_TONTINE_READ)]
    private ?string $interval = null;

    #[ORM\Column(length: 255)]
    #[Groups(GroupConst::GROUP_TONTINE_READ)]
    private ?string $typeTontine = null;

    #[ORM\Column]
    #[Groups(GroupConst::GROUP_TONTINE_READ)]
    private ?int $personPerMovement = null;

    #[ORM\Column(nullable: true)]
    #[Groups(GroupConst::GROUP_TONTINE_READ)]
    private ?int $maxDurationLoan = null;

    #[ORM\Column(nullable: true)]
    #[Groups(GroupConst::GROUP_TONTINE_READ)]
    private ?float $defaultLoanRate = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getInterval(): ?string
    {
        return $this->interval;
    }

    public function setInterval(string $interval): self
    {
        $this->interval = $interval;

        return $this;
    }

    public function getTypeTontine(): ?string
    {
        return $this->typeTontine;
    }

    public function setTypeTontine(string $typeTontine): self
    {
        $this->typeTontine = $typeTontine;

        return $this;
    }

    public function getPersonPerMovement(): ?int
    {
        return $this->personPerMovement;
    }

    public function setPersonPerMovement(int $personPerMovement): self
    {
        $this->personPerMovement = $personPerMovement;

        return $this;
    }

    public function getMaxDurationLoan(): ?int
    {
        return $this->maxDurationLoan;
    }

    public function setMaxDurationLoan(?int $maxDurationLoan): self
    {
        $this->maxDurationLoan = $maxDurationLoan;

        return $this;
    }

    public function getDefaultLoanRate(): ?float
    {
        return $this->defaultLoanRate;
    }

    public function setDefaultLoanRate(?float $defaultLoanRate): self
    {
        $this->defaultLoanRate = $defaultLoanRate;

        return $this;
    }
}
