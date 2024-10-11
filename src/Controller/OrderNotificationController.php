<?php

namespace Crehler\PayNowPayment\Controller;

use Crehler\PayNowPayment\Enum\OrderNotificationStates;
use Crehler\PayNowPayment\Service\PayNowServicesFactory;
use OpenApi\Annotations as OA;
use Paynow\Notification;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route(defaults: ['_routeScope' => ['storefront']])]
class OrderNotificationController extends AbstractController
{

    private EntityRepository $orderRepository;
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;
    private EntityRepository $orderTransactionRepository;
    private OrderTransactionStateHandler $orderTransactionStateHandler;
    private PayNowServicesFactory $payNowServicesFactory;

    public function __construct(
        EntityRepository             $orderRepository,
        EntityRepository             $orderTransactionRepository,
        LoggerInterface              $logger,
        SystemConfigService          $systemConfigService,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        PayNowServicesFactory        $payNowServicesFactory
    )
    {
        $this->orderRepository = $orderRepository;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->payNowServicesFactory = $payNowServicesFactory;
    }

    /**
     * @OA\Post(
     *      path="/paynowpayment/notification",
     *      description="Set the current status for order transaction",
     *      operationId="paynow notification route",
     *      tags={"Store API","paynow"},
     *     @OA\Parameter(
     *          parameter="paymentId",
     *          name="paymentId",
     *          in="content",
     *          description="Paynow id",
     *          required=true,
     *          @OA\Schema(type="string"),
     *      ),
     *      @OA\Parameter(
     *          parameter="externalId",
     *          name="externalId",
     *          in="content",
     *          description="Shopware order number",
     *          required=true,
     *          @OA\Schema(type="string"),
     *      ),
     *          @OA\Parameter(
     *          parameter="status",
     *          name="status",
     *          in="content",
     *          description="Status of payment",
     *          required=true,
     *          @OA\Schema(type="string"),
     *      ),
     *     @OA\Response(
     *          response="200",
     *          description="returned status"
     *      ),
     *     @OA\Response(
     *          response="400",
     *          description="failed"
     *     )
     * )
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/paynowpayment/notification', name: 'paynowpayment.notification', defaults: ['auth_required' => false], methods: ['POST'])]
    public function notification(Request $request): Response
    {
        $notification = $this->handleNotificationRequest($request);
        $response = new Response();
        switch ($notification) {
            case OrderNotificationStates::STATE_ACCEPTED:
                return $response->setStatusCode(Response::HTTP_OK);
            case OrderNotificationStates::STATE_MISSING_AUTH:
                return $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            case OrderNotificationStates::STATE_NO_UPSERT:
                return $response->setStatusCode(Response::HTTP_ACCEPTED);
            default:
                return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }
    }

    public function handleNotificationRequest(Request $request): string
    {
        $newHeaders = [];
        foreach ($request->headers->all() as $header => $value) {
            $newHeaders[$header] = $value[0];
        }

        try {
            new Notification($this->payNowServicesFactory->getSignature(), (string)$request->getContent(), $newHeaders);
        } catch (\Exception $exception) {
            return OrderNotificationStates::STATE_MISSING_AUTH;
        }


        $paymentId = $request->get('paymentId');
        $orderNumber = $request->get('externalId');
        $status = $request->get('status');

        $this->logger->info("PAYNOW_ ORDER_NO: " . $orderNumber . " PAYMENT_ID: " . $paymentId . " STATUS: " . $status);

        return $this->handleRequestBody($orderNumber, $status, $paymentId);
    }

    public function handleRequestBody($orderNumber, $status, $paymentId): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter("orderNumber", $orderNumber));
        /** @var OrderEntity $orderToUpdate */
        $orderToUpdate = $this->orderRepository->search($criteria, Context::createDefaultContext())->first();

        if (!$orderToUpdate) {
            $this->logger->error("PAYNOW_  ORDER_DID_NOT_FIND_IN_REPOSITORY_WITH_PAYMENT_ID " . $paymentId . " AND_ORDER_ID: " . $orderNumber);
            return OrderNotificationStates::STATE_MISSING_ORDER;
        }
        $orderId = $orderToUpdate->getId();

        $orderTransactionCriteria = new Criteria();
        $orderTransactionCriteria->addFilter(new EqualsFilter('orderId', $orderId));

        /** @var OrderTransactionEntity $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($orderTransactionCriteria, Context::createDefaultContext())->first();
        $orderTransactionId = $orderTransaction->getId();

        if ($orderToUpdate->getAmountTotal() == 1090.31 && $this->systemConfigService->get('CrehlerPayNowPayment.config.EnableSandbox')) {
            $this->orderTransactionStateHandler->cancel($orderTransactionId, Context::createDefaultContext());
            return OrderNotificationStates::STATE_ACCEPTED;
        }

        try {
            switch ($status) {
                case "CONFIRMED":
                    $this->orderTransactionStateHandler->paid($orderTransactionId, Context::createDefaultContext());
                    return OrderNotificationStates::STATE_ACCEPTED;
                case "REJECTED":
                case "CANCEL":
                case "ERROR":
                    $this->orderTransactionStateHandler->cancel($orderTransactionId, Context::createDefaultContext());
                    return OrderNotificationStates::STATE_ACCEPTED;
            }
        } catch (\Throwable $e) {
            $this->logger->info('PAYNOW_ MNA ERROR' . $orderTransaction->getOrder()->getOrderNumber() . $e->getMessage());
            return OrderNotificationStates::STATE_EXCEPTION;
        }

        return OrderNotificationStates::STATE_NO_UPSERT;
    }
}
