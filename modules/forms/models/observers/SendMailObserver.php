<?php
class Default_SendMailObserver extends KontorX_Observable_Observer_SendMail {
	/**
	 * Mail został wysłany
	 */
	const FORM_SEND = 1;

	/**
	 * @Overwrite
	 */
	protected $_mailFiles = array(
		self::FORM_SEND => 'form_send'
	);

	/**
	 * @Overwrite
	 */
	protected $_mailSubject = array(
		self::FORM_SEND => 'Potwierdzenie wysłania formularza zamówienia'
	);
	
	/**
	 * @Overwrite
	 */
	protected $_config = array(
		'scriptPath' => 'application/forms/views/observers/sendmail/'
	);
}
?>