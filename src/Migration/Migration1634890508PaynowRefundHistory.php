<?php declare(strict_types=1);

namespace Crehler\PayNowPayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1634890508PaynowRefundHistory extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1634890508;
    }

    public function update(Connection $connection): void
    {
        try{
            $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `paynow_refund_history` (
            `id` BINARY(16) NOT NULL,
            `transaction_id` BINARY(16) NOT NULL,
            `transaction_version_id` BINARY(16) NOT NULL,
            `refund_id` VARCHAR(1024) NOT NULL,
            `paynow_status` VARCHAR(1024) NULL,
            `refund_amount` FLOAT NOT NULL,
            `product_list` JSON NOT NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            
            PRIMARY KEY (`id`),
            CONSTRAINT `fk.paynow_refund_history.transaction_id` FOREIGN KEY (`transaction_id`) REFERENCES `order_transaction`(`id`) ON DELETE CASCADE
            
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        } catch (\Throwable $e){
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
