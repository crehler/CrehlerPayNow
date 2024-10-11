<?php

namespace Crehler\PayNowPayment\Controller;

use OrderNotificationStates;
use Paynow\Notification;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"store-api"})
 */
class OrderNotificationController extends AbstractController
{

    private EntityRepositoryInterface $orderRepository;
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;
    private EntityRepositoryInterface $orderTransactionRepository;
    private OrderTransactionStateHandler $orderTransactionStateHandler;

    public function __construct(
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $orderTransactionRepository,
        LoggerInterface $logger,
        SystemConfigService $systemConfigService,
        OrderTransactionStateHandler $orderTransactionStateHandler
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;

    }
    /**
     * @Since("1.0.0")
     * @Route("/store-api/paynowpayment/notification", name="paynowpayment.store-api.notification", options={"seo"="false"}, methods={"POST"}, defaults={"auth_required"=false})
     * @OA\Post(
     *      path="/store-api/paynowpayment/notification",
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
    public function notification(Request $request): Response
    {
        $notification = $this->handleNotificationRequest($request);

        $response = new Response();
        switch($notification) {
            case OrderNotificationStates::STATE_ACCEPTED:
                return $response->setStatusCode(Response::HTTP_OK);
            case OrderNotificationStates::STATE_MISSING_ORDER:
                return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            case OrderNotificationStates::STATE_MISSING_AUTH:
               return $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            case OrderNotificationStates::STATE_NO_UPSERT:
                return $response->setStatusCode(Response::HTTP_ACCEPTED);
            case OrderNotificationStates::STATE_EXCEPTION:
                return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            default:
                return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }
    }

    public function handleNotificationRequest(Request $request): string
    {
        $signatureKeyLabel = $this->systemConfigService->get('CrehlerPayNowPayment.config.SingatureKeyLabel');

        $newHeaders = [];
        foreach ($request->headers->all() as $header => $value){
            $newHeaders[$header] = $value[0];
        }

        try {
            new Notification($signatureKeyLabel, (string) $request->getContent(), $newHeaders);
        } catch (\Exception $exception) {
            return OrderNotificationStates::STATE_MISSING_AUTH;
        }


        $paymentId = $request->get('paymentId');
        $orderNumber = $request->get('externalId');
        $status = $request->get('status');
//---------------------------------------------------------------------------------------------------------------------------------------------------------------

        $this->logger->info("PAYNOW_ ORDER_NO: " . $orderNumber . " PAYMENT_ID: " . $paymentId . " STATUS: " . $status);

        return $this->handleRequestBody($orderNumber, $status,$paymentId);
    }

    public function handleRequestBody($orderNumber, $status,$paymentId): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter("orderNumber",$orderNumber ));
        /** @var OrderEntity $orderToUpdate */
        $orderToUpdate = $this->orderRepository->search($criteria, Context::createDefaultContext())->first();

        if(!$orderToUpdate) {
            $this->logger->error("PAYNOW_  ORDER_DID_NOT_FIND_IN_REPOSITORY_WITH_PAYMENT_ID " . $paymentId . " AND_ORDER_ID: " .  $orderNumber);
            return OrderNotificationStates::STATE_MISSING_ORDER;
        }
        $orderId = $orderToUpdate->getId();

        $orderTransactionCriteria = new Criteria();
        $orderTransactionCriteria->addFilter(new EqualsFilter('orderId', $orderId));

        /** @var OrderTransactionEntity $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($orderTransactionCriteria, Context::createDefaultContext())->first();
        $orderTransactionid = $orderTransaction->getId();

        if($orderToUpdate->getAmountTotal() ==  1090.31 && $this->systemConfigService->get('CrehlerPayNowPayment.config.EnableSandbox')){
            $this->orderTransactionStateHandler->cancel($orderTransactionid, Context::createDefaultContext());
            return  OrderNotificationStates::STATE_ACCEPTED;
        }

        try{
        switch($status) {
            case "CONFIRMED":
                $this->orderTransactionStateHandler->paid($orderTransactionid, Context::createDefaultContext());
                return  OrderNotificationStates::STATE_ACCEPTED;
            case "REJECTED":
            case "CANCEL":
            case "ERROR":
                $this->orderTransactionStateHandler->cancel($orderTransactionid, Context::createDefaultContext());
                return  OrderNotificationStates::STATE_ACCEPTED;
        }} catch (\Throwable $e){
            $this->logger->info('PAYNOW_ MNA ERROR' . $orderTransaction->getOrder()->getOrderNumber() . $e->getMessage());
            return OrderNotificationStates::STATE_EXCEPTION;
        }

        return OrderNotificationStates::STATE_NO_UPSERT;
    }
}