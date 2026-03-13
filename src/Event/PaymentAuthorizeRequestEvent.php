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
use Symfony\Contracts\EventDispatcher\Event;

class PaymentAuthorizeRequestEvent extends Event
{
    public function __construct(
        private readonly PaymentTransactionStruct $transaction,
        private readonly Context $context,
        private Client $client,
        private array $data,
        private readonly ?string $idempotencyKey = null
    ) {
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

    public function getTransaction(): PaymentTransactionStruct
    {
        return $this->transaction;
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
