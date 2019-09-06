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

abstract class Adapter
{
    /**
     * @var array
     */
    protected $shippingCosts = [];

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * Calculation of package costs is always the same for every shop
     * so we can implement it here.
     *
     * @param Detail $articleDetails
     * @param array  $attributes
     *
     * @return float
     */
    protected function calculatePackage(Detail $articleDetails, array $attributes)
    {
        // weight > 30kg
        if ($articleDetails->getWeight() >= 30) {
            // at least one dimension > 120cm
            if ($articleDetails->getHeight() >= 120 || $articleDetails->getWidth() >= 120 || $articleDetails->getLen() >= 120) {
                return $this->shippingCosts['P'][30][120];
            }

            // below 120cm
            return $this->shippingCosts['P'][30][0];
        }

        // weight < 30kg and at least one dimension > 120cm
        if ($articleDetails->getHeight() >= 120 || $articleDetails->getWidth() >= 120 || $articleDetails->getLen() >= 120) {
            return $this->shippingCosts['P'][0][120];
        }

        // below 30kg and below 120cm
        return $this->shippingCosts['P'][0][0];
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
        // ...
        return 0.0;
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
        // ...
        return false;
    }

    /**
     * ...
     *
     * @param string $value
     *
     * @return array
     */
    protected function parseExplode(string $value): array
    {
        // ...
        $explode = explode("\n", $value);

        // valid entries
        $arr = [];

        // ...
        foreach ($explode as $aktu) {
            // ...
            $aktu = trim($aktu);

            // ...
            if (!empty($aktu)) {
                // ...
                array_push($arr, $aktu);
            }
        }

        // return it
        return $arr;
    }

    /**
     * ...
     *
     * @param string $value
     * @param array  $input
     * @param bool   $fullTextSearch
     *
     * @return bool
     */
    protected function match(string $value, array $input, bool $fullTextSearch = true): bool
    {
        // force lower case for everything
        $value = strtolower($value);

        // loop every input
        foreach ($input as $str) {
            // lower it as well
            $str = strtolower($str);

            // do we want full text search?
            if ($fullTextSearch === true) {
                // is our search a substring?
                if (substr_count($str, $value) > 0) {
                    // it is free
                    return true;
                }
            } else {
                // has to be exactly the same
                if ($str === $value) {
                    // also free
                    return true;
                }
            }
        }

        // not matched
        return false;
    }

    /**
     * ...
     *
     * @param string $supplier
     * @param array  $input
     * @param bool   $fullTextSearch
     *
     * @return bool
     */
    protected function matchSupplier(string $supplier, array $input, bool $fullTextSearch = true): bool
    {
        // ...
        return $this->match($supplier, $input, $fullTextSearch);
    }

    /**
     * ...
     *
     * @param string $hwg
     * @param string $uwg
     * @param array  $input
     *
     * @return bool
     */
    protected function matchHwgUwg(string $hwg, string $uwg, array $input): bool
    {
        // loop every hwg-uwg
        foreach ($input as $hwgUwg) {
            // split and set it
            $split = explode('-', $hwgUwg);
            $matchHwg = (string) $split[0];
            $matchUwg = (string) (isset($split[1])) ? $split[1] : '';

            // is this a negation?
            if (substr($matchHwg, 0, 1) === "!") {
                // ignore it
                continue;
            }
            
            // we definitly need same hwg
            if ($matchHwg === $hwg) {
                // no need for uwg?!
                if ($matchUwg === 'x' || $matchUwg === '') {
                    // check for negative hwg
                    if ($this->checkNegativeHwg($hwg, $uwg, $input) === true) {
                        // free shipping
                        return false;
                    }

                    // not negated anywwhere -> its free
                    return true;
                }

                // same uwg?!
                if ($matchUwg === $uwg) {
                    // check for negative hwg
                    if ($this->checkNegativeHwg($hwg, $uwg, $input) === true) {
                        // free shipping
                        return false;
                    }
                }
            }
        }

        // nothing matched...
        return false;
    }

    /**
     * Checks if the HWG and UWG is anywhere negated within the input.
     * Returns true if we found the HWG-UWG combination within any negation.
     * Returns false if we havent found the HWG-UWG combination within any negation.
     *
     * @param string $articleHwg
     * @param string $articleUwg
     * @param array  $input
     *
     * @return bool
     */
    private function checkNegativeHwg(string $articleHwg, string $articleUwg, array $input): bool
    {
        // loop every hwg-uwg
        foreach ($input as $hwgUwg) {
            // split and set it
            $split = explode('-', $hwgUwg);
            $hwg = (string) $split[0];
            $uwg = (string) (isset($split[1])) ? $split[1] : '';

            // only negative
            if (substr($hwg, 0, 1) !== '!') {
                // next
                continue;
            }

            // remove negative char
            $hwg = str_replace('!', '', $hwg);

            // is this exactly the same?
            if ($articleHwg === $hwg && $articleUwg === $uwg) {
                // well... this one is denied
                return true;
            }
        }

        // is it not in any negative clause
        return false;
    }
}
