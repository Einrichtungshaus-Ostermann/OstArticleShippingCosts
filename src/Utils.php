<?php declare(strict_types=1);

/**
 * Einrichtungshaus Ostermann GmbH & Co. KG - Article Shipping Costs
 *
 * Article Shipping Costs calculator
 *
 * @package   OstArticleShippingCosts
 *
 * @author    Tim Windelschmidt <tim.windelschmidt@ostermann.de>
 * @copyright 2018 Einrichtungshaus Ostermann GmbH & Co. KG
 * @license   proprietary
 */

namespace OstArticleShippingCosts\src;

use Shopware\Models\Article\Detail;

class Utils
{
    /**
     * @param Detail $articleDetails
     *
     * @return float|int|null
     */
    public static function getShippingCosts($articleDetails)
    {
        if ($articleDetails->getShippingFree() === false) {
            if ($articleDetails->getAttribute()->getAttr13() === 'G') {
                if ($articleDetails->getWeight() > 65) {
                    return 59;
                }

                if ($articleDetails->getWeight() > 30) {
                    return 39;
                }

                return 29;
            }

            if ($articleDetails->getAttribute()->getAttr13() === 'P') {
                if ($articleDetails->getAttribute()->getAttr2() < 90) {
                    if ($articleDetails->getWeight() < 30) {
                        if ($articleDetails->getWidth() < 120 && $articleDetails->getHeight() < 120 && $articleDetails->getLen() < 120) {
                            return 4.5;
                        }

                        return 9.5;
                    }

                    if ($articleDetails->getWidth() < 120 && $articleDetails->getHeight() < 120 && $articleDetails->getLen() < 120) {
                        return 9.5;
                    }

                    return 19;
                }

                return 4.5;
            }

            return 4.5;
        }

        return 0;
    }
}
