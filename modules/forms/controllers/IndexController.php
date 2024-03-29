<?php
require_once 'KontorX/Controller/Action.php';
class Forms_IndexController extends KontorX_Controller_Action {
	public $skin = array(
		'layout' => 'index'
	);

	public function indexAction() {
		$this->_forward('show');
	}

	public function showAction() {
		$config = $this->_helper->loader->config('config.ini');

		require_once 'forms/models/Forms.php';
		$forms = new Forms($config->pathname);

		$form = $this->_getParam('form');
		
		if (!$forms->has($form)) {
			$this->_helper->viewRenderer->render('show.error');
			return;
		}
		
		try {
			$data = $forms->load($form);
		} catch (FormsException $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n". $e->getTraceAsString(), Zend_Log::ERR);

			$this->_helper->viewRenderer->render('show.error');
			return;
		}

		// ustawiamy temat
		$this->view->subject = $data->options->subject;
		
		require_once 'Zend/Form.php';
		$form = new Zend_Form($data->form);

		require_once 'Zend/Form/Decorator/Description.php';
		$form->addDecorator(new Zend_Form_Decorator_Description());

		$request = $this->getRequest();

		if (!$request->isPost()) {
			$this->view->form = $form;
			return;
		}

		if (!$form->isValid($request->getPost())) {
			$this->view->form = $form;
			return;
		}

		$html = $forms->createHtml($data, $form, $this->view);
		$this->view->html = $html;
		$this->view->form = $form;

		// czy jest w formularzu podany adres email?
		if (null !== ($email = $form->getValue('email'))) {
			// wtedy wysyłamy
			require_once 'Zend/Mail.php';
			require_once 'Zend/Mail.php';
			$mail1 = new Zend_Mail('utf-8');
			$mail2 = new Zend_Mail('utf-8');
			
			$mail1->setBodyHtml($html);
			$mail1->setSubject($data->options->subject);
			$mail1->setFrom($data->options->email, $data->options->emailName);
			$mail1->addTo($form->getValue('email'));
			
			$mail2->setBodyHtml($html);
			$mail2->setSubject($data->options->subject);
			$mail2->setFrom($data->options->email, $data->options->emailName);
			$mail2->addTo($data->options->email);
			
			
			try {
				$mail1->send();
				$mail2->send();
			} catch (Zend_Mail_Exception $e) {
				Zend_Registry::get('logger')
					->log($e->getMessage() ."\n". $e->getTraceAsString(), Zend_Log::ERR);
					
				$this->_helper->flashMessenger->addMessage("Formularz nie został wysłany!");
			}
		}
	}
}