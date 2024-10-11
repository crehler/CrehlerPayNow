<?php declare(strict_types=1);

namespace Crehler\PayNowPayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1631693071PaymentToken extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1631693071;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `paynow_payment_tokens` (
            `id` BINARY(16) NOT NULL,
            `token` VARCHAR(1024) NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
