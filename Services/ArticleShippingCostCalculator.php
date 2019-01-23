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

class ArticleShippingCostCalculator implements ArticleShippingCostCalculatorInterface
{
    /**
     * @var array
     */
    private $shippingCosts = array(
        'G' => array(
            65 => null,
            30 => null,
            0 => null
        ),
        'P' => array(
            30 => array(
                120 => null,
                0 => null
            ),
            0 => array(
                120 => null,
                0 => null
            )
        )
    );

    /**
     * @var array
     */
    private $freeShipping = array(
        'hwg-uwg' => array(),
        'supplier' => array()
    );

    /**
     * @var array
     */
    private $configuration = array(
        'freeShippingSupplierFullTextSearch' => null,
        'attributeShippingCosts' => null,
        'attributeIwmHwg' => null,
        'attributeIwmUwg' => null,
        'attributeIwmShippingCosts' => null,
        'attributeIwmFullService' => null,
        'attributeIwmShippingType' => null
    );

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
        $this->freeShipping['hwg-uwg'] = explode( "\n", $configuration['freeShippingHwgUwg'] );
        $this->freeShipping['supplier'] = explode( "\n", $configuration['freeShippingSupplier'] );

        // set shipping costs
        $this->shippingCosts['G'][65] = (float) $configuration['costsGWeight65'];
        $this->shippingCosts['G'][30] = (float) $configuration['costsGWeight30'];
        $this->shippingCosts['G'][0] = (float) $configuration['costsGWeight0'];
        $this->shippingCosts['P'][30][120] = (float) $configuration['costsGWeight30Dimension120'];
        $this->shippingCosts['P'][30][0] = (float) $configuration['costsGWeight30Dimension0'];
        $this->shippingCosts['P'][0][120] = (float) $configuration['costsGWeight0Dimension120'];
        $this->shippingCosts['P'][0][0] = (float) $configuration['costsGWeight0Dimension0'];
    }

    /**
     * ...
     *
     * @param Detail $articleDetails
     *
     * @return float
     */
    public function getShippingCosts($articleDetails)
    {
        // get data
        $attributes = Shopware()->Models()->toArray( $articleDetails->getAttribute() );
        $supplier = $articleDetails->getArticle()->getSupplier()->getName();

        // is this article free shipping?
        if ( $this->isFreeShipping( $articleDetails, $attributes, $supplier ) )
            // free please
            return 0.0;

        // is this a fullservice article?
        if ( (int) $attributes[$this->configuration['attributeIwmFullService']] == 2)
            // free shipping
            return 0.0;

        // do we have shipping costs from iwm?!
        if ( (float) $attributes[$this->configuration['attributeIwmShippingCosts']] > 0)
            // return those shipping costs
            return (float) $attributes[$this->configuration['attributeIwmShippingCosts']];

        // hard coded stuff... by shipping type (P,G) first
        if ( $attributes[$this->configuration['attributeIwmShippingType']] == "G" )
        {
            if ( $articleDetails->getWeight() >= 65 )
                return $this->shippingCosts['G'][65];

            if ( $articleDetails->getWeight() >= 30 )
                return $this->shippingCosts['G'][30];

            return $this->shippingCosts['G'][0];

        }

        // now we are P and weight > 30
        if ( $articleDetails->getWeight() >= 30 )
        {
            if ( $articleDetails->getHeight() >= 120 || $articleDetails->getWidth() >= 120 || $articleDetails->getLen() >= 120 )
                return $this->shippingCosts['P'][30][120];

            return $this->shippingCosts['P'][30][0];
        }

        // p and weight < 30
        if ( $articleDetails->getHeight() >= 120 || $articleDetails->getWidth() >= 120 || $articleDetails->getLen() >= 120 )
            return $this->shippingCosts['P'][0][120];

        return $this->shippingCosts['P'][0][0];
    }

    /**
     * ...
     *
     * @param Detail $articleDetails
     * @param array $attributes
     * @param string $supplier
     *
     * @return boolean
     */
    private function isFreeShipping( Detail $articleDetails, array $attributes, string $supplier)
    {
        // force lower case for everything
        $supplier = strtolower( $supplier );

        // loop every supplier
        foreach ( $this->freeShipping['supplier'] as $free )
        {
            // lower it as well
            $free = strtolower( $free );

            // do we want full text search?
            if ( $this->configuration['freeShippingSupplierFullTextSearch'] == true )
            {
                // is our search a substring?
                if ( substr_count( $free, $supplier ) > 0 )
                    // it is free
                    return true;
            }
            else
            {
                // has to be exactly the same
                if ( $free == $supplier )
                    // also free
                    return true;
            }
        }

        // loop every hwg-uwg
        foreach ( $this->freeShipping['hwg-uwg'] as $hwgUwg )
        {
            // split and set it
            $split = explode( "-", $hwgUwg );
            $hwg = (string) $split[0];
            $uwg = (string) ( isset( $split[1] ) ) ? $split[1] : "";

            // we definitly need same hwg
            if ( $hwg == $attributes[$this->configuration['attributeIwmHwg']] ) {
                // no need for uwg?!
                if ( $uwg == "x" || $uwg == "" ) {
                    // check for negative hwg
                    if ( $this->checkNegativeHwg($attributes[$this->configuration['attributeIwmHwg']], $attributes[$this->configuration['attributeIwmUwg']]) == true )
                        // free shipping
                        return true;
                }

                // same uwg?!
                if ( $uwg == $attributes[$this->configuration['attributeIwmUwg']] ) {
                    // check for negative hwg
                    if ( $this->checkNegativeHwg($attributes[$this->configuration['attributeIwmHwg']], $attributes[$this->configuration['attributeIwmUwg']]) == true )
                        // free shipping
                        return true;
                }
            }
        }

        // nothing matched...
        return false;
    }

    /**
     * ...
     *
     * @param string $articleHwg
     * @param string $articleUwg
     *
     * @return boolean
     */
    private function checkNegativeHwg(string $articleHwg, string $articleUwg)
    {
        // loop every hwg-uwg
        foreach ( $this->freeShipping['hwg-uwg'] as $hwgUwg )
        {
            // split and set it
            $split = explode( "-", $hwgUwg );
            $hwg = (string) $split[0];
            $uwg = (string) ( isset( $split[1] ) ) ? $split[1] : "";

            // only negative
            if ( substr( $hwg, 0, 1 ) != "!" )
                // next
                continue;

            // remove negative char
            $hwg = str_replace( "!", "", $hwg );

            // is this exactly the same?
            if ($articleHwg == $hwg && $articleUwg == $uwg)
                // well... this one is denied
                return false;
        }

        // is it not in any negative clause
        return true;
    }
}
