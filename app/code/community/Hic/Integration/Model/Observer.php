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
 * Integration observer model
 *
 * @category Hic
 * @package Integration
 * @author HiConversion <support@hiconversion.com>
 */
class Hic_Integration_Model_Observer
{
    protected static $_isHead = false;
    protected static $_isRendered = false;
    protected static $_clearCache = false;

    /**
     * Is Enabled Full Page Cache
     *
     * @var bool
     */
    protected $_isEnabled;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_isEnabled = Mage::app()->useCache('full_page');
    }

    /**
     * Check if full page cache is enabled
     *
     * @return bool
     */
    public function isCacheEnabled()
    {
        return $this->_isEnabled;
    }

    /**
     * Check 'controller_action_layout_render_before' event response
     * type to determine if it is a HTML page response
     *
     * @return $this
     */
    public function checkResponseType()
    {
        if (Mage::app()->getLayout()->getBlock('head')) {
            self::$_isHead = true;
        }
        return $this;
    }

    /**
     * Intercept 'controller_action_postdispatch' event response
     * to inject block at top of head
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function interceptResponse(Varien_Event_Observer $observer)
    {
        if (self::$_isHead && !self::$_isRendered) {
            $layout = Mage::getSingleton('core/layout');
            $tag = $layout
                ->createBlock('integration/tag', 'hic.integration.tag')
                ->setTemplate('hic/head.phtml')
                ->toHtml();
            $response = $observer->getEvent()
                ->getControllerAction()
                ->getResponse();
            $openHeadTag = '<head>';
            $pos = strpos($response, $openHeadTag);
            if ($pos !== false && $layout && $tag && $response) {
                $newStr = substr_replace($response, $tag, $pos + strlen($openHeadTag), 0);
                $response->setBody($newStr);
                self::$_isRendered = true;
            }
        }
        return $this;
    }


    /**
     * Clear placeholder cache for
     *
     * @return $this
     */
    public function flushCache()
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }
        if (self::$_clearCache == false) {
            $cacheId = Hic_Integration_Model_Container_Cache::getCacheId();
            Enterprise_PageCache_Model_Cache::getCacheInstance()
                ->remove($cacheId);
            self::$_clearCache = true;
        }
        return $this;
    }
}
