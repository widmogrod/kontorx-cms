<?php
require_once 'KontorX/Observable/Observer/SendMail.php';
class Catalog_Observer_Form extends KontorX_Observable_Observer_SendMail {
	const ERROR_NOTICE = 'ERROR_NOTICE';

	protected $_mailFiles = array(
		self::ERROR_NOTICE => 'error'
	);

	protected $_mailSubject = array(
		self::ERROR_NOTICE => '[EE] Powiadomienie o błędzie'
	);

	public function update(KontorX_Observable_Abstract $observable, Zend_Form $form = null) {
		if (null === $form) {
			parent::update($observable);
		} else {
			$data = $form->getValues();
			$this->_send($data);
		}
	}
}