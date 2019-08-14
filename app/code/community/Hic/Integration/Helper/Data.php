<?php
/**
 * HiConversion
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * [http://opensource.org/licenses/MIT]
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category Hic
 * @package Hic_Integration
 * @Copyright © 2015 HiConversion, Inc. All rights reserved.
 * @license [http://opensource.org/licenses/MIT] MIT License
 */

/**
 * Integration data helper
 *
 * @category Hic
 * @package Integration
 * @author HiConversion <support@hiconversion.com>
 */
class Hic_Integration_Helper_Data extends Mage_Core_Helper_Abstract
{
    const SETTINGS_ENABLED   = 'integration/settings/enabled';
    const SETTINGS_ENABLED_2 = 'integration/settings/enabled_2';
    const SETTINGS_SITE_ID   = 'integration/settings/site_id';

    /**
     * Returns Site ID from Configuration
     *
     * @return string
     */
    public function getSiteId()
    {
        return Mage::getStoreConfig(self::SETTINGS_SITE_ID);
    }

    /**
     * Determines if module is enabled or not
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return Mage::getStoreConfig(self::SETTINGS_ENABLED);
    }

    /**
     * Determines if CX Optimization is enabled or not
     *
     * @return boolean
     */
    public function isEnabled2()
    {
        return Mage::getStoreConfig(self::SETTINGS_ENABLED_2);
    }

    /**
     * Returns Data model
     *
     * @return object
     */
    public function getHicData()
    {
        return Mage::getModel('integration/data');
    }

    /**
     * Determines and returns page route
     *
     * @return string
     */
    public function getRoute()
    {
        $route = Mage::app()->getFrontController()->getAction()->getFullActionName();
        return $route;
    }

    /**
     * Determines if its a product page or not
     *
     * @return boolean
     */
    public function isProduct()
    {
        return 'catalog_product_view' == $this->getRoute();
    }

    /**
     * Determines if Confirmation page or not
     *
     * @return boolean
     */
    public function isConfirmation()
    {
        $request = Mage::app()->getRequest();
        return false !== strpos($request->getRouteName(), 'checkout') && 'success' == $request->getActionName();
    }

}