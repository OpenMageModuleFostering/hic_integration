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
    const CATALOG_URL = 'catalog/product/';
    
    /**
     * Class constructor
     */
    protected function _construct()
    {
    }
    
    
    /**
     * Returns category names for each product
     * passed into function
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array $categoryNames
     */
    protected function _getCategoryNames($product)
    {
        $catIds =  $product->getCategoryIds();
        $catCollection = Mage::getResourceModel('catalog/category_collection')
            ->addAttributeToFilter('entity_id', $catIds)
            ->addAttributeToSelect('name')
            ->addIsActiveFilter();
        $categoryNames = array();
        foreach ($catCollection as $category) {
            $categoryNames[] = $category->getName();
        }
        return $categoryNames;
    }

    /**
     * Returns product information for each product
     * passed into function
     *
     * @param array $items
     * @params boolean $isOrder
     * @return array $data
     */
    protected function _getCartItems($items, $isOrder)
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
            ->addAttributeToSelect(array('name','description','sku'));
        $count = 0;

        foreach ($collection as $product) {
            $info = array();
            $info['ds'] = (float)$items[$count]->getDiscountAmount();
            $info['tx'] = (float)$items[$count]->getTaxAmount();
            $info['pr'] = (float)$items[$count]->getRowTotalInclTax();
            $info['bpr'] = (float)$items[$count]->getPrice();
            if ($isOrder) {
                $info['qt'] = (float)$items[$count]->getQtyOrdered();
            } else {
                $info['qt'] = (float)$items[$count]->getQty();
            }
            $info['desc'] = strip_tags($product->getDescription());
            $info['id'] = $product->getId();
            $info['url'] = $product->getProductUrl();
            $info['nm'] = $product->getName();
            $info['img'] = $product->getImageUrl();
            $info['sku'] = $product->getSku();
            $info['cat'] = $this->_getCategoryNames($product);
            $data[] = $info;
            $count = $count + 1;
        }
        return $data;
    }
    
    /**
     * Determines and returns page route
     *
     * @return string
     */
    protected function _getRoute()
    {
        return Mage::app()
            ->getFrontController()
            ->getAction()
            ->getFullActionName();
    }
    
    /**
     * Determines if its a product page or not
     *
     * @return boolean
     */
    public function isProduct()
    {
        return 'catalog_product_view' == $this->_getRoute();
    }

    /**
     * Determines if Confirmation page or not
     *
     * @return boolean
     */
    public function isConfirmation()
    {
        $request = Mage::app()->getRequest();
        return false !== strpos($request->getRouteName(), 'checkout')
            && 'success' == $request->getActionName();
    }

    /**
     * Retrieves page route and breadcrumb info and populates page
     * attribute
     *
     * @return array $this
     */
    public function populatePageData()
    {
        $crumb = array();
        foreach (Mage::helper('catalog')->getBreadcrumbPath() as $item) {
            
            $crumb[] = $item['label'];
        }
        
        $this->setPage(
            array(
                'route' => $this->_getRoute(),
                'bc' => $crumb
            )
        );
        return $this;
    }

    /**
     * Retrieves cart information and populates cart attribute
     *
     * @return array $this
     */
    public function populateCartData()
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
                $data['qt'] = (float)$cartQuote->getItemsQty();
            }
            if (Mage::app()->getStore()->getCurrentCurrencyCode()) {
                $data['cu'] = Mage::app()->getStore()->getCurrentCurrencyCode();
            }
            $data['li'] = $this
                ->_getCartItems($cartQuote->getAllVisibleItems(), false);
            $this->setCart($data);
        }
        return $this;
    }

    /**
     * Retrieves user information and populates user attribute
     *
     * @return array $this
     */
    public function populateUserData()
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
        }
        return $this;
    }

    /**
     * Retrieves order information and populates tr attribute
     *
     * @return array $this
     */
    public function populateOrderData()
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
                ->_getCartItems($order->getAllVisibleItems(), false);
            $transaction['sh'] = (float)$order->getShippingAmount();
            $transaction['shm'] = $order->getShippingMethod()
                ? $order->getShippingMethod() : '';
            $this->setTr($transaction);
        }
        return $this;
    }

    /**
     * Retrieves product information and populates product attribute
     *
     * @return array $this
     */
    public function populateProductData()
    {
        // registry does not exist when we are cached
        if ($product = Mage::registry('current_product')) {
            $data['cat'] = $this->_getCategoryNames($product);
            $data['id']  = $product->getId();
            $data['nm']  = $product->getName();
            $data['url'] = $product->getProductUrl();
            $data['sku'] = $product->getSku();
            $data['bpr'] = $product->getPrice();
            $data['img'] = Mage::getBaseUrl('media')
                . self::CATALOG_URL . $product->getImage();
            $this->setProduct($data);
        }
        return $this;       
    }
    
    

}