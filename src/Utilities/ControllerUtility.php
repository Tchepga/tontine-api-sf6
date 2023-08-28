<?php

namespace App\Utilities;

use App\Entity\Deposit;
use App\Entity\Member;
use App\Entity\Tontine;
use App\Enum\ErrorCode;
use App\Enum\ReasonDeposit;
use App\Exception\CollectionException;
use App\Exception\TontineException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;

class ControllerUtility
{
    public const DEFAULT_PASSWORD = 'changeme';

    /**
     * sort array of objects by the given property
     * @param array $array
     * @param string $property
     * @param int $ascending
     * @return array array sorted by the given property
     * @throws Exception when the property is not found
     */
    public static function sortCollection(array $array, string $property, int $ascending = 1): array
    {
        foreach ($array as $value) {
            if (!property_exists($value, $property)) {
                throw new CollectionException('Property ' . $property . ' not found');
            }
        }

        usort($array, function (mixed $a, mixed $b) use ($property, $ascending): bool {
            $propA = call_user_func(array($a, "get" . ucfirst($property)));
            $propB = call_user_func(array($b, "get" . ucfirst($property)));
            $typeA = gettype($propA);
            $typeB = gettype($propB);
            if ($typeA != $typeB) {
                throw new CollectionException('Types are different');
            }

            if (($typeA != 'string' && $typeA != 'integer') || ($typeB != 'string' && $typeB != 'integer')) {
                throw new CollectionException('Type should be string or integer');
            }

            if ($propA === $propB) {
                return 0;
            }

            $result = ($propA < $propB) ? -1 : 1;
            return ($ascending === 1) ? $result : -$result;
        });

        return $array;
    }

    /**
     * build error message of controller
     * @param string $errorCode
     * @return ErrorCodeFormat
     * @throws TontineException
     */
    public static function buildError(string $errorCode): ErrorCodeFormat
    {
        return match ($errorCode) {
            ErrorCode::DUPLICATE_USER => new ErrorCodeFormat(ErrorCode::DUPLICATE_USER, "Duplicate user"),
            ErrorCode::BAD_REQUEST => new ErrorCodeFormat(ErrorCode::BAD_REQUEST, "Bad request"),
            ErrorCode::AMOUNT_REJECTED => new ErrorCodeFormat(
                ErrorCode::AMOUNT_REJECTED,
                "The amount is greater than the cash flow amount"
            ),
            ErrorCode::DELAY_EXPIRED => new ErrorCodeFormat(
                ErrorCode::DELAY_EXPIRED,
                "The max date for loan is crossed"
            ),
            ErrorCode::ALREADY_VOTED => new ErrorCodeFormat(ErrorCode::ALREADY_VOTED, "Member have already voted"),
            ErrorCode::UNAUTHORIZED_USER => new ErrorCodeFormat(
                ErrorCode::UNAUTHORIZED_USER,
                "Wrong password or username, please try again"
            ),
            default => throw new TontineException('Unknown error code'),
        };
    }

    /**
     * compute the dividends from all the given deposits
     * @param Collection<Deposit> $deposits
     * @return int
     */
    public static function computeDividends(Collection $deposits): int
    {
        $dividends = 0;
        $deposits->map(function (Deposit $deposit) use ($dividends) {
            if (
                $deposit->getReasons() &&
                ($deposit->getReasons() != ReasonDeposit::TONTINARD_DEPOSIT)
            ) {
                $dividends += $deposit->getAmount();
            }
        });

        return $dividends;
    }

    /**
     * @param EntityManagerInterface $em
     * @param int $idTontine
     * @param UserInterface|null $user
     * @return Tontine
     * @throws TontineException
     */
    public static function getTontine(EntityManagerInterface $em, int $idTontine, null | UserInterface $user): Tontine
    {
        $member = $em->getRepository(Member::class)->findOneBy(['phone' => $user->getUserIdentifier()]);
        if (!$member) {
            throw new TontineException("Login user not found");
        }

        $tontine = $em->getRepository(Tontine::class)->find($idTontine);
        if (!$tontine) {
            throw new TontineException("Tontine not found");
        }

        if (!$tontine->getTontinards()->contains($member)) {
            throw new TontineException("Login Member not in given tontinards");
        }
        return $tontine;
    }
}
