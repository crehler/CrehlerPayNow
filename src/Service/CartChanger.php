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

namespace Crehler\PayNowPayment\Service;


use Crehler\PayNowPayment\Struct\CrehlerLineItemPrice;
use Crehler\PayNowPayment\Utils\AmountFormat;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;

class CartChanger
{
    public function change(Cart $cart): Cart
    {
        foreach ($cart->getLineItems() as $lineItem) {
            if (
                $lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE &&
                $lineItem->getType() !== 'crehlerbundle' &&
                $lineItem->getType() !== 'crehlerbundleitem'
            ) {
                continue;
            }
            $lineItem->addExtension(
                CrehlerLineItemPrice::getExtensionName(),
                $this->createCrehlerPriceFormCalculatedPrice($lineItem->getPrice())
            );

            if ($lineItem->hasChildren()) {
                foreach ($lineItem->getChildren() as $child) {
                    if (
                        $child->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE &&
                        $child->getType() !== 'crehlerbundle' &&
                        $child->getType() !== 'crehlerbundleitem'
                    ) {
                        continue;
                    }
                    $child->addExtension(
                        CrehlerLineItemPrice::getExtensionName(),
                        $this->createCrehlerPriceFormCalculatedPrice(
                            new CalculatedPrice(
                                $child->getPrice()?->getUnitPrice(),
                                $child->getPrice()?->getUnitPrice() * $child->getQuantity(),
                                $child->getPrice()?->getCalculatedTaxes(),
                                $child->getPrice()?->getTaxRules(),
                                $child->getQuantity(),
                                null,
                                null,
                                null
                            )
                        )
                    );
                }
            }
        }
        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== LineItem::PROMOTION_LINE_ITEM_TYPE) {
                continue;
            }
            $composition = $lineItem->getPayloadValue('composition');
            if ($composition === null) {
                continue;
            }
            foreach ($composition as $item) {
                if (!array_key_exists('id', $item) || !array_key_exists('quantity', $item) || !array_key_exists('discount', $item)) {
                    continue;
                }
                $lineItem = $cart->getLineItems()->get($item['id']);
                if ($lineItem === null) {
                    continue;
                }
                $currentCrehlerPrice = $lineItem->getExtension(CrehlerLineItemPrice::getExtensionName());
                $currentCrehlerPrice = $this->recalculateCrehlerPrice($currentCrehlerPrice, $item['quantity'], $item['discount']);
                $lineItem->addExtension(CrehlerLineItemPrice::getExtensionName(), $currentCrehlerPrice);

                if (!$lineItem->hasChildren()) {
                    continue;
                }

                $pennyCorrectionCounter = 0;
                foreach ($lineItem->getChildren() as $child) {
                    $childPriceParticipation = ($child->getQuantity() * $child->getPrice()->getUnitPrice()) / $lineItem->getPrice()->getTotalPrice();
                    $currentCrehlerChildPrice = $child->getExtension(CrehlerLineItemPrice::getExtensionName());
                    $currentCrehlerChildPrice = $this->recalculateCrehlerPrice($currentCrehlerChildPrice, $item['quantity'], $item['discount'] * $childPriceParticipation);
                    $pennyCorrectionCounter += $currentCrehlerChildPrice->getTotalPrice();
                    $child->addExtension(CrehlerLineItemPrice::getExtensionName(), $currentCrehlerChildPrice);
                }

                if ($pennyCorrectionCounter === $currentCrehlerPrice->getTotalPrice()) {
                    continue;
                }

                $pennyCorrection = $currentCrehlerPrice->getTotalPrice() - $pennyCorrectionCounter;
                /** @var CrehlerLineItemPrice $currentCrehlerChildPrice */
                $currentCrehlerChildPrice = $lineItem->getChildren()->last()->getExtension(CrehlerLineItemPrice::getExtensionName());
                $child->addExtension(
                    CrehlerLineItemPrice::getExtensionName(),
                    new CrehlerLineItemPrice(
                        $currentCrehlerChildPrice->getQuantity() === 1 ? $currentCrehlerChildPrice->getUnitPrice() + round($pennyCorrection, 2) : $currentCrehlerChildPrice->getUnitPrice(),
                        $currentCrehlerChildPrice->getTotalPrice() + round($pennyCorrection, 2),
                        $currentCrehlerChildPrice->getCalculatedTaxes(),
                        $currentCrehlerChildPrice->getTaxRules(),
                        $currentCrehlerChildPrice->getQuantity(),
                        null,
                        null,
                        null
                    )
                );
            }
        }

        return $cart;
    }

    private function recalculateCrehlerPrice(CrehlerLineItemPrice $crehlerLineItemPrice, int $quantity, float $discount): CrehlerLineItemPrice
    {
        $itemUnitPrice = AmountFormat::floatToInt($crehlerLineItemPrice->getUnitPrice());
        $itemTotalPrice = AmountFormat::floatToInt($crehlerLineItemPrice->getTotalPrice());
        $discount = AmountFormat::floatToInt($discount);
        $unitDiscount = $discount / $quantity;

        $itemUnitPrice = $itemUnitPrice - $unitDiscount;
        $itemTotalPrice = $itemTotalPrice - $discount;


        $newTaxCollection = new CalculatedTaxCollection();

        foreach ($crehlerLineItemPrice->getCalculatedTaxes() as $calculatedTax) {
            $taxRate = $calculatedTax->getTaxRate();
            $vatDivisor = 1 + ($taxRate / 100);
            $vatAmount = $itemTotalPrice - ($itemTotalPrice / (1 + ($calculatedTax->getTaxRate() / 100)));
            $newTaxCollection->add(new CalculatedTax(AmountFormat::intToFloat($vatAmount), $calculatedTax->getTaxRate(), AmountFormat::intToFloat($itemTotalPrice)));
        }

        return new CrehlerLineItemPrice(
            AmountFormat::intToFloat($itemUnitPrice),
            AmountFormat::intToFloat($itemTotalPrice),
            $newTaxCollection,
            $crehlerLineItemPrice->getTaxRules(),
            $crehlerLineItemPrice->getQuantity(),
            null,
            null,
            null
        );
    }

    private function createCrehlerPriceFormCalculatedPrice(?CalculatedPrice $price): ?CrehlerLineItemPrice
    {
        if ($price === null) {
            return null;
        }
        return CrehlerLineItemPrice::createFrom($price);
    }
}