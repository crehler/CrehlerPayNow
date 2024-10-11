<?php declare(strict_types=1);
/**
 * @copyright 2022 Crehler Sp. z o. o.
 * @link https://crehler.com/
 * @support support@crehler.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Crehler\PayNowPayment\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class IdempotencyKeyGenerator
{
    private EntityRepository $paynowIdempotencyKeyRepository;

    public function __construct(EntityRepository $paynowIdempotencyKeyRepository)
    {
        $this->paynowIdempotencyKeyRepository = $paynowIdempotencyKeyRepository;
    }

    public function generate(OrderTransactionEntity $orderTransactionEntity, ?Context $context = null)
    {
        if ($context === null) {
            $context = Context::createDefaultContext();
        }
        $idempotencyKey = uniqid();

        $this->paynowIdempotencyKeyRepository->upsert([
            [
                'id' => Uuid::randomHex(),
                'transactionId' => $orderTransactionEntity->getId(),
                'idempotencyKey' => $idempotencyKey
            ]
        ], $context);

        return $idempotencyKey;
    }
}
