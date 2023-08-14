<?php

namespace App\Controller;

use App\Conts\GroupConst;
use App\Entity\Event;
use App\Entity\Member;
use App\Enum\TypeEvent;
use App\Exception\TontineException;
use App\Utilities\ControllerUtility;
use App\Utilities\HttpHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class EventController extends AbstractController
{
    /**
     * add a new event to the given id tontine
     * @param int $idTontine
     * @param EntityManagerInterface $em
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @return JsonResponse
     * @throws TontineException
     */
    #[Route('/api/tontine/{idTontine}/event', name: 'app_event_create', methods: ['POST'])]
    public function createEvent(
        int                    $idTontine,
        EntityManagerInterface $em,
        Request                $request,
        SerializerInterface    $serializer,
        ValidatorInterface     $validator,
    ): JsonResponse {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $tontine = ControllerUtility::getTontine($em, $idTontine, $this->getUser());
        $event = HttpHelper::getResource($request, $serializer, Event::class);
        $errors = $validator->validate($event);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $member = $em->getRepository(Member::class)->findOneBy([
            'phone' => $this->getUser()->getUserIdentifier()
        ]);

        $typeEvent = $event->getType();
        if (!in_array($typeEvent, TypeEvent::allTypeEvent())) {
            return $this->json('You can not create events of this type.', Response::HTTP_BAD_REQUEST);
        }

        $participants = $event->getParticipants();
        if (count($participants) > 0) {
            foreach ($participants->toArray() as $participant) {
                $memberBelongTo = $em->getRepository(Member::class)->findOneBy(['phone' => $participant->getPhone()]);
                if (!$tontine->getTontinards()->contains($memberBelongTo)) {
                    throw new TontineException('You are not allowed to add this participant to this event.');
                } else {
                    $event->removeParticipant($participant);
                    $memberBelongTo->addEvent($event);
                    $event->addParticipant($memberBelongTo);
                }
            }
        }

        $event->setAuthor($member);
        $em->persist($event);
        $em->flush();

        return $this->json(
            $event,
            Response::HTTP_CREATED,
            [],
            [
                'groups' => [
                    GroupConst::GROUP_EVENT_READ,
                    GroupConst::GROUP_MEMBER_READ
                ]
            ]
        );
    }

    /**
     * get all event of a specific tontine
     * @param int $idTontine
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws TontineException
     */
    #[Route("/api/tontine/{idTontine}/event", name: "app_get_event", methods: ['GET'])]
    public function getEventOfTontine(
        int $idTontine,
        EntityManagerInterface $em,

    ): JsonResponse {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $tontine = ControllerUtility::getTontine($em, $idTontine, $this->getUser());

        $events = $em->getRepository(Event::class)->findAll();
        $eventsTontine = [];

        foreach ($events as $event) {
            if ($tontine->getTontinards()->contains($event->getAuthor())) {
                $eventsTontine[] = $event;
            }
        }

        return $this->json(
            $eventsTontine,
            Response::HTTP_OK,
            [],
            [
                'groups' =>
                [
                    GroupConst::GROUP_EVENT_READ, GroupConst::GROUP_MEMBER_READ
                ]
            ]
        );
    }
}
