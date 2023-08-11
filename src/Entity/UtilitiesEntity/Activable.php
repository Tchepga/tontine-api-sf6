<?php

namespace App\Entity\UtilitiesEntity;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Some entity cannot be deleted from the database. they will be simply disable
 */
#[MappedSuperclass]
abstract class Activable
{
    #[ORM\Column( nullable: true)]
    protected ?bool $isActivated = null;

    #[ORM\Column( nullable: true)]
    protected ?\DateTime $deleteDate = null;

    /**
     * @return bool|null
     */
    public function getIsActivated(): ?bool
    {
        return $this->isActivated;
    }

    /**
     * @param bool|null $isActivated
     */
    public function setIsActivated(?bool $isActivated): void
    {
        $this->isActivated = $isActivated;
    }


    /**
     * @return DateTime|null
     */
    public function getDeleteDate(): ?\DateTime
    {
        return $this->deleteDate;
    }

    /**
     * @param DateTime|null $deleteDate
     */
    public function setDeleteDate(?\DateTime $deleteDate): void
    {
        $this->deleteDate = $deleteDate;
    }


}