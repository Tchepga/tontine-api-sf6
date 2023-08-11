<?php

namespace App\Entity;

use App\Entity\UtilitiesEntity\Activable;
use App\Conts\GroupConst;
use App\Repository\TontineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TontineRepository::class)]
class Tontine extends Activable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(GroupConst::GROUP_TONTINE_READ)]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(GroupConst::GROUP_TONTINE_READ)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(GroupConst::GROUP_TONTINE_READ)]
    private ?string $legacy = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups(GroupConst::GROUP_TONTINE_READ)]
    private ?CashFlow $fund = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[Groups(GroupConst::GROUP_TONTINE_READ)]
    #[Assert\NotBlank]
    private ?TontineConfig $configuration = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToMany(targetEntity: Member::class, inversedBy: 'tontines')]
    #[Groups(GroupConst::GROUP_TONTINE_READ)]
    private Collection $tontinards;

    public function __construct()
    {
        $this->tontinards = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLegacy(): ?string
    {
        return $this->legacy;
    }

    public function setLegacy(string $legacy): self
    {
        $this->legacy = $legacy;

        return $this;
    }

    /**
     * @return Collection<int, Member>
     */

    public function getFund(): ?CashFlow
    {
        return $this->fund;
    }

    public function setFund(?CashFlow $fund): self
    {
        $this->fund = $fund;

        return $this;
    }

    public function getConfiguration(): ?TontineConfig
    {
        return $this->configuration;
    }

    public function setConfiguration(?TontineConfig $configuration): self
    {
        $this->configuration = $configuration;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, Member>
     */
    public function getTontinards(): Collection
    {
        return $this->tontinards;
    }

    public function addTontinard(Member $tontinard): self
    {
        if (!$this->tontinards->contains($tontinard)) {
            $this->tontinards->add($tontinard);
        }

        return $this;
    }

    public function removeTontinard(Member $tontinard): self
    {
        $this->tontinards->removeElement($tontinard);

        return $this;
    }
}
