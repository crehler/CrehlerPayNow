<?php

declare(strict_types=1);

/**
 * @copyright 2022 Crehler Sp. z o. o.
 * @link https://crehler.com/
 * @support support@crehler.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Crehler\PayNowPayment\Subscriber;

use Crehler\PayNowPayment\Service\CartChanger;
use Shopware\Core\Checkout\Cart\Event\CartChangedEvent;
use Shopware\Core\Checkout\Cart\Event\CartVerifyPersistEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly CartChanger $cartChanger)
    {
    }


    public static function getSubscribedEvents()
    {
        return [CartVerifyPersistEvent::class => ['onChartChange', 999]];
    }

    public function onChartChange(CartVerifyPersistEvent $event)
    {
        $this->cartChanger->change($event->getCart());
    }
}