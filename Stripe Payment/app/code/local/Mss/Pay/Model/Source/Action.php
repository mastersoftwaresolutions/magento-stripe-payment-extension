<?php
/**
 * 
* @package    Mss_Stripepay
* @author     Yogendra Kumar (mss.yogendra@gmail.com)
* @copyright  Copyright (c) 2014 mastersoftwaresolution
*/
class Mss_Pay_Model_Source_Action
{
	public function toOptionArray()
	{
		return array(
				array(
						'value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE,
						'label' => Mage::helper('core')->__('Authorize & Capture')
				),
				array(
						'value' => Mage_Payment_Model_Method_Abstract::ACTION_ORDER,
						'label' => Mage::helper('core')->__('Order')
				),
				array(
						'value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE,
						'label' => Mage::helper('core')->__('Authorize')
				),
				
		);
	}
}