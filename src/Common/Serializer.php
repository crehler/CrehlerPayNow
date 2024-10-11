<?php
declare (strict_types=1);

namespace Crehler\PayNowPayment\Common;

use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;

class Serializer
{
    /**
     * @var SymfonySerializer
     */
    private static $serializer;

    private function __construct()
    {
    }

    public static function getSerializer()
    {
        if (!self::$serializer) {
            $encoders = [new JsonEncoder()];
            $normalizers = [new ArrayDenormalizer(), new ObjectNormalizer(null, null, null, new ReflectionExtractor())];

            self::$serializer = new SymfonySerializer($normalizers, $encoders);
        }

        return self::$serializer;
    }
}