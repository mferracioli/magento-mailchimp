<?php 

require_once(Mage::getBaseDir('lib') . '/MailChimp/MailChimp.php');
require_once(Mage::getBaseDir('lib') . '/MailChimp/MailChimp3.php');

class Cammino_Mailchimp_Model_Ecommerce extends Mage_Core_Model_Abstract {

	private $_token, $_enabled;

	protected function _construct() {
		$this->_enabled  = Mage::getStoreConfig("newsletter/mailchimp/ecommerce");
		$this->_token    = Mage::getStoreConfig("newsletter/mailchimp/token");
		$this->_store_id = Mage::getStoreConfig("newsletter/mailchimp/store_id");
		$this->_mailchimp  = new MailChimp3($this->_token);
	}

	public function cart($quote) {
		if ($this->_enabled && $this->_store_id) {
			try {
				$this->handleProduct($quote->getAllItems());				
				$addQuote = $this->getQuote($quote);
				$callResultAddQuote = $this->_mailchimp->post('ecommerce/stores/' . $this->_store_id . '/orders', $addQuote);
			} catch (Exception $e) {
				var_dump($e->getMessage()); die;
			}
		}
	}

	public function order($orderId) {
		if ($this->_enabled && $this->_store_id) {
			try {
				Mage::log("passou pelo model -------- Função order", null, 'mailchimp-ecommerce-api.log');
				// $mailchimp  = new MailChimp3($this->_token);
				// $request2 = $mailchimp->post('ecommerce/stores', array(
				// 		"id" => "vital-atman",
				//       "list_id" => "f032d2565b",
				//       "name" => "Vital Atman",
				//       "domain" => "www.vitalatman.com.br",
				//       "email_address" => "teste@teste.com",
				//       "currency_code" => "BRL"
				// 	)
				// );
				// var_dump($request2);die;
				// $request3 = $mailchimp->get('ecommerce/stores', []);
				// var_dump($request3);die;
				// $request4 = $mailchimp->get('ecommerce/stores/vital-atman/products', []);
				// var_dump($request4);die;
				// $request4	 = $this->postProducts($orderId);
				// $callResult  = $mailchimp->post('ecommerce/stores/vital-atman/products', $request4);
				// var_dump($callResult);die;
				$order 	  = Mage::getModel('sales/order')->loadByIncrementId($orderId);
				$this->handleProduct($order->getAllVisibleItems());				
				
				$addOrder	 = $this->getOrder($order);
				$callResultAddOrder = $this->_mailchimp->post('ecommerce/stores/' . $this->_store_id . '/orders', $addOrder);
			} catch (Exception $e) {
				var_dump($e->getMessage()); die;
			}
		}
	}

	private function handleProduct($items) {
		foreach($items as $item) {
	        $productsVerification = $this->verifyProduct($item->getProductId());
	        if ($productsVerification) {
				$addProduct = $this->postProducts($item);
				$callResult = $this->_mailchimp->post('ecommerce/stores/' . $this->_store_id . '/products', $addProduct);
			}
		}
	}
	private function getQuote($quote) {
		$customer = $this->getCustomer($quote, null);
		$products = $this->getProducts($quote->getAllItems());
		$result = array(
			'id' => $quote->getId(),
			'customer' => $customer,
			'currency_code' => 'BRL',
			'order_total' => (double)number_format($quote->getGrandTotal(), 2, '.', ''),
			'lines' => $products
		);

		return $result;
	}

	private function verifyProduct($productId) {
		$returnGetProduct = $this->_mailchimp->get('ecommerce/stores/' . $this->_store_id . '/products/' . $productId, []);
		return ($returnGetProduct['status'] == 404);		
	}

	private function getOrder($order)
	{
		$customer = $this->getCustomer(null, $order);
		$products = $this->getProducts($order->getAllVisibleItems());
		
		$result = array(
			'id' => $order->getIncrementId(),
			'customer' => $customer,
			'email' => $order->getCustomerEmail(),
			'currency_code' => 'BRL',
			'order_total' => (double)number_format($order->getBaseGrandTotal(), 2, '.', ''),
			'shipping_total' => (double)number_format($order->getBaseShippingAmount(), 2, '.', ''),
			'lines' => $products
		);

		return $result;
	}

	private function postProducts($item) {		
		$result = array(
			'id' => $item->getProductId(), 
			'title' => $item->getName(),
			'variants' => array(
				array(
					'id' => $item->getProductId(), 
					'title' => $item->getName(),
					'price' => (double)number_format($item->getBasePrice(), 2, '.', ''),
					'sku'   => $item->getSku()
				)
			), 
		);
        
	  	return $result;
	}

	private function getProducts($items) {
        
		foreach ($items as $item) {	
			$result[] = array(
				'id' => $item->getProductId(), 
				'product_id' => $item->getProductId(), 
				'product_variant_id' => $item->getProductId(), 
				// 'quantity'  => (double)number_format($item->getQtyOrdered(), 0, '', ''),
				'quantity' => 1,
				'price' => (double)number_format($item->getBasePrice(), 2, '.', '')
			);
        }
	  	
	  	return $result;
	}

	private function getCustomer($quote, $order = null) {
		if ($order) {
			$billingAddress = $order->getBillingAddress();
			$customer = array(
				"id" => $order->getCustomerEmail(),
				"opt_in_status" => true,
				"email_address" => $order->getCustomerEmail(), 
				"first_name" => $billingAddress->getFirstname(),
				"last_name" => $billingAddress->getLastname(),
			);
		}
		else if ($quote) {
			$customerObj = Mage::getSingleton('customer/session')->getCustomer();
			$customer = array(
				"id" => $customerObj->getEmail(),
				"opt_in_status" => true,
				"email_address" => $customerObj->getEmail(), 
				"first_name" => $customerObj->getFirstname(),
				"last_name" => $customerObj->getLastname(),
			);
		}

		return $customer;
	}
}