<?php

namespace Crehler\PayNowPayment\Controller\StatusCheck;


use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


#[Route(defaults: ['_routeScope' => ['store-api']])]
class StatusCheckRoute extends AbstractStatusCheckRoute
{
    private EntityRepository $orderTransactionRepository;

    public function __construct(EntityRepository $orderTransactionRepository)
    {
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    #[Route(path: '/store-api/paynow/payment-check', name: 'store-api.paynow-payment-check', methods: ['POST'])]
    public function load(Request $request, SalesChannelContext $context): StatusCheckRouteResponse
    {
        $transactionId = $request->get('transactionId');

        if ($transactionId === null) {
            return new StatusCheckRouteResponse([
                'waiting' => false,
                'success' => false,
            ]);
        }

        $isOrderPaid = $this->isOrderPaid($transactionId, $context->getContext());

        switch ($isOrderPaid) {
            case true:
                $responseData = [
                    'waiting' => false,
                    'success' => true,
                ];
                break;
            case false:
                $responseData = [
                    'waiting' => true,
                    'success' => false
                ];
                break;
            default:
                $responseData = [
                    'waiting' => false,
                    'success' => false,
                ];
                break;
        }

        return new StatusCheckRouteResponse($responseData);
    }

    public function getDecorated(): AbstractStatusCheckRoute
    {
        throw new DecorationPatternException(self::class);
    }

    private function isOrderPaid(string $transactionId, Context $context): ?bool
    {
        $criteria = new Criteria([$transactionId]);

        /** @var OrderTransactionEntity $transaction */
        $transaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        $stateName = $transaction->getStateMachineState()->getTechnicalName();

        if ($stateName === OrderTransactionStates::STATE_PAID || $stateName === OrderTransactionStates::STATE_CANCELLED) {
            return true;
        } else if ($stateName === OrderTransactionStates::STATE_OPEN) {
            return false;
        } else {
            return null;
        }
    }
}