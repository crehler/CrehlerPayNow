<?php

namespace Crehler\PayNowPayment\Controller;

use Crehler\PayNowPayment\Page\CheckPaymentPage;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\GenericPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @RouteScope(scopes={"storefront"})
 */
class PaymentReturnController extends StorefrontController
{
    private RouterInterface $router;

    private EntityRepositoryInterface $orderTransactionRepository;

    protected GenericPageLoader $genericLoader;

    public function __construct(EntityRepositoryInterface $orderTransactionRepository,
                                RouterInterface $router,
                                GenericPageLoader $genericLoader)
    {
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->router = $router;
        $this->genericLoader = $genericLoader;
    }

    /**
     * @Route("/paynow/check/{transactionId}", name="frontend.paynow.check", methods={"GET"})
     */
    public function createReturnUrl(?string $transactionId, Request $request, SalesChannelContext $context): Response
    {
        if (null === $transactionId) {
            throw new \Exception('Token is empty');
        }

        /** @var OrderTransactionEntity $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search(new Criteria([$transactionId]), $context->getContext())->first();
        $orderId = $orderTransaction->getOrderId();

        $page = $this->genericLoader->load($request, $context);
        $page = CheckPaymentPage::createFrom($page);
        $page->setTransactionId($transactionId);
        $page->setOrderId($orderId);

        return $this->renderStorefront('@PayNowPayment/storefront/page/paynow/check-payment.html.twig', ['page' => $page]);
    }
}