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

namespace Crehler\PayNowPayment\Event;

use Paynow\Client;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class PaymentAuthorizeRequestEvent extends Event implements ShopwareSalesChannelEvent
{
    protected array $data;

    protected ?string $idempotencyKey;

    protected PaymentTransactionStruct $transaction;

    protected ?SalesChannelContext $salesChannelContext;

    protected Client $client;

    public function __construct(PaymentTransactionStruct $transaction, ?SalesChannelContext $salesChannelContext, Client $client, array $data, ?string $idempotencyKey = null)
    {
        $this->transaction = $transaction;
        $this->salesChannelContext = $salesChannelContext;
        $this->client = $client;
        $this->data = $data;
        $this->idempotencyKey = $idempotencyKey;
    }

    public function getName(): string
    {
        return 'checkout.payment.paynow.authorize.request';
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return PaymentTransactionStruct
     */
    public function getTransaction(): PaymentTransactionStruct
    {
        return $this->transaction;
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    /**
     * @return SalesChannelContext
     */
    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext ? $this->salesChannelContext->getContext() : Context::createDefaultContext();
    }
}
