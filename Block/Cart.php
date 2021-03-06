<?php
class Cammino_Mailchimp_Block_Cart extends Mage_Core_Block_Template {
	
	private $_enabled;
	
	protected function _construct() {
		$this->_enabled = Mage::getStoreConfig("newsletter/mailchimp/ecommerce");
	}

	protected function _toHtml() {
		$html = "";
		if (strval($this->_enabled) == "1") {
			$html .= '<script type="text/javascript">
				new Ajax.Request("'.Mage::getUrl('mailchimp/ecommerce/cart', array(
				'_secure' => true)).'",{ method:"get", onComplete: function (data) { } });
			</script>';
		}
	
		return $html;
	}

}