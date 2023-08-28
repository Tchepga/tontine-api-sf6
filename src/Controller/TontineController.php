<?php

namespace App\Controller;

use App\Conts\GroupConst;
use App\Entity\Member;
use App\Entity\Tontine;
use App\Repository\TontineRepository;
use App\Utilities\ControllerUtility;
use App\Utilities\HttpHelper;
use Doctrine\ORM\EntityManagerInterface;
use App\Exception\TontineException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TontineController extends AbstractController
{
    public const TONTINE_NOT_FOUND = "Tontine not found";
    /**
     * @throws TontineException
     */
    private function validatePresidentData(Member $member, ValidatorInterface $validator): void
    {
        if (!in_array('ROLE_PRESIDENT', $member->getRoles())) {
            throw new TontineException("Member should be president");
        }

        $errors = $validator->validate($member);
        if (empty($errors) > 0) {
            throw new TontineException($errors[0]->getMessage());
        }
    }

    /**
     * create a new tontine. Requires a member, configuration and a cash flow.
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     * @param UserPasswordHasherInterface $passwordHasher
     * @return JsonResponse
     * @throws TontineException
     */
    #[Route('/api/tontine', name: 'app_tontine_create', methods: ['POST'])]
    public function create(
        Request                $request,
        SerializerInterface    $serializer,
        EntityManagerInterface $entityManager,
        ValidatorInterface     $validator,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $tontine = HttpHelper::getResource($request, $serializer, Tontine::class);

        $errors = $validator->validate($tontine);
        if (count($errors) > 0) {
            return $this->json('', Response::HTTP_BAD_REQUEST);
        }

        // configuration
        $config = $tontine->getConfiguration();
        if (!$config) {
            throw new TontineException("The configuration of the tontine cannot be empty!");
        }
        $cashFlow = $tontine->getFund();
        if (!$cashFlow) {
            throw new TontineException("The cash flow of the tontine cannot be empty!");
        }

        $tontine->setCreatedAt(new \DateTimeImmutable());
        $tontine->setConfiguration($config);
        $tontine->setFund($cashFlow);
        $tontine->setIsActivated(true);
        $entityManager->persist($cashFlow);

        //Hack to add president member of the tontine entity
        $tontinards = $tontine->getTontinards();
        $nbrTontinards = count($tontinards);
        if ($nbrTontinards != 1) {
            throw new TontineException(
                "Creation of a new tontine requires only one tontinard member"
            );
        } else {
            $president = $tontinards->first();
            $this->validatePresidentData($president, $validator);
            $president->setPassword(
                $passwordHasher->hashPassword(
                    $president,
                    $president->getPassword() ?? ControllerUtility::DEFAULT_PASSWORD
                )
            );
            $entityManager->persist($president);
            $tontine->removeTontinard($president);
        }

        $entityManager->persist($tontine);

        if ($tontine->getId() && $president->getId()) {
            $president->addTontine($tontine);
        }
        $entityManager->flush();

        return $this->json(
            null,
            Response::HTTP_CREATED,
            [],
            [
                'groups' => [
                    GroupConst::GROUP_TONTINE_READ,
                    GroupConst::GROUP_CASHFLOW_READ
                ]
            ]
        );
    }

    /**
     * add a new member to the tontine
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     * @param UserPasswordHasherInterface $passwordHasher
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/api/tontine/{id}/member', methods: ['PATCH'])]
    public function addNewTontinard(
        Request                     $request,
        SerializerInterface         $serializer,
        EntityManagerInterface      $entityManager,
        ValidatorInterface          $validator,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {

        $this->denyAccessUnlessGranted('ROLE_PRESIDENT');

        $tontineId = $request->get('id');
        if (!$tontineId) {
            throw new TontineException("The id of the tontine cannot be empty!");
        }
        $member = HttpHelper::getResource($request, $serializer, Member::class);
        if (!$member->getUsername()) {
            //format username
            $member->setUsername(strtolower($member->getFirstname() .
                $member->getLastname()));
        }

        return $this->addNewMember($tontineId, $member, $entityManager, $validator);
    }

    /**
     * add a new member to the tontine (member)
     * @param int $tontineId
     * @param Member $member
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     * @return JsonResponse
     * @throws TontineException
     */
    private function addNewMember(
        int                    $tontineId,
        Member                 $member,
        EntityManagerInterface $entityManager,
        ValidatorInterface     $validator,

    ): JsonResponse {
        if (empty($tontineId)) {
            throw new TontineException("The id of the tontine cannot be empty!");
        }

        $errors = $validator->validate($member);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $memberRepository = $entityManager->getRepository(Member::class);
        $isMemberExists = $memberRepository->findOneBy(['phone' => $member->getPhone()]) ||
            $memberRepository->findOneBy(['username' => $member->getUsername()]);
        if (!$isMemberExists) {
            return $this->json(
                'Member not found. First, create a new member and after add him to the tontine.',
                Response::HTTP_BAD_REQUEST
            );
        }

        $mbRepository = $entityManager->getRepository(Member::class);
        $freshMember = $mbRepository->findOneBy([
            'username' => $member->getUsername(),
            'phone' => $member->getPhone(),
        ]);

        $tontineRepository = $entityManager->getRepository(Tontine::class);
        $tontine = $tontineRepository->find($tontineId);

        if (!$tontine) {
            return $this->json(self::TONTINE_NOT_FOUND, Response::HTTP_NOT_FOUND, []);
        }

        if ($freshMember) {
            $tontinard = $tontine->getTontinards();

            // check if there is a use with the same roles in the tontinard list
            $sameRole = $tontinard->exists(function ($key, $value) use ($freshMember) {

                $rolesNewMember = $freshMember->getRoles();
                $userRolesIndex = array_search('ROLE_USER', $rolesNewMember);
                if ($userRolesIndex) {
                    $rolesNewMember = array_slice($rolesNewMember, $userRolesIndex, 1);
                }

                return in_array($rolesNewMember, $value->getRoles());
            });
            if ($sameRole) {
                return $this->json(
                    'You are already a member in the tontine with same role (staff role).',
                    Response::HTTP_BAD_REQUEST
                );
            }
            // is not possible to add to staff list without be a in tontinards list
            if (!$tontine->getTontinards()->contains($freshMember)) {
                $this->addToToninardList($freshMember, $tontine, $entityManager);
            }

            return $this->json(
                $tontine,
                Response::HTTP_ACCEPTED,
                [],
                ['groups' => [GroupConst::GROUP_TONTINE_READ, GroupConst::GROUP_MEMBER_READ]]
            );
        } else {
            return $this->json('Member not found', Response::HTTP_NOT_FOUND, []);
        }
    }

    /**
     * basic add a new member to the tontine (member)
     * @param Member $member
     * @param Tontine $tontine
     * @param EntityManagerInterface $em
     */
    private function addToToninardList(
        Member                 $member,
        Tontine                $tontine,
        EntityManagerInterface $em
    ): void {

        $tontine->addTontinard($member);
        $member->addTontine($tontine);

        $em->persist($tontine);
        $em->flush();
    }

    #[Route('/api/tontine', methods: ['GET'])]
    public function getAll(TontineRepository $tontineRepository): JsonResponse
    {

        $tontines = $tontineRepository->findBy(['isActivated' => true]);
        return $this->json(
            $tontines,
            Response::HTTP_OK,
            [],
            [
                'groups' => [
                    GroupConst::GROUP_TONTINE_READ,
                    GroupConst::GROUP_MEMBER_READ,
                    GroupConst::GROUP_CASHFLOW_READ,
                    GroupConst::GROUP_LOAN_READ
                ]
            ]
        );
    }

    /**
     * get tontine by id
     * @param TontineRepository $tontineRepository
     * @param string $id tontine id
     * @return JsonResponse
     */
    #[Route('/api/tontine/{id}', methods: ['GET'])]
    public function getOne(TontineRepository $tontineRepository, string $id): JsonResponse
    {
        $tontines = $tontineRepository->findOneBy(['id' => $id, 'isActivated' => true]);
        if (!$tontines) {
            return $this->json(self::TONTINE_NOT_FOUND, Response::HTTP_NOT_FOUND, []);
        }
        return $this->json(
            $tontines,
            Response::HTTP_OK,
            [],
            ['groups' => [
                GroupConst::GROUP_TONTINE_READ,
                GroupConst::GROUP_MEMBER_READ,
                GroupConst::GROUP_CASHFLOW_READ
            ]]
        );
    }

    /**
     * delete a tontine
     * @param int $id tontine id
     */
    #[Route('/api/tontine/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $tontineRepository = $entityManager->getRepository(Tontine::class);
        $tontine = $tontineRepository->findOneBy(['isActivated' => true, 'id' => $id]);

        if (!$tontine) {
            return $this->json(self::TONTINE_NOT_FOUND, Response::HTTP_NOT_FOUND, []);
        }

        $tontine->setIsActivated(false);
        $tontine->setDeleteDate(new \DateTime());

        $entityManager->persist($tontine);

        //TODO should be removed just for testing
        $entityManager->remove($tontine);

        $entityManager->flush();

        return $this->json(
            '',
            Response::HTTP_NO_CONTENT,
            []
        );
    }

    /**
     * get all tontines of member from user token
     *
     */
    #[Route('/api/tontinePerUser', methods: ['GET'])]
    public function getAllTontinesByUser(EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $member = $this->getUser();
        if (!$member) {
            return $this->json('User not found', Response::HTTP_BAD_REQUEST, []);
        }

        $tontineRepository = $em->getRepository(Tontine::class);
        $tontines = $tontineRepository->findBy(['isActivated' => true]);
        $tontines = array_filter($tontines, function (Tontine $tontine) use ($member) {
            $tontinards = $tontine->getTontinards();
            return !$tontinards->isEmpty() && $tontinards->contains($member);
        });

        return $this->json(
            $tontines,
            Response::HTTP_OK,
            [],
            ['groups' => [
                GroupConst::GROUP_TONTINE_READ,
                GroupConst::GROUP_MEMBER_READ,
                GroupConst::GROUP_CASHFLOW_READ
            ]]
        );
    }
}
