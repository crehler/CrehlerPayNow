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

namespace Crehler\PayNowPayment\Utils;

class AmountFormat
{
    public static function floatToInt($value): int
    {
        return intval(round($value * 100, 2));
    }

    public static function intToFloat($value): float
    {
        return floatval(round($value / 100, 2));
    }

    public static function round($value): float
    {
        return self::intToFloat(self::floatToInt($value));
    }
}