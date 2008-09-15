<?php
class Default_SendMailObserver extends KontorX_Observable_Observer_SendMail {
	/**
	 * Mail został wysłany
	 */
	const MAIL_SEND = 1;

	/**
	 * @Overwrite
	 */
	protected $_mailFiles = array(
		self::MAIL_SEND => 'mail_send'
	);

	/**
	 * @Overwrite
	 */
	protected $_mailSubject = array(
		self::MAIL_SEND => 'Potwierdzenie wysłania formularza kontaktowego'
	);
	
	/**
	 * @Overwrite
	 */
	protected $_config = array(
		'scriptPath' => '{{APP_MODULES_PATHNAME}}/default/views/observers/sendmail/'
	);
}
?>