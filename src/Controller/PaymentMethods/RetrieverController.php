<?php


namespace Crehler\PayNowPayment\Controller\PaymentMethods;


use Paynow\Client;
use Paynow\Model\PaymentMethods\PaymentMethod;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Paynow\Exception\PaynowException;
use Paynow\Service\Payment;
use OpenApi\Annotations as OA;


/**
 * @RouteScope(scopes={"store-api"})
 */
class RetrieverController extends AbstractRetrieveController
{

    private SystemConfigService $systemConfigService;
    private Client $client;
    private LoggerInterface $logger;

    public function __construct(
        SystemConfigService $systemConfigService,
        LoggerInterface     $logger,
        Client              $client
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
        $this->client = $client;
    }

    public function getDecorated(): AbstractRetrieveController
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @Since("1.0.0")
     * @Route("/store-api/paynowpayment/payment-methods", name="store-api.paynowpayment.paymentmethods", methods={"GET"}, defaults={"auth_required"=false})
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
    public function load(SalesChannelContext $context): PaymentResponse
    {
        try {
            $payment = new Payment($this->client);
            $paymentMethods = $payment->getPaymentMethods($context->getCurrency()->getIsoCode(), 0);
            $availablePaymentMethods = $paymentMethods->getAll();
        } catch (PaynowException $exception) {
            $this->logger->info($exception->getMessage());
        }

        $paymentMethodsCollection = new PaymentMethodsCollection();

        /** @var PaymentMethod $method */
        foreach ($availablePaymentMethods as $method) {

                $struct = new PaymentMethodStruct($method->getId(), $method->getType(), $method->getName(), $method->getDescription(), $method->getImage(), $method->getStatus());
                $paymentMethodsCollection->add($struct);
        }
        return new PaymentResponse($paymentMethodsCollection);
    }

    public function loadActive(?SalesChannelContext $context): PaymentResponse
    {
        try {
            $payment = new Payment($this->client);
            if($context){
                $paymentMethods = $payment->getPaymentMethods($context->getCurrency()->getIsoCode(), 0);
            }else{
                $paymentMethods = $payment->getPaymentMethods('PLN', 0);
            }
            $availablePaymentMethods = $paymentMethods->getAll();
        } catch (PaynowException $exception) {
            $this->logger->info($exception->getMessage());
        }

        $paymentMethodsCollection = new PaymentMethodsCollection();


        $showCards = $this->systemConfigService->get('CrehlerPayNowPayment.config.EnableCarts');

        /** @var PaymentMethod $method */
        foreach ($availablePaymentMethods as $method) {
            if ($method->getStatus() !== 'ENABLED') continue;
            if (!$showCards) {
                if ($method->getId() === 2002 || $method->getId() === 2003) continue;
            }
            $struct = new PaymentMethodStruct($method->getId(), $method->getType(), $method->getName(), $method->getDescription(), $method->getImage(), $method->getStatus());
            $paymentMethodsCollection->add($struct);
        }

        return new PaymentResponse($paymentMethodsCollection);
    }

}