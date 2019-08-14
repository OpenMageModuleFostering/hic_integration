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
    protected $_version = '1.1';
    protected $_platform = 'magento';

    const CATALOG_URL = 'catalog/product/';

    /**
     * Class constructor
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
     * Returns product information for each product
     * passed into function
     *
     * @param array $items
     * @return array $data
     */
    protected function _getCartItems($items)
    {
        $data = array();

        // build list of product IDs from either cart or transaction object.
        $productIds = array();
        foreach ($items as $item) {
            $productIds[] = $item->getProduct()->getId();
        }

        // request item information from product collection catalog
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->addFieldToFilter('entity_id', array('in' => $productIds ))
            ->addAttributeToSelect(array('name','description'));
        $count = 0;

        foreach ($collection as $product) {
            $info = array();
            $info['ds'] = (float)$items[$count]->getDiscountAmount();
            $info['tx'] = (float)$items[$count]->getTaxAmount();
            $info['qt'] = (float)$items[$count]->getQty();
            $info['pr'] = (float)$items[$count]->getRowTotalInclTax();
            $info['bpr'] = (float)$items[$count]->getPrice();
            if ($this->helper()->isConfirmation()) {
                $info['qt'] = (float)$items[$count]->getQtyOrdered();
            }
            $info['desc'] = strip_tags($product->getDescription());
            $info['id'] = $product->getId();
            $info['url'] = $product->getProductUrl();
            $info['nm'] = $product->getName();
            $info['img'] = $product->getImageUrl();
            $info['sku'] = $product->getSku();
            $info['cat'] = $product->getCategoryIds();
            $data[] = $info;
            $count = $count + 1;
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
        $this->setPage(
            array(
                'route' => $this->helper()->getRoute(),
                'bc' => $crumb
            )
        );
        return $this;
    }

    /**
     * Returns cart information
     *
     * @return array $this
     */
    protected function _initCart()
    {
        $cart = Mage::getModel('checkout/cart');
        $cartQuote = $cart->getQuote();
        if ($cartQuote->getItemsCount() > 0) {
            $data = array();
            if ($cartQuote->getId()) {
                $data['id'] = (string)$cartQuote->getId();
            }
            if ($cartQuote->getSubtotal()) {
                $data['st'] = (float)$cartQuote->getSubtotal();
            }
            if ($cartQuote->getGrandTotal()) {
                $data['tt'] = (float)$cartQuote->getGrandTotal();
            }
            if ($cartQuote->getItemsCount()) {
                $data['qt'] = (float)$cartQuote->getItemsCount();
            }
            if (Mage::app()->getStore()->getCurrentCurrencyCode()) {
                $data['cu'] = Mage::app()->getStore()->getCurrentCurrencyCode();
            }
            $data['li'] = $this
                ->_getCartItems($cartQuote->getAllVisibleItems());
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
            $data['cg'] = Mage::getSingleton('customer/session')
                ->getCustomerGroupId();
            if ($customer->getId()) {
                $orders = Mage::getModel('sales/order')->getCollection();
                $orders->addAttributeToFilter('customer_id', $customer->getId());
                if ($orders) {
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
                $data['since'] = $customer->getCreatedAt();
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
            $transaction['li'] = $this
                ->_getCartItems($order->getAllVisibleItems());
            $transaction['sh'] = (float)$order->getShippingAmount();
            $transaction['shm'] = $order->getShippingMethod()
                ? $order->getShippingMethod() : '';
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
            $data['img'] = Mage::getBaseUrl('media')
                . self::CATALOG_URL . $product->getImage();
            $this->setProduct($data);
            return $this;
        }
    }

    /**
     * Helper reference
     *
     * @return Mage_Core_Helper_Abstract
     */
    protected function helper()
    {
        return Mage::helper('integration');
    }
}