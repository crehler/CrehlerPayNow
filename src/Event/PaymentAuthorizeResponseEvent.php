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
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class PaymentAuthorizeResponseEvent extends Event implements ShopwareSalesChannelEvent
{
    protected Authorize $authorize;

    protected AsyncPaymentTransactionStruct $transaction;

    protected SalesChannelContext $salesChannelContext;

    public function __construct(AsyncPaymentTransactionStruct $transaction, SalesChannelContext $salesChannelContext, Authorize $authorize)
    {
        $this->transaction = $transaction;
        $this->salesChannelContext = $salesChannelContext;
        $this->authorize = $authorize;
    }

    /**
     * @return Authorize
     */
    public function getAuthorize(): Authorize
    {
        return $this->authorize;
    }

    /**
     * @return AsyncPaymentTransactionStruct
     */
    public function getTransaction(): AsyncPaymentTransactionStruct
    {
        return $this->transaction;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }
}
