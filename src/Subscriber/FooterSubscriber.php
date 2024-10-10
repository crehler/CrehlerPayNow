<?php


namespace Crehler\PayNowPayment\Subscriber;


use Crehler\PayNowPayment\Controller\PaymentMethods\AbstractRetrieveController;
use Crehler\PayNowPayment\Controller\PaymentMethods\PaymentResponse;
use Crehler\PayNowPayment\Controller\PaymentMethods\RetrieverController;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class FooterSubscriber implements EventSubscriberInterface
{
    const CACHE_LIFETIME = 86400;
    const CACHE_KEY_PAYMENT_METHODS = 'PaymentMethods';
    const CACHE_NAMESPACE = 'CrehlerPaynowIntegration';

    protected FilesystemAdapter $cache;

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly RetrieverController  $paymentMethods
    )
    {
        $this->cache = new FilesystemAdapter(self::CACHE_NAMESPACE);
    }
    public static function getSubscribedEvents(): array
    {
        return [
            FooterPageletLoadedEvent::class => 'onFooterLoaded'
        ];
    }

    public function onFooterLoaded(FooterPageletLoadedEvent $event)
    {
        $pageLet = $event->getPagelet();
        $paymentMethodsCache = $this->cache->getItem(self::CACHE_KEY_PAYMENT_METHODS);

        if($paymentMethodsCache->get()){
            $pageLet->addExtension("paymentMethods",$paymentMethodsCache->get());
            return;
        };

        /** @var PaymentResponse $footerBankIcons */
        $footerBankIcons = $this->paymentMethods->loadActive($event->getSalesChannelContext())->getResult();
        $paymentMethodsCache->set($footerBankIcons);
        $paymentMethodsCache->expiresAfter(self::CACHE_LIFETIME);

        $this->cache->save($paymentMethodsCache);

        $pageLet->addExtension("paymentMethods",$footerBankIcons);

    }
}