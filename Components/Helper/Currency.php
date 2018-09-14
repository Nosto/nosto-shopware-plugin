<?php
/**
 * Copyright (c) 2018, Nosto Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2018 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use Shopware_Plugins_Frontend_NostoTagging_Bootstrap as Bootstrap;
use Nosto\Object\ExchangeRateCollection;
use Nosto\Object\Signup\Account;
use Nosto\Object\ExchangeRate;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Shop\Currency;
use Nosto\Object\Format;

/**
 * Helper class for Currency
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Helper_Currency
{
    const CURRENCY_SYMBOL_LEFT = 32;
    const CURRENCY_SYMBOL_RIGHT = 16;
    const CURRENCY_SYMBOL_DEFAULT = 0;
    const CURRENCY_DECIMAL_CHAR = ',';
    const CURRENCY_GROUPING_CHAR = '.';
    const CURRENCY_DECIMAL_PRECISION = 2;

    /**
     * Update exchange rates for the given shop.
     *
     * @param Account $account
     * @param Shop $shop
     * @return bool
     */
    public static function updateCurrencyExchangeRates(Account $account, Shop $shop)
    {
        $currencyService = new \Nosto\Operation\SyncRates($account);
        $collection = self::buildExchangeRatesCollection($shop);
        try {
            return $currencyService->update($collection);
        } catch (\Exception $e) {
            /** @noinspection PhpUndefinedMethodInspection */
            Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error(
                'Failed to update exchange rates: '.
                $e->getMessage()
            );
            return false;
        }
    }

    /**
     * @param Shop $shop
     * @return ExchangeRateCollection
     */
    public static function buildExchangeRatesCollection(Shop $shop)
    {
        $currencies = $shop->getCurrencies();
        $collection = new ExchangeRateCollection();
        foreach ($currencies as $currency) {
            $rate = new ExchangeRate($currency->getCurrency(), (string)$currency->getFactor());
            $collection->addRate($currency->getCurrency(), $rate);
        }
        return $collection;
    }

    /**
     * @param Shop $shop
     * @return string
     */
    public static function getCurrencyCode(Shop $shop)
    {
        if (self::isMultiCurrencyEnabled()) {
            // Return currency code
            $defaultCurrency = self::getDefaultCurrency($shop);
            if ($defaultCurrency) {
                return $defaultCurrency->getCurrency();
            }
        }
        return $shop->getCurrency()->getCurrency();
    }

    /**
     * @param Shop $shop
     * @return mixed|null|\Shopware\Models\Shop\Currency
     */
    public static function getDefaultCurrency(Shop $shop)
    {
        $currencies = $shop->getCurrencies();
        if ($currencies) {
            foreach ($currencies as $currency) {
                if ($currency->getDefault()) {
                    return $currency;
                }
            }
        }
        return null;
    }

    /**
     * Wrapper that returns if multi-currency is enabled in Shopware backend.
     * If no shop object is given, will use the shop from the frontend request
     *
     * @param Shop|null $shop
     * @return bool
     */
    public static function isMultiCurrencyEnabled(Shop $shop = null)
    {
        if ($shop === null) {
            $shop = Shopware()->Shop();
        }
        $shopConfig = Shopware()->Container()
            ->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('NostoTagging', $shop);
        return $shopConfig[Bootstrap::CONFIG_MULTI_CURRENCY] !== Bootstrap::CONFIG_MULTI_CURRENCY_DISABLED;
    }

    /**
     * @param Shop $shop
     * @return array
     */
    public static function getFormattedCurrencies(Shop $shop)
    {
        $currencies = $shop->getCurrencies();
        $formattedCurrencies = array();
        foreach ($currencies as $currency) {
            $formattedCurrencies[$currency->getCurrency()] = new Format(
                self::getCurrencyBeforeAmount($currency),
                $currency->getSymbol(),
                self::CURRENCY_DECIMAL_CHAR,
                self::CURRENCY_GROUPING_CHAR,
                self::CURRENCY_DECIMAL_PRECISION
            );
        }
        return $formattedCurrencies;
    }

    /**
     * @param Currency $currency
     * @return bool|null
     */
    public static function getCurrencyBeforeAmount(Currency $currency)
    {
        switch ($currency->getSymbolPosition()) {
            case self::CURRENCY_SYMBOL_LEFT:
                return true;
            case self::CURRENCY_SYMBOL_RIGHT:
                return false;
            case self::CURRENCY_SYMBOL_DEFAULT:
                // Shopware's default is after the amount
                return false;
            default:
                /** @noinspection PhpUndefinedMethodInspection */
                Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->warning(
                    'Failed to get currency symbol position, setting as after amount'
                );
                return false;
        }
    }
}
