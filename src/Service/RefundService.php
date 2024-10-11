<?php

namespace Crehler\PayNowPayment\Service;


use Crehler\PayNowPayment\Entity\PaymentRefundHistoryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class RefundService
{
    public EntityRepository $orderRepository;
    public EntityRepository $orderTransactionRepository;
    private EntityRepository $payNowRefundHistoryRepository;

    public function __construct(
        EntityRepository $orderRepository,
        EntityRepository $orderTransactionRepository,
        EntityRepository $payNowRefundHistoryRepository
    )
    {
        $this->orderRepository = $orderRepository;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->payNowRefundHistoryRepository = $payNowRefundHistoryRepository;
    }

    public function createArrayOfProductsToRefund(array $productsToRefund):array
    {
        $arrayOfProductsToRefund = [];
        foreach ($productsToRefund as $product) {
            if ($product["refundedQty"] === 0) continue;
            $arrayOfProductsToRefund[] = [$product["id"] => strval($product["refundedQty"])];
        }
        return $arrayOfProductsToRefund;
    }

    public function getPaymentId(string $orderId): ?string
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('transactions.paymentMethod');
        $criteria->addFilter(new EqualsFilter('transactions.paymentMethod.handlerIdentifier', PayNowService::class));
        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($criteria, Context::createDefaultContext())->first();
        if (!isset($order->getTransactions()->first()->getCustomFields()['paynowPaymentId'])) {
            return null;
        }
        return $order->getTransactions()->first()->getCustomFields()['paynowPaymentId'];

    }

    public function getTransactionId(string $orderId)
    {
        $orderTransactionCriteria = new Criteria();
        $orderTransactionCriteria->addFilter(new EqualsFilter('orderId', $orderId));

        /** @var OrderTransactionEntity $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($orderTransactionCriteria, Context::createDefaultContext())->first();
        return $orderTransaction->getId();
    }

    public function getPaynowData(string $refundId): array
    {
        $payNowHistoryCriteria = new Criteria();
        $payNowHistoryCriteria->addFilter(new EqualsFilter('refundId', $refundId));

        /** @var PaymentRefundHistoryEntity $payNowHistoryRecord */
        $payNowHistoryRecord = $this->payNowRefundHistoryRepository->search($payNowHistoryCriteria, Context::createDefaultContext())->first();

        $orderTransactionCriteria = new Criteria();
        $orderTransactionCriteria->addFilter(new EqualsFilter('id', $payNowHistoryRecord->getTransactionId()));

        /** @var OrderTransactionEntity $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($orderTransactionCriteria, Context::createDefaultContext())->first();

        $orderCriteria = new Criteria();
        $orderCriteria->addFilter(new EqualsFilter('id', $orderTransaction->getOrderId()));
        /** @var OrderEntity $order */
        $order = $this->orderRepository->search($orderCriteria, Context::createDefaultContext())->first();


        return ["transactionId" => $payNowHistoryRecord->getTransactionId(),
                "refundAmount" => $payNowHistoryRecord->getRefundAmount(),
                "refundId" =>  $payNowHistoryRecord->getId(),
                "orderRefundPriceCustomField"=> $order->getCustomFields(),
                "orderTotalAmount"=> $order->getPrice()->getTotalPrice(),
                "orderId" => $order->getId(),
                "currentState" => $orderTransaction->getStateMachineState()->getTechnicalName()];
    }

}