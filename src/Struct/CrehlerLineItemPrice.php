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

namespace Crehler\PayNowPayment\Struct;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;

class CrehlerLineItemPrice extends CalculatedPrice
{
    public function getApiAlias(): string
    {
        return static::getExtensionName();
    }

    public static function getExtensionName(): string
    {
        return 'crehler_line_item_price';
    }
}