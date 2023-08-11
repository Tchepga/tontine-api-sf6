<?php

namespace App\Entity;

use App\Repository\LoanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Conts\GroupConst;

#[ORM\Entity(repositoryClass: LoanRepository::class)]
class Loan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(GroupConst::GROUP_LOAN_READ)]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(GroupConst::GROUP_LOAN_READ)]
    private ?Member $author = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups(GroupConst::GROUP_LOAN_READ)]
    private ?int $amount = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(GroupConst::GROUP_LOAN_READ)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(length: 255)]
    #[Groups(GroupConst::GROUP_LOAN_READ)]
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    #[Groups(GroupConst::GROUP_LOAN_READ)]
    private ?string $currency = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    #[Groups(GroupConst::GROUP_LOAN_READ)]
    private ?\DateTimeInterface $redemptionDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(GroupConst::GROUP_LOAN_READ)]
    private ?\DateTimeInterface $updateDate = null;

    #[ORM\Column(nullable: true)]
    #[Groups(GroupConst::GROUP_LOAN_READ)]
    private array $voters = [];

    #[ORM\ManyToOne(inversedBy: 'loans')]
    private ?CashFlow $cashFlow = null;

    #[ORM\Column(nullable: true)]
    #[Groups(GroupConst::GROUP_LOAN_READ)]
    private ?float $rate = null;

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

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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

    public function getRedemptionDate(): ?\DateTimeInterface
    {
        return $this->redemptionDate;
    }

    public function setRedemptionDate(\DateTimeInterface $redemptionDate): self
    {
        $this->redemptionDate = $redemptionDate;

        return $this;
    }

    public function getUpdateDate(): ?\DateTimeInterface
    {
        return $this->updateDate;
    }

    public function setUpdateDate(?\DateTimeInterface $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getVoters(): array
    {
        return $this->voters;
    }

    public function setVoters(?array $voters): self
    {
        $this->voters = $voters;

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

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(float $rate): self
    {
        $this->rate = $rate;

        return $this;
    }
}
