<?php

namespace App\Controller;

use App\Conts\GroupConst;
use App\Entity\CashFlow;
use App\Entity\Deposit;
use App\Entity\Loan;
use App\Entity\Member;
use App\Entity\Tontine;
use App\Enum\ErrorCode;
use App\Enum\ReasonDeposit;
use App\Enum\StatusDeposit;
use App\Exception\TontineException;
use App\Utilities\ControllerUtility;
use App\Utilities\HttpHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

const NO_FOUND_CASHFLOW = " cashflow not found";

#[Route(path: '/api/cashFlow')]
class CashFlowController extends BasicController
{
    /**
     * create a new deposit and add to the cash flow
     * @param int $id
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws TontineException
     * @throws Exception
     */
    #[Route('/{id}', name: 'app_cashflow_create', methods: ['POST'])]
    public function create(
        int                    $id,
        Request                $request,
        SerializerInterface    $serializer,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$this->isGranted('ROLE_ACCOUNT_MANAGER')) {
            return $this->json('access denied', Response::HTTP_FORBIDDEN);
        }
        $this->denyAccessUnlessGranted('ROLE_ACCOUNT_MANAGER');

        $cashFlow = $em->getRepository(CashFlow::class)->find($id);
        if (!$cashFlow) {
            throw new TontineException(NO_FOUND_CASHFLOW);
        }

        $tontine = $em->getRepository(Tontine::class)->findOneBy(['fund' => $cashFlow]);
        if (!$tontine) {
            throw new TontineException('no tontine is attached to the cash flow');
        }

        $deposit = HttpHelper::getResource($request, $serializer, Deposit::class);

        if (!in_array($deposit->getReasons(), ReasonDeposit::allsReasonDeposit())) {
            return $this->json("Invalid deposit reason!", Response::HTTP_BAD_REQUEST);
        }

        $isAuthorPartOfTontine = count($tontine->getTontinards()->filter(function (Member $member) use ($deposit) {
            return $member->getUsername() == $deposit->getAuthor()->getUsername();
        })) > 0;

        if (!$isAuthorPartOfTontine) {
            throw new TontineException('The author of the deposit :'
                . $deposit->getAuthor()->getUsername() . ' is not the tontine');
        }

        if ($cashFlow->getCurrency() != $deposit->getCurrency()) {
            $this->json(
                'Currency between deposit and cash flow does not match.' .
                    ' deposit with different currency is not allowed for now.',
                Response::HTTP_BAD_REQUEST
            );
        }
        $author = $em->getRepository(Member::class)->findOneBy(['username' => $deposit->getAuthor()->getUsername()]);
        $author->addDeposit($deposit);

        $deposit->setCreationDate(new DateTime());
        $deposit->setCashFlow($cashFlow);
        $deposit->setStatus(StatusDeposit::PENDING);

        $cashFlow->setBalance($cashFlow->getBalance() + $deposit->getAmount());
        $cashFlow->setDividendes(ControllerUtility::computeDividends($cashFlow->getDeposits()));

        $em->persist($deposit);
        $em->persist($author);
        $em->persist($cashFlow);
        $em->flush();

