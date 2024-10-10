<?php declare(strict_types=1);

namespace Crehler\PayNowPayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1689580348PaynowRefundHistoryUpdate extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1689580348;
    }

    public function update(Connection $connection): void
    {
        try{
            $connection->executeStatement("
            ALTER TABLE `paynow_refund_history`
                ADD `version_id` BINARY(16) NOT NULL AFTER `id`,
                ADD PRIMARY KEY `id_version_id` (`id`, `version_id`),
                DROP INDEX `PRIMARY`;
            ALTER TABLE `paynow_refund_history`
                DROP FOREIGN KEY `fk.paynow_refund_history.transaction_id`;
			ALTER TABLE `paynow_refund_history`
                ADD CONSTRAINT `fk.paynow_refund_history.transaction_id` FOREIGN KEY (`transaction_id`, `transaction_version_id`)
		            REFERENCES `order_transaction`(`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ");
        } catch (\Throwable $e){
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
