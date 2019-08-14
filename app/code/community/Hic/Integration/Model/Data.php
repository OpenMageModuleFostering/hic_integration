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
 * Integration data model
 *
 * @category Hic
 * @package Integration
 * @author HiConversion <support@hiconversion.com>
 */
class Hic_Integration_Model_Data extends Varien_Object
{
    protected $_version = '1.0';
    protected $_platform = 'magento';

    const CATALOG_URL = 'catalog/product/';

    /**
     * Runs module
     */
    protected function _construct()
    {
        $this
            ->setVersion($this->_version)
            ->setPlatform($this->_platform)
            ->setPid($this->helper()->getSiteId())
            ->_initPage()
            ->_initUser()
            ->_initCart();
        if ($this->helper()->isProduct()) {
            $this->_initProduct();
        }
        if ($this->helper()->isConfirmation()) {
            $this->_initOrder();
        }
    }

    /**
     * Return JSON string representing data object
     *
     * @return string
     */
    public function hicData()
    {
        $data = $this->toArray(array('page', 'cart', 'user','tr','version','platform','pid','product'));
        $data = array_filter($data);
        $obj = new Varien_Object($data);
        if ($obj && $data) {
            return Zend_Json::encode($obj->getData());
        }
    }

    /**
     * Returns product information for each product
     * passed into function
     *
     * @param $items
     * @return array
     */
    protected function _getCartItems($items)
    {
        $data = array();
        foreach ($items as $i) {
            $product = Mage::getModel('catalog/product')->load($i->getProductId());
            if ($product) {
                $info = array();
                $info['tt'] = (float)$i->getRowTotalInclTax();
                $info['ds'] = (float)$i->getDiscountAmount();
                $info['qt'] = (float)$i->getQty();
                $info['id'] = $product->getId();
                $info['url'] = $product->getProductUrl();
                $info['nm'] = $product->getName();
                $info['bpr'] = (float)$product->getPrice();
                $info['pr'] = (float)$product->getFinalPrice();
                $info['desc'] = strip_tags($product->getShortDescription());
                $info['img'] = $product->getImageUrl();
                $info['sku'] = $product->getSku();
                $info['cat'] = $product->getCategoryIds();
                $data[] = $info;
            }
        }
        return $data;
    }

    /**
     * Returns page route and breadcrumb info
     *
     * @return array $this
     */
    protected function _initPage()
    {
        $crumb = array();
        foreach (Mage::helper('catalog')->getBreadcrumbPath() as $item) {
            $crumb[] = $item['label'];
        }
        $this->setPage(array(
            'route' => $this->helper()->getRoute(),
            'bc' => $crumb
        ));
        return $this;
    }

    /**
     * Returns cart information
     *
     * @return array $this
     */
    protected function _initCart()
    {
        $cart = Mage::getModel('checkout/cart')->getQuote();
        if ($cart->getItemsCount() > 0) {
            $data = array();
            if ($cart->getId()) {
                $data['id'] = (string)$cart->getId();
            }
            if ($cart->getSubtotal()) {
                $data['st'] = (float)$cart->getSubtotal();
            }
            if ($cart->getGrandTotal()) {
                $data['tt'] = (float)$cart->getGrandTotal();
            }
            if ($cart->getItemsCount()) {
                $data['qt'] = (float)$cart->getItemsCount();
            }
            if (Mage::app()->getStore()->getCurrentCurrencyCode()) {
                $data['cu'] = Mage::app()->getStore()->getCurrentCurrencyCode();
            }
            $data['li'] = $this->_getCartItems($cart->getAllVisibleItems());
            $this->setCart($data);
            return $this;
        }
    }

    /**
     * Returns user information
     *
     * @return array $this
     */
    protected function _initUser()
    {
        $session = Mage::helper('customer');
        $customer = $session->getCustomer();
        $data = array();
        if ($customer) {
            $data['auth'] = $session->isLoggedIn();
            $data['ht'] = false;
            $data['nv'] = true;
            $data['cg'] = Mage::getSingleton('customer/session')->getCustomerGroupId();
            $data['sid'] = Mage::getSingleton("core/session")->getEncryptedSessionId();
            if ($customer->getId()) {
                // Determine if customer has transacted or not.  Must be logged in.
                $orders = Mage::getModel('sales/order')->getCollection();
                $orders->addAttributeToFilter('customer_id',$customer->getId());
                if ($orders){
                    $data['ht'] = $orders->getSize() > 0;
                }
                if ($customer->getDob()) {
                    $data['bday'] = $customer->getDob();
                }
                if ($customer->getGender()) {
                    $data['gndr'] = $customer->getGender();
                }
                if ($customer->getEmail()) {
                    $data['email'] = $customer->getEmail();
                }
                $data['id'] = $customer->getId();
                $data['nv'] = false;
                $data['nm'] = trim($customer->getFirstname() . ' ' . $customer->getLastname());
                $data['since'] = $customer->getCreatedAt(); // yyyy-mm-dd hh:mm:ss+01:00
            }
            $this->setUser($data);
            return $this;
        }
    }

    /**
     * Returns transaction information
     *
     * @return array $this
     */
    protected function _initOrder()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        if (!$orderId) {
            return false;
        }
        $order = Mage::getModel('sales/order')->load($orderId);
        $transaction = array();
        if ($order) {
            if ($order->getIncrementId()) {
                $transaction['id'] = $order->getIncrementId();
            }
            if (Mage::app()->getStore()->getCurrentCurrencyCode()) {
                $transaction['cu'] = Mage::app()->getStore()->getCurrentCurrencyCode();
            }
            if ($order->getSubtotal()) {
                $transaction['st'] = (float)$order->getSubtotal();
            }
            if ($order->getTaxAmount()) {
                $transaction['tx'] = (float)$order->getTaxAmount();
            }
            if ($order->getPayment()->getMethodInstance()->getTitle()) {
                $transaction['type'] = $order->getPayment()->getMethodInstance()->getTitle();
            }
            if ($order->getGrandTotal()) {
                $transaction['tt'] = (float)$order->getGrandTotal();
            }
            if ($order->getCouponCode()) {
                $transaction['coup'] = array($order->getCouponCode());
            }
            if ($order->getDiscountAmount() > 0) {
                $transaction['ds'] = -1 * $order->getDiscountAmount();
            }
            $transaction['li'] = $this->_getCartItems($order->getAllVisibleItems());
            $transaction['sh'] = (float)$order->getShippingAmount();
            $transaction['shm'] = $order->getShippingMethod() ? $order->getShippingMethod() : '';
            $this->setTr($transaction);
            return $this;
        }
    }

    /**
     * Returns product information
     *
     * @return array $this
     */
    protected function _initProduct()
    {
        if ($product = Mage::registry('current_product')) {
            $data['cat'] = $product->getCategoryIds();
            $data['id']  = $product->getId();
            $data['nm']  = $product->getName();
            $data['url'] = $product->getProductUrl();
            $data['sku'] = $product->getSku();
            $data['img'] = Mage::getBaseUrl('media') . self::CATALOG_URL . $product->getImage();
            $this->setProduct($data);
            return $this;
        }
    }

    protected function helper()
    {
        return Mage::helper('integration');
    }
}