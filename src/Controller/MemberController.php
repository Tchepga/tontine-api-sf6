<?php

namespace App\Controller;

use App\Conts\GroupConst;
use App\Entity\Member;
use App\Enum\ErrorCode;
use App\Exception\TontineException;
use App\Utilities\ControllerUtility;
use App\Utilities\HttpHelper;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

const DEFAULT_PATH = "/api/member";
/**
 * Note: Do not put global path
 * Class MemberController
 */
class MemberController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route(DEFAULT_PATH, name: 'app_member_create', methods: ['POST'])]
    public function create(
        Request                     $request,
        SerializerInterface         $serializer,
        EntityManagerInterface      $em,
        ValidatorInterface          $validator,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $member = HttpHelper::getResource($request, $serializer, Member::class);
        $noCheck = $request->query->get('noCheck') === 'true';
        //format username
        $member->setUsername(strtolower($member->getFirstname() .
            $member->getLastname()));

        // set basic password for testing
        // hash the password (based on the security.yaml config for the $user class)
        $hashedPassword = $passwordHasher->hashPassword(
            $member,
            $member->getPassword() ?? "changeme"
        );
        $member->setPassword($hashedPassword);

        $errors = $validator->validate($member);
        if (count($errors) > 0) {
            return $this->json($errors->get(0), Response::HTTP_BAD_REQUEST);
        }

        $isAlreadyMember = $em->getRepository(Member::class)->findOneBy(['phone' => $member->getPhone()]) ||
            $em->getRepository(Member::class)->findOneBy(['username' => $member->getUsername()]);
        if ($isAlreadyMember && !$noCheck) {
            return $this->json(ControllerUtility::buildError(ErrorCode::DUPLICATE_USER), Response::HTTP_BAD_REQUEST);
        } else {
            $em->persist($member);
            $em->flush();
            return $this->json(
                $member,
                Response::HTTP_CREATED,
                [],
                [
                    'groups' => [
                        GroupConst::GROUP_MEMBER_READ
                    ]
                ]
            );
        }
    }

    /**
     * get member information of authenticated user
     * @return JsonResponse
     */
    #[Route(
        path: '/api/member/profile',
        name: 'app_member_get_profile',
        methods: ['GET']
    )]
    public function getProfile(): JsonResponse
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $member = $this->getUser();
        if (!$member) {
            return $this->json('', Response::HTTP_NOT_FOUND);
        }
        return $this->json(
            $member,
            Response::HTTP_OK,
            [],
            [
                'groups' => [
                    GroupConst::GROUP_MEMBER_READ,
                    GroupConst::GROUP_LOAN_READ,
                    GroupConst::GROUP_DEPOSIT_READ
                ]
            ]
        );
    }

    /**
     * get all members
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws Exception
     */
    #[Route(DEFAULT_PATH, name: 'app_member_get_all', methods: ['GET'])]
    public function getAll(EntityManagerInterface $em): JsonResponse
    {
        $members = $em->getRepository(Member::class)->findAll();
        return $this->json(
            ControllerUtility::sortCollection($members, 'id'),
            Response::HTTP_OK,
            [],
            [
                'groups' => [
                    GroupConst::GROUP_MEMBER_READ
                ]
            ]
        );
    }

    /**
     * login a member
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @param JWTTokenManagerInterface $jwtManager
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request                     $request,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface    $jwtManager,
        EntityManagerInterface      $em
    ): JsonResponse {
        // Get the username and password from the request
        $body = json_decode($request->getContent(), true);
        $username = $body['username'];
        $password = $body['password'];
        $member = $em->getRepository(Member::class)->findOneBy(['username' => $username]);

        if (!$member || !$passwordHasher->isPasswordValid($member, $password)) {
            return $this->json(
                ControllerUtility::buildError(ErrorCode::UNAUTHORIZED_USER),
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Generate a JWT token
        $token = $jwtManager->create($member);

        // Return the token as a response
        return $this->json(['token' => $token], Response::HTTP_OK);
    }

    /**
     * logout a member
     * @param Security $security
     * @return JsonResponse
     */
    #[Route('/api/member/logout', name: 'api_logout', methods: ['GET'])]
    public function logout(
        Security $security
    ) {

        //        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        //
        //        // logout the user in on the current firewall
        //        $security->logout(false);
        //
        //        return $this->json('', Response::HTTP_OK);
    }

    /**
     * delete a member
     * @param int $memberId
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/api/member/{memberId}', name: 'app_member_delete_member', methods: ['DELETE'])]
    public function deleteMember(int $memberId, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PRESIDENT');

        $memberRepository = $em->getRepository(Member::class);
        $member = $memberRepository->find($memberId);
        if (!$member) {
            return $this->json(
                'member not found',
                Response::HTTP_NOT_FOUND
            );
        }

        $em->remove($member);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * update a member
     * @param request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @return JsonResponse
     * @throws Exception
     */
    #[Route('/api/member', name: 'app_member_update_member', methods: ['PUT'])]
    public function updateMemberByPresi(
        Request                $request,
        SerializerInterface    $serializer,
        EntityManagerInterface $em,
        ValidatorInterface     $validator
    ): JsonResponse {

        $this->denyAccessUnlessGranted('ROLE_PRESIDENT');

        $bodyMember = HttpHelper::getResource($request, $serializer, Member::class);
        $member = $em->getRepository(Member::class)->findOneBy([
            'username' => $bodyMember->getUsername(),
            'phone' => $bodyMember->getPhone(),
            //'id' => $bodyMember->getId()
        ]);

        if (!$member) {
            return $this->json(
                'member not found',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->updateMember($em, $validator, $member, $bodyMember);
    }

    /**
     * update the profile of the authenticated member
     * @throws Exception
     */
    #[Route('/api/member/profile', name: 'app_member_update_profile', methods: ['PATCH'])]
    public function updateProfile(
        Request                $request,
        EntityManagerInterface $em,
        ValidatorInterface     $validator
    ): JsonResponse {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $loginMember = $this->getUser();
        $member = $em->getRepository(Member::class)->findOneBy([
            'phone' => $loginMember->getUserIdentifier()
        ]);

        if ($member) {
            $content = json_decode($request->getContent(), true);
            if (gettype($content) !== 'array') {
                return $this->json(
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (array_key_exists('email', $content)) {
                $member->setEmail($content['email']);
            }
            if (array_key_exists('firstName', $content)) {
                $member->setFirstName($content['firstName']);
            }
            if (array_key_exists('lastName', $content)) {
                $member->setLastName($content['lastName']);
            }

            $validations = $validator->validate($member);
            if ($validations->count() > 0) {
                return $this->json($validations, Response::HTTP_BAD_REQUEST);
            }

            $em->flush();
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * utility to update a member
     */
    private function updateMember(
        EntityManagerInterface $em,
        ValidatorInterface     $validator,
        Member                 $member,
        Member                 $bodyMember
    ): JsonResponse {

        //TODO: the email and phone have a specific process for changing
        $member->setPhone($bodyMember->getPhone());
        $member->setEmail($bodyMember->getEmail());
        $member->setFirstName($bodyMember->getFirstName());
        $member->setLastName($bodyMember->getLastName());
        $member->setRoles($bodyMember->getRoles());
        $member->setCountry($bodyMember->getCountry());

        $violations = $validator->validate($member);
        if ($violations->count() > 0) {
            return $this->json(
                $violations,
                Response::HTTP_BAD_REQUEST
            );
        } else {
            $em->flush();
        }

        return $this->json($member, Response::HTTP_ACCEPTED, [], ['groups' => [GroupConst::GROUP_MEMBER_READ]]);
    }

    /**
     * reset the password of the authenticated member
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws TontineException
     */
    #[Route('/api/member/password', name: 'app_member_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request                     $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface      $em
    ): JsonResponse {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $user = $this->getUser();
        if (!$user) {
            throw new TontineException("Session expired");
        }

        $member = $em->getRepository(Member::class)->findOneBy(['phone' => $user->getUserIdentifier()]);
        if (!$member) {
            throw new TontineException('User not found');
        }

        $content = json_decode($request->getContent(), true);
        $oldPassword = $content['oldPassword'];
        $newPassword = $content['newPassword'];
        if (!$oldPassword || !$newPassword) {
            return $this->json(
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($passwordHasher->isPasswordValid($member, $oldPassword)) {
            $member->setPassword($passwordHasher->hashPassword($member, $newPassword));
            $em->flush();

            return $this->json('', Response::HTTP_ACCEPTED);
        } else {
            return $this->json(
                "The old password is incorrect",
                Response::HTTP_UNAUTHORIZED
            );
        }
    }
}
