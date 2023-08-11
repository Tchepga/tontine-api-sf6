<?php

namespace App\Entity;

use App\Entity\UtilitiesEntity\Activable;
use App\Repository\DepositRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Conts\GroupConst;

#[ORM\Entity(repositoryClass: DepositRepository::class)]
class Deposit extends Activable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(GroupConst::GROUP_DEPOSIT_READ)]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'deposits')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups(GroupConst::GROUP_DEPOSIT_READ)]
    private ?Member $author = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(GroupConst::GROUP_DEPOSIT_READ)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups(GroupConst::GROUP_DEPOSIT_READ)]
    private ?int $amount = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(GroupConst::GROUP_DEPOSIT_READ)]
    private ?string $currency = null;

    #[ORM\Column(length: 255)]
    #[Groups(GroupConst::GROUP_DEPOSIT_READ)]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(GroupConst::GROUP_DEPOSIT_READ)]
    private ?string $reasons = null;

    #[ORM\ManyToOne(inversedBy: 'deposits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CashFlow $cashFlow = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?Member
    {
        return $this->author;
    }

    public function setAuthor(?Member $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeInterface $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getReasons(): ?string
    {
        return $this->reasons;
    }

    public function setReasons(?string $reasons): self
    {
        $this->reasons = $reasons;

        return $this;
    }

    public function getCashFlow(): ?CashFlow
    {
        return $this->cashFlow;
    }

    public function setCashFlow(?CashFlow $cashFlow): self
    {
        $this->cashFlow = $cashFlow;

        return $this;
    }
}
