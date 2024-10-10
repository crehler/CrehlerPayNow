<?php

namespace Crehler\PayNowPayment\Common;


class OrderAmountFormat
{
    public static function floatToInt($value): int
    {
        return intval(round($value * 100,2));
    }

    public static function intToFloat($value): float
    {
        return floatval(round($value / 100,2));
    }

    public static function round($value): float
    {
        return self::intToFloat(self::floatToInt($value));
    }
}
