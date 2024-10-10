<?php


namespace Crehler\PayNowPayment\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;


class PaymentRefundHistoryEntity extends Entity
{

    use EntityIdTrait;

    /**
     * @var string
     */
    protected $transactionId;

    /**
     * @var string
     */
    protected $refundId;


    /**
     * @var string
     */
    protected $paynowStatus;

    /**
     * @var int
     */
    protected $refundAmount;

    /**
     * @var array|null
     */
    protected $productId;


    /**
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     */
    public function setTransactionId(string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return string
     */
    public function getRefundId(): string
    {
        return $this->refundId;
    }

    /**
     * @param string $refundId
     */
    public function setRefundId(string $refundId): void
    {
        $this->refundId = $refundId;
    }

    /**
     * @return int
     */
    public function getRefundAmount(): int
    {
        return $this->refundAmount;
    }

    /**
     * @param int $refundAmount
     */
    public function setRefundAmount(int $refundAmount): void
    {
        $this->refundAmount = $refundAmount;
    }

    /**
     * @return array|null
     */
    public function getProductId(): ?array
    {
        return $this->productId;
    }

    /**
     * @param array|null $productId
     */
    public function setProductId(?array $productId): void
    {
        $this->productId = $productId;
    }

    /**
     * @return string
     */
    public function getPaynowStatus(): string
    {
        return $this->paynowStatus;
    }

    /**
     * @param string $paynowStatus
     */
    public function setPaynowStatus(string $paynowStatus): void
    {
        $this->paynowStatus = $paynowStatus;
    }




}