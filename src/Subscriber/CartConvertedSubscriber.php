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
use Crehler\PayNowPayment\Struct\CrehlerLineItemPrice;
use Shopware\Core\Checkout\Cart\Order\CartConvertedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartConvertedSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly CartChanger $cartChanger)
    {
    }

    public static function getSubscribedEvents()
    {
        return [CartConvertedEvent::class => 'onCartConvert'];
    }

    public function onCartConvert(CartConvertedEvent $event)
    {
        $cart = $event->getCart();

        $cart = $this->cartChanger->change($cart);

        $convertedCart = $event->getConvertedCart();

        foreach ($convertedCart['lineItems'] as &$lineItem) {
            $identifier = $lineItem['identifier'];
            $flatCart = $cart->getLineItems()->getFlat();
            $extension = array_filter($flatCart, fn($item) => $item->getId() === $identifier);
            if (count($extension) === 0) {
                continue;
            }
            $extension = array_shift($extension)->getExtension(CrehlerLineItemPrice::getExtensionName());
            if (!array_key_exists('customFields', $lineItem)) {
                $lineItem['customFields'] = [];
            }
            $lineItem['customFields'][CrehlerLineItemPrice::getExtensionName()] = $extension;
        }

        $event->setConvertedCart($convertedCart);
    }
}