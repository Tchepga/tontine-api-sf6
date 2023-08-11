<?php

namespace App\Entity;

use App\Repository\CashFlowRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Conts\GroupConst;

#[ORM\Entity(repositoryClass: CashFlowRepository::class)]
class CashFlow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(GroupConst::GROUP_CASHFLOW_READ)]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(GroupConst::GROUP_CASHFLOW_READ)]
    private ?string $currency = null;

    #[ORM\Column]
    #[Groups(GroupConst::GROUP_CASHFLOW_READ)]
    private ?int $balance = null;

    #[ORM\OneToMany(mappedBy: 'cashFlow', targetEntity: Deposit::class)]
    private Collection $deposits;

    #[ORM\Column]
    #[Groups(GroupConst::GROUP_CASHFLOW_READ)]
    private ?int $dividendes = null;

    #[ORM\OneToMany(mappedBy: 'cashFlow', targetEntity: Loan::class)]
    private Collection $loans;

    public function __construct()
    {
        $this->deposits = new ArrayCollection();
        $this->loans = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getBalance(): ?int
    {
        return $this->balance;
    }

    public function setBalance(int $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * @return Collection<int, Deposit>
     */
    public function getDeposits(): Collection
    {
        return $this->deposits;
    }

    public function addDeposit(Deposit $deposit): self
    {
        if (!$this->deposits->contains($deposit)) {
            $this->deposits->add($deposit);
            $deposit->setCashFlow($this);
        }

        return $this;
    }

    public function removeDeposit(Deposit $deposit): self
    {
        if ($this->deposits->removeElement($deposit)) {
            // set the owning side to null (unless already changed)
            if ($deposit->getCashFlow() === $this) {
                $deposit->setCashFlow(null);
            }
        }

        return $this;
    }

    public function getDividendes(): ?int
    {
        return $this->dividendes;
    }

    public function setDividendes(int $dividendes): self
    {
        $this->dividendes = $dividendes;

        return $this;
    }

    /**
     * @return Collection<int, Loan>
     */
    public function getLoans(): Collection
    {
        return $this->loans;
    }

    public function addLoan(Loan $loan): self
    {
        if (!$this->loans->contains($loan)) {
            $this->loans->add($loan);
            $loan->setCashFlow($this);
        }

        return $this;
    }

    public function removeLoan(Loan $loan): self
    {
        if ($this->loans->removeElement($loan)) {
            // set the owning side to null (unless already changed)
            if ($loan->getCashFlow() === $this) {
                $loan->setCashFlow(null);
            }
        }

        return $this;
    }
}
