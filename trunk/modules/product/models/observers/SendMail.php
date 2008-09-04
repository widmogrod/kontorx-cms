<?php
class Product_Model_Observer_SendMail extends KontorX_Observable_Observer_SendMail {
	/**
	 * Mail został wysłany
	 */
	const CHECKOUT_SUCCESS = 1;

	/**
	 * @Overwrite
	 */
	protected $_mailFiles = array(
		self::CHECKOUT_SUCCESS => 'checkout_success'
	);

	/**
	 * @Overwrite
	 */
	protected $_mailSubject = array(
		self::CHECKOUT_SUCCESS => 'Potwierdzenie wysłania formularza zamówienia'
	);
	
	/**
	 * @Overwrite
	 */
	protected $_config = array(
		'scriptPath' => '{{APP_PATHNAME}}product/views/observers/sendmail/'
	);
}
?>