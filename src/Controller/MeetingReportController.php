<?php

namespace App\Controller;

use App\Conts\GroupConst;
use App\Entity\MeetingReport;
use App\Entity\Member;
use App\Entity\Sanction;
use App\Enum\StatusSanction;
use App\Exception\TontineException;
use App\Utilities\ControllerUtility;
use App\Utilities\HttpHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MeetingReportController extends AbstractController
{
    /**
     * get all the reports of the given tontine id
     * @param int $idTontine
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws TontineException
     */
    #[Route('/api/meeting/report/tontine/{idTontine}', name: 'app_meeting_report', methods: ['GET'])]
    public function getReportOfTontine(int $idTontine, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $tontine = ControllerUtility::getTontine($em, $idTontine, $this->getUser());

        $meetingReports = $em->getRepository(MeetingReport::class)->findBy(['tontine' => $tontine]);
        return $this->json(
            $meetingReports,
            Response::HTTP_OK,
            [],
            [
                'groups' => [GroupConst::GROUP_MEETING_REPORT_READ]
            ]
        );
    }

    /**
     * update a report of the given tontine id
     * @throws TontineException
     */
    #[Route(
        '/api/meeting/report/tontine/{idTontine}/{idMeetingReport}',
        name: 'app_meeting_report_update',
        methods: ['PATCH']
    )]
    public function updateReportOfTontine(
        Request                $request,
        //HtmlSanitizerInterface $htmlSanitizer,
        int                    $idTontine,
        int                    $idMeetingReport,
        EntityManagerInterface $em,
    ): JsonResponse {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $tontine = ControllerUtility::getTontine($em, $idTontine, $this->getUser());
        $meetingReport = $em->getRepository(MeetingReport::class)->find($idMeetingReport);
        if (!$meetingReport) {
            throw new TontineException(
                sprintf('Meeting report with id %d does not exist ', $idMeetingReport)
            );
        }
        if ($meetingReport->getTontine()->getId() !== $tontine->getId()) {
            throw new TontineException(
                sprintf('Tontine with id %d does not belong to tontine with id %d ', $idTontine, $tontine->getId())
            );
        }

        $body = json_decode($request->getContent(), true);
        if (!isset($body['content'])) {
            return $this->json('', Response::HTTP_BAD_REQUEST);
        }
        //TODO check how to sanitize the content without HtmlSanitizer
        //$safeContents = $htmlSanitizer->sanitize($body['content']);
        $meetingReport->setContent($body['content']);
        $meetingReport->setUpdateDate(new DateTime());
        $em->flush();

        return $this->json(
            $meetingReport,
            Response::HTTP_OK,
            [],
            [
                'groups' => [
                    GroupConst::GROUP_MEETING_REPORT_READ
                ]
            ]
        );
    }


    /**
     * create a new report from the given tontine id
     * @throws TontineException
     * @throws Exception
     */
    #[Route('/api/meeting/report/tontine/{idTontine}', name: 'app_meeting_report_create', methods: ['POST'])]
    public function createReportOfTontine(
        Request                $request,
        SerializerInterface    $serializer,
        int                    $idTontine,
        EntityManagerInterface $em,
        ValidatorInterface     $validator
    ): JsonResponse {

        $this->denyAccessUnlessGranted('ROLE_OFFICE');

        $tontine = ControllerUtility::getTontine($em, $idTontine, $this->getUser());

        $user = $this->getUser();
        $author = $em->getRepository(Member::class)->findOneBy(['phone' => $user->getUserIdentifier()]);


        $meetingReport = HttpHelper::getResource($request, $serializer, MeetingReport::class);
        $errors = $validator->validate($meetingReport);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $meetingReport->setTontine($tontine);
        $meetingReport->setCreationDate(new DateTime('NOW'));
        $meetingReport->setAuthor($author);
        $em->persist($meetingReport);
        $em->flush();

        return $this->json(
            $meetingReport,
            Response::HTTP_CREATED,
            [],
            [
                'groups' => [GroupConst::GROUP_MEETING_REPORT_READ]
            ]
        );
    }

    /**
     * create a new sanction
     * @param int $idTontine
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @return JsonResponse
     * @throws TontineException
     */
    #[Route(
        '/api/meeting/report/tontine/{idTontine}/sanction',
        name: 'app_meeting_sanction_create',
        methods: ['POST']
    )]
    public function createSanction(
        int                    $idTontine,
        Request                $request,
        SerializerInterface    $serializer,
        EntityManagerInterface $em,
        ValidatorInterface     $validator,
    ): JsonResponse {

        $this->denyAccessUnlessGranted('ROLE_OFFICE');

        // control to make sure the user is allowed to create a sanction in this tontine
        $tontine = ControllerUtility::getTontine($em, $idTontine, $this->getUser());

        $sanction = HttpHelper::getResource($request, $serializer, Sanction::class);
        $errors = $validator->validate($sanction);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $applyMember = $em->getRepository(Member::class)->findOneBy([
            'phone' => $sanction->getMember()->getUserIdentifier()
        ]);
        if (!$applyMember) {
            throw new TontineException("The member with id {$sanction->getMember()->getId()} does not exist");
        }

        if (!$tontine->getTontinards()->contains($applyMember)) {
            throw new TontineException(
                "The member with id {$applyMember->getId()} does not belong to tontine with id {$tontine->getId()}"
            );
        }

        $sanction->setStatus(StatusSanction::PENDING);
        $sanction->setStartDate(new DateTime('NOW'));
        $sanction->setTontine($tontine);
        $applyMember->addSanction($sanction);

        $em->persist($sanction);
        $em->flush();

        return $this->json(
            $sanction,
            Response::HTTP_CREATED,
            [],
            [
                'groups' => [GroupConst::GROUP_SANCTION_READ, GroupConst::GROUP_MEMBER_READ]
            ]
        );
    }

    /**
     * delete a sanction
     * @param int $idTontine
     * @param int $idSanction
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws TontineException
     */
    #[Route(
        '/api/meeting/report/tontine/{idTontine}/sanction/{idSanction}',
        name: 'app_meeting_sanction_delete',
        methods: ['DELETE']
    )]
    public function deleteSanction(
        int                    $idTontine,
        int                    $idSanction,
        EntityManagerInterface $em
    ): JsonResponse {

        $this->denyAccessUnlessGranted('ROLE_OFFICE');

        ControllerUtility::getTontine($em, $idTontine, $this->getUser());

        $sanction = $em->getRepository(Sanction::class)->findOneBy([
            'id' => $idSanction
        ]);

        if (!$sanction) {
            return $this->json("The sanction with id $idSanction does not exist", Response::HTTP_NOT_FOUND);
        }

        $sanction->setStatus(StatusSanction::REJECTED);
        $sanction->setEndDate(new DateTime('NOW'));
        $em->persist($sanction);
        $em->flush();

        return $this->json(
            '',
            Response::HTTP_NO_CONTENT,
            [],
            [
                'groups' => [GroupConst::GROUP_SANCTION_READ, GroupConst::GROUP_MEMBER_READ]
            ]
        );
    }

    /**
     * get all sanctions of a tontine
     * @param int $idTontine
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws TontineException
     */
    #[Route(
        '/api/meeting/report/tontine/{idTontine}/sanction',
        name: 'app_meeting_sanctions_get',
        methods: ['GET']
    )]
    public function getSanctions(
        int                    $idTontine,
        EntityManagerInterface $em
    ): JsonResponse {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $tontine = ControllerUtility::getTontine($em, $idTontine, $this->getUser());
        $sanctions = $em->getRepository(Sanction::class)->findBy([
            'tontine' => $tontine,
            'status' => [StatusSanction::PENDING, StatusSanction::EXECUTED]
        ]);

        return $this->json(
            $sanctions,
            Response::HTTP_OK,
            [],
            [
                'groups' => [GroupConst::GROUP_SANCTION_READ, GroupConst::GROUP_MEMBER_READ]
            ]
        );
    }
}
