<?php

class Hic_Integration_Block_Html_Head extends Mage_Page_Block_Html_Head
{
	protected function _afterToHtml($html)
	{
		//prepend Hic block output
		$block = Mage::app()->getLayout()->createBlock('integration/template','hiconversion.head.tag');
		$block->setTemplate('hic/head.phtml');
		return $block->toHtml() . $html;
	}
}