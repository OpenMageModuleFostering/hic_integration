<?php

class Hic_Integration_Model_Container_Template extends Enterprise_PageCache_Model_Container_Advanced_Quote
{

    /**
     * Render block content
     *
     * @return string
     */
    protected function _renderBlock()
    {
        $block = $this->_placeholder->getAttribute('block');
        $template = $this->_placeholder->getAttribute('template');
 
        $block = new $block;
        $block->setTemplate($template);
        $block->setLayout(Mage::app()->getLayout());
 
        return $block->toHtml();
    }

    protected function _saveCache($data, $id, $tags = array(), $lifetime = null)
    {
        return false;
    }
}