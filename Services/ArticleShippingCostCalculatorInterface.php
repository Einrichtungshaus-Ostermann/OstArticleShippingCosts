<?php declare(strict_types=1);

/**
 * Einrichtungshaus Ostermann GmbH & Co. KG - Article Shipping Costs
 *
 * @package   OstArticleShippingCosts
 *
 * @author    Tim Windelschmidt <tim.windelschmidt@ostermann.de>
 * @copyright 2018 Einrichtungshaus Ostermann GmbH & Co. KG
 * @license   proprietary
 */

namespace OstArticleShippingCosts\Services;

use Shopware\Models\Article\Detail;

interface ArticleShippingCostCalculatorInterface
{
    /**
     * @param Detail $articleDetails
     *
     * @return float|int|null
     */
    public function getShippingCosts($articleDetails);
}
