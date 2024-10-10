<?php


namespace Crehler\PayNowPayment\Controller\PaymentMethods;


use Paynow\Client;
use Paynow\Model\PaymentMethods\PaymentMethod;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;
use Paynow\Exception\PaynowException;
use Paynow\Service\Payment;
use OpenApi\Annotations as OA;


#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Autoconfigure(public: true)]
class RetrieverController extends AbstractRetrieveController
{
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly LoggerInterface     $logger,
        private readonly Client              $client,
        private readonly RequestStack        $requestStack
    )
    {
    }

    public function getDecorated(): AbstractRetrieveController
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @OA\Get(
     *      path="/store-api/paynowpayment/payment-methods",
     *      description="Check available payment methods",
     *      operationId="payment methods route",
     *      tags={"Store API","payment methods","paynow"},
     *     @OA\Response(
     *          response="200",
     *          description="returned status"
     *     )
     * )
     * @param SalesChannelContext $context
     * @return PaymentResponse
     */
    #[Route(
        path: '/store-api/paynowpayment/payment-methods',
        name: 'store-api.paynowpayment.paymentmethods',
        methods: ['GET']
    )]
    public function load(SalesChannelContext $context): PaymentResponse
    {
        $paymentMethodsCollection = new PaymentMethodsCollection();

        try {
            $payment = new Payment($this->client);

            $paymentMethods = $payment->getPaymentMethods($context->getCurrency()->getIsoCode(), 0);

            $availablePaymentMethods = $paymentMethods->getAll() ?? [];

            /** @var PaymentMethod $method */
            foreach ($availablePaymentMethods as $method) {
                $struct = new PaymentMethodStruct($method->getId(), $method->getType(), $method->getName(), $method->getDescription(), $method->getImage(), $method->getStatus());
                $paymentMethodsCollection->add($struct);
            }
        } catch (PaynowException $exception) {
            $this->logger->info($exception->getMessage());
        }

        return new PaymentResponse($paymentMethodsCollection);
    }

    public function loadActive(?SalesChannelContext $context): PaymentResponse
    {
        $paymentMethodsCollection = new PaymentMethodsCollection();

        $availablePaymentMethods = [];
        try {
            $payment = new Payment($this->client);
            if ($context) {
                $paymentMethods = $payment->getPaymentMethods($context->getCurrency()->getIsoCode(), 0);
            } else {
                $paymentMethods = $payment->getPaymentMethods('PLN', 0);
            }
            $availablePaymentMethods = $paymentMethods->getAll() ?? [];
        } catch (PaynowException $exception) {
            $this->logger->info($exception->getMessage());
        }

        $showCards = $this->systemConfigService->get('CrehlerPayNowPayment.config.EnableCarts');
        /** @var PaymentMethod $method */
        foreach ($availablePaymentMethods as $method) {
            if ($method->getStatus() !== 'ENABLED') {
                continue;
            }
            if (!$showCards) {
                if ($method->getId() === 2002 || $method->getId() === 2003) {
                    continue;
                }
            }
            $struct = new PaymentMethodStruct($method->getId(), $method->getType(), $method->getName(), $method->getDescription(), $method->getImage(), $method->getStatus());
            $paymentMethodsCollection->add($struct);
        }

        return new PaymentResponse($paymentMethodsCollection);
    }
}