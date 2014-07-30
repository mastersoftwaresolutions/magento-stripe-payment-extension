<?php
/**
 * 
* @package    Mss_Stripepay
* @author     Yogendra Kumar (mss.yogendra@gmail.com)
* @copyright  Copyright (c) 2014 mastersoftwaresolution
*/

class Mss_Pay_Model_Observer 
{
    /**
     * Retrieve array of credit card types
     *
     * @return array
    */
  
    public function invoicepay($observer)
    {
         $orderIds = $observer->getData('order_ids');
        foreach($orderIds as $_orderId){
            $order = Mage::getModel('sales/order')->load($_orderId);
            try {
                $order->sendNewOrderEmail();
                Mage::log('email sent');
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        Mage::log('invoidm'.print_r($order,true),null,'inv.log');
        
        return $this;
    }
    
}
