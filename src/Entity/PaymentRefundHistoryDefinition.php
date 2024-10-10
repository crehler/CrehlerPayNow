<?php


namespace Crehler\PayNowPayment\Entity;


use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PaymentRefundHistoryDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'paynow_refund_history';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }
    public function getEntityClass(): string
    {
        return PaymentRefundHistoryEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PaymentRefundHistoryCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new VersionField())->addFlags(new ApiAware()),
            (new FkField('transaction_id', 'transactionId', OrderTransactionDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new ReferenceVersionField(self::class, 'transaction_version_id'))->addFlags(new ApiAware(), new Required()),
            (new StringField('refund_id', 'refundId', 1024))->addFlags(new ApiAware(), new Required()),
            (new StringField('paynow_status', 'paynowStatus', 1024))->addFlags(new ApiAware()),
            (new FloatField('refund_amount', 'refundAmount'))->addFlags(new ApiAware(), new Required()),
            (new JsonField('product_list', 'productList'))->addFlags(new ApiAware()),
            (new CreatedAtField())->addFlags(new ApiAware()),
            (new UpdatedAtField())->addFlags(new ApiAware()),

            (new ManyToOneAssociationField('transaction', 'transaction_id',
                OrderTransactionDefinition::class, 'id',  false))->addFlags(new ApiAware()),
        ]);
    }

}