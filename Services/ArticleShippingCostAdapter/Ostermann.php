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

class Ostermann extends Adapter implements AdapterInterface
{
    /**
     * @var array
     */
    protected $shippingCosts = [
        'G' => [
            65 => null,
            30 => null,
            0  => null
        ],
        'P' => [
            30 => [
                120 => null,
                0   => null
            ],
            0 => [
                120 => null,
                0   => null
            ]
        ]
    ];

    /**
     * @var array
     */
    protected $freeShipping = [
        'hwg-uwg'        => [],
        'supplier'       => [],
        'fullTextSearch' => null
    ];

    /**
     * @var array
     */
    protected $configuration = [
        'attributeShippingCosts'    => null,
        'attributeIwmHwg'           => null,
        'attributeIwmUwg'           => null,
        'attributeIwmShippingCosts' => null,
        'attributeIwmFullService'   => null,
        'attributeIwmShippingType'  => null
    ];

    /**
     * ...
     *
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        // set default configuration
        $this->configuration = $configuration;

        // set free shipping stuff
        $this->freeShipping['hwg-uwg'] = $this->parseExplode($configuration['ostermannFreeShippingHwgUwg']);
        $this->freeShipping['supplier'] = $this->parseExplode($configuration['ostermannFreeShippingSupplier']);
        $this->freeShipping['fullTextSearch'] = (bool) $configuration['ostermannFreeShippingSupplierFullTextSearch'];

        // set shipping costs
        $this->shippingCosts['G'][65] = (float) $configuration['ostermannCostsGWeight65'];
        $this->shippingCosts['G'][30] = (float) $configuration['ostermannCostsGWeight30'];
        $this->shippingCosts['G'][0] = (float) $configuration['ostermannCostsGWeight0'];
        $this->shippingCosts['P'][30][120] = (float) $configuration['ostermannCostsPWeight30Dimension120'];
        $this->shippingCosts['P'][30][0] = (float) $configuration['ostermannCostsPWeight30Dimension0'];
        $this->shippingCosts['P'][0][120] = (float) $configuration['ostermannCostsPWeight0Dimension120'];
        $this->shippingCosts['P'][0][0] = (float) $configuration['ostermannCostsPWeight0Dimension0'];
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingCosts(Detail $articleDetails, array $attributes): float
    {
        // free shipping or what???
        if ($this->isFreeShipping($articleDetails, $attributes)) {
            // yep
            return 0.0;
        }

        // hard coded stuff... by shipping type (P,G) first
        if ($attributes[$this->configuration['attributeIwmShippingType']] === 'G') {
            // do we have shipping costs from iwm?!
            if ((float) $attributes[$this->configuration['attributeIwmShippingCosts']] > 0) {
                // return those shipping costs
                return (float) $attributes[$this->configuration['attributeIwmShippingCosts']];
            }

            // ...
            return $this->calculateTruck($articleDetails, $attributes);
        }

        // if we are inhouse we have to check for iwm shipping costs first
        if (Shopware()->Container()->get("ost_foundation.configuration")['shop'] == "inhouse" && (float) $attributes[$this->configuration['attributeIwmShippingCosts']] > 0) {
            // return shipping costs from iwm
            return (float) $attributes[$this->configuration['attributeIwmShippingCosts']];
        }

        // return by package calculation
        return $this->calculatePackage($articleDetails, $attributes);
    }

    /**
     * ...
     *
     * @param Detail $articleDetails
     * @param array  $attributes
     *
     * @return bool
     */
    protected function isFreeShipping(Detail $articleDetails, array $attributes)
    {
        // free hwg-uwg?
        if ($this->matchHwgUwg($attributes[$this->configuration['attributeIwmHwg']], $attributes[$this->configuration['attributeIwmUwg']], $this->freeShipping['hwg-uwg'])) {
            // freeee
            return true;
        }

        // free supplier?
        if ($this->matchSupplier($articleDetails->getArticle()->getSupplier()->getName(), $this->freeShipping['supplier'], $this->freeShipping['fullTextSearch'])) {
            // free please
            return true;
        }

        // is this a fullservice article?
        if ((int) $attributes[$this->configuration['attributeIwmFullService']] === 2) {
            // free shipping
            return true;
        }

        // nope...
        return false;
    }

    /**
     * ...
     *
     * @param Detail $articleDetails
     * @param array  $attributes
     *
     * @return float
     */
    protected function calculateTruck(Detail $articleDetails, array $attributes)
    {
        if ($articleDetails->getWeight() >= 65) {
            return $this->shippingCosts['G'][65];
        }

        if ($articleDetails->getWeight() >= 30) {
            return $this->shippingCosts['G'][30];
        }

        return $this->shippingCosts['G'][0];
    }
}
