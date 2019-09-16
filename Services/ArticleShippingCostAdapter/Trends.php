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

class Trends extends Adapter implements AdapterInterface
{
    /**
     * @var array
     */
    protected $shippingCosts = [
        'G' => [
            1 => [
                'price'    => 0.0,
                'hwg-uwg'  => [],
                'supplier' => [],
                'numbers'  => [],
                'names'    => []
            ],
            2 => [],
            3 => [],
            4 => [],
            5 => []
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
        $this->freeShipping['hwg-uwg'] = $this->parseExplode($configuration['trendsFreeShippingHwgUwg']);
        $this->freeShipping['supplier'] = $this->parseExplode($configuration['trendsFreeShippingSupplier']);
        $this->freeShipping['fullTextSearch'] = (bool) $configuration['trendsFreeShippingSupplierFullTextSearch'];

        // set shipping costs
        $this->shippingCosts['P'][30][120] = (float) $configuration['trendsCostsPWeight30Dimension120'];
        $this->shippingCosts['P'][30][0] = (float) $configuration['trendsCostsPWeight30Dimension0'];
        $this->shippingCosts['P'][0][120] = (float) $configuration['trendsCostsPWeight0Dimension120'];
        $this->shippingCosts['P'][0][0] = (float) $configuration['trendsCostsPWeight0Dimension0'];

        // set g shipping costs
        for ($i = 1; $i <= 5; ++$i) {
            // set this echelon
            $this->shippingCosts['G'][$i] = [
                'price'    => (float) $configuration['trendsCostsGEchelon' . $i . 'Price'],
                'hwg-uwg'  => $this->parseExplode($configuration['trendsCostsGEchelon' . $i . 'HwgUwg']),
                'supplier' => $this->parseExplode($configuration['trendsCostsGEchelon' . $i . 'Supplier']),
                'numbers'  => $this->parseExplode($configuration['trendsCostsGEchelon' . $i . 'Numbers']),
                'names'    => $this->parseExplode($configuration['trendsCostsGEchelon' . $i . 'Names'])
            ];
        }
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
        // get parameters
        $hwg = (string) $attributes[$this->configuration['attributeIwmHwg']];
        $uwg = (string) $attributes[$this->configuration['attributeIwmUwg']];
        $supplier = (string) $articleDetails->getArticle()->getSupplier()->getName();

        // shipping free by shopware flag?!
        if ((int) $articleDetails->getShippingFree() === 1) {
            // yep...
            return true;
        }

        // free hwg-uwg?
        if ($this->matchHwgUwg($hwg, $uwg, $this->freeShipping['hwg-uwg'])) {
            // freeee
            return true;
        }

        // free supplier?
        if ($this->matchSupplier($supplier, $this->freeShipping['supplier'], $this->freeShipping['fullTextSearch'])) {
            // free please
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
        // get parameters
        $hwg = $attributes[$this->configuration['attributeIwmHwg']];
        $uwg = $attributes[$this->configuration['attributeIwmUwg']];
        $supplier = $articleDetails->getArticle()->getSupplier()->getName();

        // we check every article number first
        for ($i = 1; $i <= 5; $i++) {
            // get current scale
            $scale = $this->shippingCosts['G'][$i];

            // ignore invalid
            if ((float) $scale['price'] == 0) {
                // next
                continue;
            }

            // we need at least one match
            if ($this->match($articleDetails->getNumber(), $scale['numbers'])) {
                // return this price
                return $scale['price'];
            }
        }

        // we are g and we loop every echolon
        for ($i = 1; $i <= 5; $i++) {
            // get current scale
            $scale = $this->shippingCosts['G'][$i];

            // ignore invalid
            if ((float) $scale['price'] == 0) {
                // next
                continue;
            }

            // we need at least one match
            if ($this->matchHwgUwg($hwg, $uwg, $scale['hwg-uwg']) || $this->matchSupplier($supplier, $scale['supplier']) || $this->match($articleDetails->getArticle()->getName(), $scale['names'])) {
                // return this price
                return $scale['price'];
            }
        }

        // default is lowest price
        for ($i = 5; $i >= 1; $i--) {
            // get current scale
            $scale = $this->shippingCosts['G'][$i];

            // ignore invalid
            if ((float) $scale['price'] == 0) {
                // next
                continue;
            }

            // return cheapest price
            return $scale['price'];
        }

        // default free
        return 0.0;
    }
}
