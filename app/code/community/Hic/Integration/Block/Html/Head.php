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
 * Integration html head block
 *
 * @category Hic
 * @package Integration
 * @author HiConversion <support@hiconversion.com>
 */
class Hic_Integration_Block_Html_Head extends Mage_Page_Block_Html_Head
{
    /**
     * Prepends HiC header template to head.
     *
     * @params string
     * @return string
     */
	protected function _afterToHtml($html)
	{
		// prepend Hic block output
		$block = Mage::app()->getLayout()->createBlock('integration/template','hiconversion.head.tag');
		$block->setTemplate('hic/head.phtml');
		return $block->toHtml() . $html;
	}
}