        return $this->json($deposit, Response::HTTP_CREATED, [], ['groups' => [GroupConst::GROUP_DEPOSIT_READ]]);
    }

    /**
     * delete a deposit. Only President/account manager/Admin role can delete a deposit.
     * @param int $id
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws TontineException
     */
    #[Route('/{id}', name: 'app_cashflow_delete', methods: ['DELETE'])]

    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ACCOUNT_MANAGER');

        $member = $this->getUser();
        $deposit = $em->getRepository(Deposit::class)->find($id);
        if (!$deposit) {
            throw new TontineException('Deposit not found');
        }

        $tontine = $em->getRepository(Tontine::class)->findOneBy(['fund' => $deposit->getCashFlow()]);
        if (!$tontine) {
            throw new TontineException('no tontine is attached to the deposit');
        }

        if (!$tontine->getTontinards()->contains($member)) {
            throw new TontineException('The tontinard is not part of the deposit');
        }

        $deposit->setStatus(StatusDeposit::REJECTED);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * get all deposits of a cash flow (only deposits created during the defined config period)
     * @param int $id
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws TontineException
     */
    #[Route('/{id}/deposits', name: 'app_cashflow_getdeposits', methods: ['GET'])]
    public function getDeposits(int $id, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        // only member of the tontine of the cash flow can get the deposits
        $member = $this->getUser();
        if (!$member) {
            throw new TontineException('Member is not authenticated');
        }
        $cashFlow = $em->getRepository(CashFlow::class)->find($id);
        if (!$cashFlow) {
            return $this->json(NO_FOUND_CASHFLOW, Response::HTTP_NOT_FOUND);
        }
        $tontine = $em->getRepository(Tontine::class)->findOneBy(['fund' => $cashFlow]);
        if (!$tontine) {
            throw new TontineException('no tontine is attached to the cash flow with id : ' . $id);
        }

        //TODO: to make and of the periode configurable
        // $period = $this->getPeriod($tontine->getConfiguration()->getInterval());

        if (!$tontine->getTontinards()->contains($member)) {
            throw new TontineException('The authenticated use is not part of the ' .
                ' tontine of the cash flow with id : ' . $id);
        }

        $deposits = $cashFlow->getDeposits()->filter(function (Deposit $deposit) {
            return $deposit->getStatus() != StatusDeposit::REJECTED;
        });

        return $this->json(
            $deposits,
            Response::HTTP_OK,
            [],
            [
                'groups' => [GroupConst::GROUP_DEPOSIT_READ, GroupConst::GROUP_MEMBER_READ]
            ]
        );
    }


    /**
     * update status of deposits (Validated or Rejected)
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws TontineException
     */
    #[Route('/{id}/status', name: 'app_cashflow_status', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ACCOUNT_MANAGER');

        $member = $this->getUser();
        $status = json_decode($request->getContent(), true)['status'];

        if (!in_array($status, array_column(StatusDeposit::allStatusDeposits(), 'name'))) {
            return $this->json('invalid status', Response::HTTP_BAD_REQUEST);
        }

        $deposit = $em->getRepository(Deposit::class)->find($id);
        if (!$deposit) {
            return $this->json('Deposit not found', Response::HTTP_NOT_FOUND);
        }

        $tontine = $em->getRepository(Tontine::class)->findOneBy(['fund' => $deposit->getCashFlow()]);
        if (!$tontine) {
            throw new TontineException('no tontine is attached to the deposit');
        }

        if (!$tontine->getTontinards()->contains($member)) {
            throw new TontineException('The tontinard is not part of the deposit');
        }

        $deposit->setStatus($status);
        $em->flush();

        return $this->json($deposit, Response::HTTP_NO_CONTENT, [], ['groups' => ['deposit:read']]);
    }

    /**
     * Create a new loan from cashFlow
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @return JsonResponse
     * @throws TontineException
     * @throws Exception
     */
    #[Route('/{id}/loan', name: 'app_create_loan', methods: ['POST'])]
    public function createLoan(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        SerializerInterface    $serializer,
        ValidatorInterface     $validator
    ): JsonResponse {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $loan = HttpHelper::getResource($request, $serializer, Loan::class);

        $this->validateData($em, $id, '');

        $cashFlow = $em->getRepository(CashFlow::class)->find($id);
        $errors = $validator->validate($loan);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        if ($loan->getAmount() > $cashFlow->getBalance()) {
            return $this->json(
                ControllerUtility::buildError(ErrorCode::EM04),
                Response::HTTP_BAD_REQUEST
            );
        }

        $author = $this->getUser();
        $tontine = $em->getRepository(Tontine::class)->findOneBy(['fund' => $cashFlow]);

        $maxDateLoanDuration = $tontine->getConfiguration()->getMaxDurationLoan() ?? 0;
        $maxDate = (new DateTime())->modify("+$maxDateLoanDuration days");
        if ($loan->getRedemptionDate()->getOffset() > $maxDate->getOffset()) {
            return $this->json(
                ControllerUtility::buildError(ErrorCode::EM05),
                Response::HTTP_CONFLICT
            );
        }

        $loan->setStatus(StatusDeposit::PENDING);
        $loan->setCreationDate(new DateTime());
        $loan->setCashFlow($cashFlow);
        $loan->setAuthor($author);
        $loan->setCurrency($cashFlow->getCurrency());
        $em->persist($loan);
        $em->flush();

        return $this->json(
            $loan,
            Response::HTTP_CREATED,
            [],
            [
                'groups' => [
                    GroupConst::GROUP_LOAN_READ,
                    GroupConst::GROUP_MEMBER_READ
                ]
            ]
        );
    }

    /**
     * vote a loan
     * @param int $cashFlowId
     * @param int $loanId
     * @param EntityManagerInterface $em
     * @param Request $request
     * @return JsonResponse
     * @throws TontineException
     * @throws Exception
     */
    #[Route("/{cashFlowId}/loan/{loanId}/vote", name: "app_loan_vote", methods: ['PATCH'])]
    public function voteLoan(
        int                    $cashFlowId,
        int                    $loanId,
        EntityManagerInterface $em,
        Request                $request
    ): JsonResponse {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $status = json_decode($request->getContent(), true)['status'];
        $this->validateData($em, $cashFlowId, $status);

        $loan = $em->getRepository(Loan::class)->find($loanId);
        if (!$loan) {
            throw new TontineException('Loan not found');
        }

        $phone = $this->getUser()->getUserIdentifier();
        $member = $em->getRepository(Member::class)->findOneBy(['phone' => $phone]);

        $voters = $loan->getVoters();
        $isUserAlreadyVoted = in_array($member->getId(), array_column($voters, 'id'));

        if ($isUserAlreadyVoted) {
            return $this->json(
                ControllerUtility::buildError(ErrorCode::EM06),
                Response::HTTP_BAD_REQUEST
            );
        }

        $vote = ['id' => $member->getId(), 'status' => $status];
        $voters[] = $vote;
        $loan->setVoters($voters);
        $loan->setUpdateDate(new DateTime());
        $em->flush();

        return $this->json(
            $loan,
            Response::HTTP_CREATED,
            [],
            [
                'groups' => [
                    GroupConst::GROUP_LOAN_READ,
                    GroupConst::GROUP_MEMBER_READ
                ]
            ]
        );
    }

    /**
     * validate field required to update status
     * @param EntityManagerInterface $em
     * @param int $cashFlowId
     * @param string $status
     * @throws TontineException
     */
    private function validateData(
        EntityManagerInterface $em,
        int                    $cashFlowId,
        string                 $status
    ): void {
        // validation
        $cashFlow = $em->getRepository(CashFlow::class)->find($cashFlowId);
        if (!$cashFlow) {
            throw new TontineException(NO_FOUND_CASHFLOW);
        }

        if ($status && !in_array($status, StatusDeposit::allStatusDeposits())) {
            throw new  TontineException('invalid status', Response::HTTP_BAD_REQUEST);
        }

        $author = $this->getUser();
        $tontine = $em->getRepository(Tontine::class)->findOneBy(['fund' => $cashFlow]);
        if (!$tontine) {
            throw new TontineException('Tontine not found');
        }

        // check if the author of Loan is the part of the attached tontine of cash flow
        if (!$tontine->getTontinards()->contains($author)) {
            throw new TontineException(
                "The Author of Loan is not part of the tontine of cash flow",
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * get all loan of the tontine that the authenticated user has access
     * @param int $cashFlowId
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws TontineException
     */
    #[Route("/{cashFlowId}/loan", name: "app_cashflow_get_loan", methods: ['GET'])]
    public function getAllLoanOfTontine(int $cashFlowId, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $member = $this->getUser();
        if (!$em->getRepository(Member::class)->findOneBy(['phone' => $member->getUserIdentifier()])) {
            throw new TontineException('Login Member not found');
        }

        $cashFlow = $em->getRepository(CashFlow::class)->find($cashFlowId);
        if (!$cashFlow) {
            throw new TontineException("No cash flow found");
        }

        return $this->json(
            $cashFlow->getLoans(),
            Response::HTTP_OK,
            [],
            [
                'groups' => [
                    GroupConst::GROUP_LOAN_READ,
                    GroupConst::GROUP_MEMBER_READ
                ]
            ]
        );
    }

    /**
     * extract deposit data between two dates
     * @param int $cashFlowId
     * @param EntityManagerInterface $em
     * @param Request $request
     * @param KernelInterface $kernel
     * @return JsonResponse
     * @throws TontineException
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws Exception
     */
    #[Route("/{cashFlowId}/deposits/extract", name: "app_cashflow_get_loan_between", methods: ['GET'])]
    public function getLoanBetweenDates(
        int                    $cashFlowId,
        EntityManagerInterface $em,
        Request                $request,
        KernelInterface        $kernel
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $member = $this->getUser();
        if (!$em->getRepository(Member::class)->findOneBy(['phone' => $member->getUserIdentifier()])) {
            throw new TontineException('Login Member not found');
        }

        $cashFlow = $em->getRepository(CashFlow::class)->find($cashFlowId);
        if (!$cashFlow) {
            throw new TontineException("No cash flow found");
        }

        $startDate = new DateTime($request->query->get('startDate') ?? 'NOW');
        $endDate = new DateTime($request->query->get('endDate') ?? 'NOW');

        if ($startDate->getTimestamp() > $endDate->getTimestamp()) {
            throw new TontineException("Start date must be before end date");
        }

        $depositsToExtract = $cashFlow->getDeposits()->filter(function (Deposit $deposit) use ($startDate, $endDate) {
            return ($deposit->getCreationDate()->getTimestamp() >= $startDate->getTimestamp()) &&
                ($deposit->getCreationDate()->getTimestamp() <= $endDate->getTimestamp());
        });

        $spreadsheet = new Spreadsheet();


        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Deposits');
        $sheet->setCellValue('A1', 'Nom');
        $sheet->setCellValue('B1', 'Versement');
        $sheet->setCellValue('C1', 'Date');
        $sheet->setCellValue('D1', 'Raisons');
        $sheet->setCellValue('E1', 'Status');

        foreach ($depositsToExtract->toArray() as $key => $deposit) {
            $sheet->setCellValue(
                'A' . $key + 2,
                $deposit->getAuthor()->getFirstName() . ' ' . $deposit->getAuthor()->getLastName()
            );
            $sheet->setCellValue('B' . $key + 2, $deposit->getAmount());
            $sheet->setCellValue('C' . $key + 2, $deposit->getCreationDate()->format('Y-m-d'));
            $sheet->setCellValue('D' . $key + 2, $deposit->getReasons());
            $sheet->setCellValue('E' . $key + 2, $deposit->getStatus());
        }

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);

        // In this case, we want to write the file in the public directory
        $publicDirectory = $kernel->getProjectDir() . '/public';
        $nameFile = 'deposits' . (new DateTime('NOW'))->getTimestamp() . '.xlsx';
        $excelFilepath = $publicDirectory . '/' . $nameFile;

        // Create the file
        $writer->save($excelFilepath);

        // Return a text response to the browser saying that the Excel was successfully created
        return $this->json($nameFile);
    }
}
