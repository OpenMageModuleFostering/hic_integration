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
    protected static $isHead = FALSE;
    protected static $isRendered = FALSE;

    /**
     * Check 'controller_action_layout_render_before' event response
     * type to determine if it is a HTML page response
     */
    public function checkResponseType()
    {
        if (Mage::app()->getLayout()->getBlock('head')) {
            self::$isHead = TRUE;
        }
    }

    /**
     * Intercept 'controller_action_postdispatch' event response
     * to inject block at top of head
     *
     * @param Varien_Event_Observer $observer
     */
    public function interceptResponse(Varien_Event_Observer $observer)
    {
        if (self::$isHead && !self::$isRendered) {
            $layout = Mage::getSingleton('core/layout');
            $tag = $layout->createBlock('integration/tag', 'hic.integration.tag')->setTemplate('hic/head.phtml')->toHtml();
            $response = $observer->getEvent()->getControllerAction()->getResponse();
            // TODO: Will this always be <HEAD>
            $openHeadTag = '<head>';
            $pos = strpos($response, $openHeadTag);
            if ($pos !== false && $layout && $tag && $response) {
                $newStr = substr_replace($response, $tag, $pos + strlen($openHeadTag), 0);
                $response->setBody($newStr);
                self::$isRendered = TRUE;
            }
        }
    }
}