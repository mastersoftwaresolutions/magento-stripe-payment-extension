<?php
/**
 * 
* @package    Mss_Stripepay
* @author     Yogendra Kumar (mss.yogendra@gmail.com)
* @copyright  Copyright (c) 2014 mastersoftwaresolution
*/
require_once Mage::getBaseDir('lib').DS.'Stripe'.DS.'Stripe.php';
class Mss_Pay_Model_Pay extends Mage_Payment_Model_Method_Abstract
{
	protected $_code = 'pay';
	protected $_formBlockType = 'pay/form_pay';
	protected $_infoBlockType = 'pay/info_pay';

	
	protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
	protected $_canSaveCc = false; //if made try, the actual credit card number and cvv code are stored in database.
	public function process($data){
		//Mage::log('process method'.print_r($data,true), null,'mss.log');
		if($data['cancel'] == 1){
		 $order->getPayment()
		 ->setTransactionId(null)
		 ->setParentTransactionId(time())
		 ->void();
		 $message = 'Unable to process Payment';
		 $order->registerCancellation($message)->save();
		}
	}
	

	 public function __construct()
    {
		Stripe::setApiKey($this->getConfigData('api_key'));
		//Mage::log('apikey method'.$this->getConfigData('api_key'), null,'cmss.log');
    }   
    public function capture(Varien_Object $payment, $amount)
	{
		return $this;
	}
	/** For authorization **/
	public function authorize(Varien_Object $payment, $amount)
	{
		Mage::log('authorize method', null,'cmss.log');
		#getOrderid	
		$order = $payment->getOrder();
		$billing = $order->getBillingAddress();
		#creating request object for stripe
				$request=array(
								'amount'	=> $amount*100,
								'currency'	=> strtolower($order->getBaseCurrencyCode()),
								'card' 		=> array(
									'number'			=>	$payment->getCcNumber(),
									'exp_month'			=>	sprintf('%02d',$payment->getCcExpMonth()),
									'exp_year'			=>	$payment->getCcExpYear(),
									'cvc'				=>	$payment->getCcCid(),
									'name'				=>	$billing->getName(),
									'address_line1'		=>	$billing->getStreet(1),
									'address_line2'		=>	$billing->getStreet(2),
									'address_zip'		=>	$billing->getPostcode(),
									'address_state'		=>	$billing->getRegion(),
									'address_country'	=>	$billing->getCountry(),
								),
								'description'	=>	sprintf('#%s, %s', $order->getIncrementId(), $order->getCustomerEmail())
							);
		
		#request to stripe to charge the card
		try{
			//Stripe::setApiKey('sk_test_4SaT1bB6uEuFF5POEUnsSZHJ');
			$response = Stripe_Charge::create($request);
			Mage::log('authorize method'.print_r($response,true), null,'cmss.log');
			//$fmessage=$response->failure_code;
			if($response->paid){$result=1;}
			else{$result=false;}
		} catch (Exception $e) {
			$this->debugData($e->getMessage());
			
			Mage::throwException(Mage::helper('paygate')->__($e->getMessage()));
		}		
		if($result === false) {
			$errorCode = 'Invalid Data';
			$errorMsg = $this->_getHelper()->__('Error Processing the request');
		} else {
			Mage::log($result, null, $this->getCode().'.log');
			//process result here to check status etc as per payment gateway.
			// if invalid status throw exception

			if($result==1){
				//Mage::log('sdsd'.$response->id, null, 'cmss.log');
				$payment->setTransactionId($response->id);
				$payment->setIsTransactionClosed(1);
				$payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,array('key1'=>'value1','key2'=>'value2'));//
					
			}else{
				Mage::throwException($errorMsg);
			}

			// Add the comment and save the order
		}
		if($errorMsg){
			Mage::throwException($errorMsg);
		}
		$this->capture($payment, $order->getGrandTotal());

        // Create invoice
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice(array());
        $invoice->register();
        
        $invoice->setCanVoidFlag(true);
        $invoice->getOrder()->setIsInProcess(true);
        $invoice->setState(2);

        if (Mage::helper('sales')->canSendNewInvoiceEmail($order->getStoreId())) {
            $invoice->setEmailSent(true);
            $invoice->sendEmail();
        }

        $invoice->save();
        $order->setTotalPaid($order->getBaseGrandTotal());
        $order->addStatusHistoryComment('Captured online amount of R$'.$order->getBaseGrandTotal(), false);
        $order->save();
			//Mage::log($response->id, null, 'mss.log');
		return $this;
	}

	public function processBeforeRefund($invoice, $payment){
		return parent::processBeforeRefund($invoice, $payment);
	}
	public function refund(Varien_Object $payment, $amount){
		$order = $payment->getOrder();
		$result = $this->callApi($payment,$amount,'refund');
		if($result === false) {
			$errorCode = 'Invalid Data';
			$errorMsg = $this->_getHelper()->__('Error Processing the request');
			Mage::throwException($errorMsg);
		}
		return $this;

	}
	public function processCreditmemo($creditmemo, $payment){
		return parent::processCreditmemo($creditmemo, $payment);
	}		
}
?>
