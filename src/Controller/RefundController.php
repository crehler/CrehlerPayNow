<?php

namespace Crehler\PayNowPayment\Controller;


use Crehler\PayNowPayment\Controller\PaymentMethods\PaymentResponse;
use Crehler\PayNowPayment\Controller\PaymentMethods\RetrieverController;
use Paynow\Client;
use Crehler\PayNowPayment\Service\RefundService;
use Psr\Log\LoggerInterface;
use Paynow\Exception\PaynowException;
use Paynow\Service\Refund;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Shopware\Core\Framework\Uuid\Uuid;
use Crehler\PayNowPayment\Common\OrderAmountFormat;


#[Route(defaults: ['_routeScope' => ['api']])]
class RefundController extends AbstractController
{
    public function __construct(
        private readonly EntityRepository             $orderTransactionRepository,
        private readonly LoggerInterface              $logger,
        private readonly EntityRepository             $orderRepository,
        private readonly OrderTransactionStateHandler $orderTransactionStateHandler,
        private readonly EntityRepository             $paynowRefundHistoryRepository,
        private readonly RetrieverController          $retrieveController,
        private readonly Client                       $client,
        private readonly RefundService                $refundService
    )
    {
    }

    #[Route(path: '/api/paynowpayment/fetch-refund-payment', name: 'paynowpayment.api.fetch-refund', defaults: ['auth_required' => true], methods: ['POST'])]
    public function fetchStatus(Request $request): JSONResponse
    {
        try {
            $refund = new Refund($this->client);
            $result = $refund->status($request->get("refundId"));
        } catch (PaynowException $e) {
            $this->logger->info('PAYNOW_ERROR on ' . $e->getMessage());
            return new JSONResponse(["response" => 'error'], 400);
        }

        $paynowData = $this->refundService->getPaynowData((string)$request->get("refundId"));

        $orderRefundPriceCustomField = $paynowData["orderRefundPriceCustomField"];
        $refoundAmountForOrder = $paynowData["refundAmount"];
        $orderTotalAmount = $paynowData["orderTotalAmount"];

        if (isset($orderRefundPriceCustomField['refoundAmount'])) {
            $prevValue = intval($orderRefundPriceCustomField['refoundAmount']);
            $refoundAmountForOrder = $prevValue + $refoundAmountForOrder;
        }

        try {
            if ($result->getStatus() === 'SUCCESSFUL') {
                $this->orderRepository->upsert([[
                    'id' => $paynowData["orderId"],
                    'customFields' => ['refoundAmount' => $refoundAmountForOrder],
                ]], Context::createDefaultContext());

                if ($paynowData["currentState"] === OrderTransactionStates::STATE_PAID && $refoundAmountForOrder < $orderTotalAmount) {
                    $this->orderTransactionStateHandler->refundPartially($paynowData["transactionId"], Context::createDefaultContext());
                } elseif ($paynowData["currentState"] === OrderTransactionStates::STATE_PAID || ('refunded_partially' && $refoundAmountForOrder >= $orderTotalAmount)) {
                    $this->orderTransactionStateHandler->refund($paynowData["transactionId"], Context::createDefaultContext());
                }

                $this->paynowRefundHistoryRepository->upsert([[
                    'id' => $paynowData["refundId"],
                    'paynowStatus' => 'zwrot zaakceptowany'
                ]], Context::createDefaultContext());
            }

        } catch (\Throwable $e) {
            $this->logger->info('PAYNOW_ERROR on ' . $e->getMessage());
            return new JSONResponse(["success" => false], 400);
        }
        return new JSONResponse(["success" => true], 202);
    }

    #[Route(path: '/api/paynowpayment/refund-payment', name: 'paynowpayment.api.refund', defaults: ['auth_required' => true], methods: ['POST'])]
    public function refund(Request $request): JSONResponse
    {
        $payNowPaymentId = $this->refundService->getPaymentId((string)$request->get("orderId"));

        if (!$payNowPaymentId) $this->json(['success' => false]);

        try {
            $refund = new Refund($this->client);
            $result = $refund->create($payNowPaymentId,
                uniqid(),
                OrderAmountFormat::floatToInt((int)$request->get("amountToRefund")),
                (string)$request->get("descriptionOfRefund"));
        } catch (PaynowException $e) {
            $this->logger->info('PAYNOW_ERROR on ' . $e->getMessage());
            return new JSONResponse(["success" => false], 400);
        }

        if (!$this->handleResult($result, $request)) return new JSONResponse(["success" => false], 400);

        return new JSONResponse(["success" => true], 202);
    }

    private function handleResult($result, $request): bool
    {
        $orderTransactionid = $this->refundService->getTransactionId((string)$request->get("orderId"));
        $arrayOfProductsToRefund = $this->refundService->createArrayOfProductsToRefund((array)$request->get("productsToRefund"));
        try {
            $this->orderTransactionRepository->update([
                [
                    'id' => $orderTransactionid,
                    'customFields' => ['refundId' => $result->getRefundId()],
                ],
            ], Context::createDefaultContext());

            $this->paynowRefundHistoryRepository->create([
                [
                    'id' => Uuid::randomHex(),
                    'transactionId' => $orderTransactionid,
                    'paynowStatus' => 'oczekuje na zwrot',
                    'productList' => $arrayOfProductsToRefund,
                    'refundId' => $result->getRefundId(),
                    'refundAmount' => OrderAmountFormat::intToFloat(OrderAmountFormat::floatToInt((int)$request->get("amountToRefund"))),
                ],
            ], Context::createDefaultContext());

        } catch (\Throwable $e) {
            $this->logger->info('PAYNOW_ERROR on ' . $e->getMessage());
            return false;
        }
        return true;
    }

    #[Route(path: '/api/paynowpayment/test-api', name: 'paynowpayment.api.test-api', defaults: ['auth_required' => true], methods: ['POST'])]
    public function testApi(): JSONResponse
    {
        try {
            /** @var PaymentResponse $footerBankIcons */
            $footerBankIcons = $this->retrieveController->loadActive(null);

            if ($footerBankIcons->getPaymentMethods()->getElements()) {
                return new JSONResponse(["success" => true], 200);
            }
            return new JSONResponse(["success" => false], 400);
        } catch (\Throwable $e) {
            return new JSONResponse(["success" => false], 400);
        }
    }
}