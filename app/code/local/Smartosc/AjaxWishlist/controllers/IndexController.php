<?php
/**
 * Created by PhpStorm.
 * User: Tungbv
 * Date: 25/07/2014
 * Time: 14:21
 */
Class Smartosc_AjaxWishlist_IndexController extends Mage_Core_Controller_Front_Action
{
    protected function _getWishlist()
    {
        $wishlist = Mage::registry('wishlist');
        if($wishlist){
            return $wishlist;
        }
        try
        {
            $wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer(), true);
            Mage::registry('wishlist',$wishlist);

        }catch (Mage_Core_Exception $e){
            Mage::getSingleton('wishlist/session')->addError($e->getMessage());
        }catch(Exception $e){
            Mage::getSingleton('wishlist/session')->addException($e, Mage::helper('wishlist')->__('Cannot create wishlist'));
        }
        return $wishlist;
    }
    public function addAction()
    {
        $response = array();
//        check wishlist active in admin
        if(!Mage::getStoreConfigFlag('wishlist/general/active')){
            $response['status'] = 'ERROR';
            $response['message'] = $this->__('Wishlist Has Been Disabled By Admin') ;
        }
        //        add product to wishlist for customer
        if(empty($response))
        {
            $session = Mage::getSingleton('customer/session');
            $wishlist = $this->_getWishlist();
            if(!$wishlist){
                $response['status'] = 'ERROR';
                $response['message'] = $this->__('Unable to Create Wishlist');
            }else{
                $productId = (int) $this->getRequest()->getParam('product');
                if(!$productId){
                    $response['status'] = 'ERROR';
                    $response['message'] = 'Product not found';
                }else{
                    $customer = Mage::getSingleton('customer/session')->getCustomer();
                    $wishlistItem = Mage::getModel('wishlist/wishlist')->loadByCustomer($customer, true);
                    $wishListItemCollection = $wishlistItem->getItemCollection();
                    $product = Mage::getModel('catalog/product')->load($productId);
                    foreach ($wishListItemCollection as $item) {
                        $arrId[] = $item->getProductId();
                    }
                    if(in_array($productId, $arrId)) {
                        $response['message'] = $this->__('%1$s already exist in your Wishlist.', $product->getName());
                    }else{

                        try{
                            $requestParams = $this->getRequest()->getParams();
                            $buyRequest = new Varien_Object($requestParams);
                            $referer = $session->getBeforeWishlistUrl();

                            $result = $wishlist->addNewItem($product, $buyRequest);
                            if(is_string($result)){
                                Mage::throwException($result);
                            }
                            $wishlist->save();

                            Mage::dispatchEvent(
                                'wishlist_add_product',
                                array(
                                    'wishlist' => $wishlist,
                                    'product'  => $product,
                                    'item'     => $result
                                )
                            );

                            Mage::helper('wishlist')->calculate();
                            $message =  $this->__('%1$s has been added to your wishlist.', $product->getName(), $referer);
                            $response['status'] = 'SUCCESS';
                            $response['message'] = $message;

                            Mage::unregister('wishlist');
                        }
                        catch (Mage_Core_Exception $e) {
                            $response['status'] = 'ERROR';
                            $response['message'] = $this->__('An error occurred while adding item to wishlist: %s', $e->getMessage());
                        }
                        catch (Exception $e) {
                            mage::log($e->getMessage());
                            $response['status'] = 'ERROR';
                            $response['message'] = $this->__('An error occurred while adding item to wishlist.');
                        }
                    }
                }
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        return;
    }

    public function guestAction()
    {
        $response = array();
//        check wishlist active in admin
        if(!Mage::getStoreConfigFlag('wishlist/general/active')){
            $response['status'] = 'ERROR';
            $response['message'] = $this->__('Wishlist Has Been Disabled By Admin') ;
        }
//        Add product to wishlist for guest
        if(!Mage::getSingleton('customer/session')->isLoggedIn()){
            $requestParams = $this->getRequest()->getParams();
            $buyRequest = new Varien_Object($requestParams);
            $sessionGuest = Mage::getSingleton('customer/session');
            $refererGuest = $sessionGuest->getBeforeWishlistUrl();
            $productIdGuest = (int) $this->getRequest()->getParam('product');
            $productGuest = Mage::getModel('catalog/product')->load($productIdGuest);

//            Check product existed in session
            if( Mage::getSingleton('core/session')->getWishListGuest()&& in_array((string)$productIdGuest, explode(',',Mage::getSingleton('core/session')->getWishListGuest())))
            {
                $messageGuest = $this->__('%1$s is existing your wishlist', $productGuest->getName(), $refererGuest);
                $response['status'] = 'SUCCESS';
                $response['message'] = $messageGuest;
            }else{
                if(Mage::getSingleton('core/session')->getWishListGuest() && Mage::getSingleton('core/session')->getWishListGuest() != ''){
                    Mage::getSingleton('core/session')->setWishListGuest(Mage::getSingleton('core/session')->getWishListGuest().','.$productIdGuest);
                }else{
                    Mage::getSingleton('core/session')->setWishListGuest($productIdGuest);
                }
                //            Message Successfully
                $messageGuest = $this->__('%1$s has been added to  your wishlist.', $productGuest->getName(), $refererGuest);
                $response['status'] = 'SUCCESS';
                $response['message'] = $messageGuest;
                $_productIds = explode(',',Mage::getSingleton('core/session')->getWishListGuest());
                $cart = Mage::getModel('checkout/cart')->getQuote();
                foreach ($cart->getAllItems() as $item){
                    $itemCart = $item->getProduct()->getId();
                    $key = array_search($itemCart,$_productIds);
                    if($key!==false){
                        unset($_productIds[$key]);
                    }
                }
                $response['count']  = count($_productIds).' items';
            }
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        return;
    }
}