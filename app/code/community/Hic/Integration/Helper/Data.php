<?php

class Hic_Integration_Helper_Data extends Mage_Core_Helper_Abstract
{
    const SETTINGS_ENABLED   = 'integration/settings/enabled';
    const SETTINGS_ENABLED_2 = 'integration/settings/enabled_2';
    const SETTINGS_SITE_ID   = 'integration/settings/site_id';

    public function getSiteId()
    {
        return Mage::getStoreConfig(self::SETTINGS_SITE_ID);
    }

    public function isEnabled()
    {
        return Mage::getStoreConfig(self::SETTINGS_ENABLED);
    }

    public function isEnabled2()
    {
        return Mage::getStoreConfig(self::SETTINGS_ENABLED_2);
    }

    public function getHicData()
    {
        return Mage::getModel('integration/data');
    }

    public function getRoute()
    {
        $route = Mage::app()->getFrontController()->getAction()->getFullActionName();
        return $route;
    }

    public function isHomepage()
    {
        return 'cms_index_index' == $this->getRoute();
    }

    public function isContent()
    {
        return 'cms_page_view' == $this->getRoute();
    }

    public function isCategory()
    {
        return 'catalog_category_view' == $this->getRoute();
    }

    public function isSearch(){
        return 'catalogsearch_result_index' == $this->getRoute();
    }

    public function isProduct()
    {
        return 'catalog_product_view' == $this->getRoute();
    }

    public function isCart()
    {
        return 'checkout_cart_index' == $this->getRoute();
    }

    public function isCheckout()
    {
        return 'checkout_onepage_index' == $this->getRoute();
    }

    public function is404()
    {
        return 'cms_index_noRoute' == $this->getRoute();
    }

    public function isConfirmation()
    {
        $request = Mage::app()->getRequest();
        return false !== strpos($request->getRouteName(), 'checkout') && 'success' == $request->getActionName();
    }

}