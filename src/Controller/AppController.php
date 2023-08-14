<?php

namespace App\Controller;

use App\Entity\Deposit;
use App\Enum\ReasonDeposit;
use App\Enum\TypeEvent;
use App\Enum\TypeSanction;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppController extends AbstractController
{
    #[Route('/api/param', name: 'app_get_param')]
    public function getConfiguration(): JsonResponse
    {
        $config = [];
        $config['reasonsDeposit'] = ReasonDeposit::allsReasonDeposit();
        $config['typesSanction'] = TypeSanction::allSanctions();
        $config['typesEvent'] = TypeEvent::allTypeEvent();

        return $this->json($config);
    }

    /**
     * implement a heartbeat for my api
     * @throws Exception
     */
    #[Route('/heart', name: 'app_app_heart', methods: ['GET'])]
    public function heart(): JsonResponse
    {
        return $this->json("Hello World! I am available", Response::HTTP_OK);
    }
}
