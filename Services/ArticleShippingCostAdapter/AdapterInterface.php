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

namespace OstArticleShippingCosts\Services\ArticleShippingCostAdapter;

use Shopware\Models\Article\Detail;

interface AdapterInterface
{
    /**
     * @param Detail $articleDetails
     * @param array  $attributes
     *
     * @return float
     */
    public function getShippingCosts(Detail $articleDetails, array $attributes): float;
}
