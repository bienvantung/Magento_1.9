<?php
/**
 * Created by PhpStorm.
 * User: Tungbv
 * Date: 25/07/2014
 * Time: 15:05
 */
class Smartosc_AjaxWishlist_Block_AjaxWishlist extends Mage_Core_Block_Template
{
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function getAjaxWishlist()
    {
        if (!$this->hasData('ajaxwishlist')) {
            $this->setData('ajaxwishlist', Mage::registry('ajaxwishlist'));
        }
        return $this->getData('ajaxwishlist');

    }
}