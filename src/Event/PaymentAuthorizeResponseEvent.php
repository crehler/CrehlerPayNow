<?php declare(strict_types=1);
/**
 * @copyright 2022 Crehler Sp. z o. o.
 * @link https://crehler.com/
 * @support support@crehler.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Crehler\PayNowPayment\Event;

use Paynow\Response\Payment\Authorize;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Symfony\Contracts\EventDispatcher\Event;

class PaymentAuthorizeResponseEvent extends Event
{
    public function __construct(
        public readonly PaymentTransactionStruct $transaction,
        public readonly Context $context,
        public readonly Authorize $authorize
    ) {
    }

    public function getName(): string
    {
        return 'checkout.payment.paynow.authorize.response';
    }
}
