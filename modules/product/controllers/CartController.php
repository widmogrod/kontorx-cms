<?php

class Product_CartController extends KontorX_Controller_Action {
	public $ajaxable = array(
		'cart' => array('json')
	);

	public function init() {
		$this->_initLayout(null,null,null,'default');
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
		
		$this->_helper->ajaxContext()
			->setAutoJsonSerialization(false)
			->initContext();

		$this->view->product_category_id = $this->_getParam('cid');
	}

	/**
	 * Dodaj produkt do koszyka
	 *
	 */
	public function addAction() {
		// wylaczenie widoku
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();

		require_once 'product/models/Cart.php';
		$cart = new Cart();

		$id = $this->_getParam('id');
		$quantity = $this->_getParam('quantity');
		if ($cart->addProduct($id, $quantity)) {
			$success = true;
			$message = "Produkt został dodany do koszyka";
		} else {
			$success = false;
			$message = "Produkt nie został dodany do koszyka";
		}

		if (!$this->_request->isXmlHttpRequest()) {
			$this->_helper->flashMessenger->addMessage($message);
			$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
		}

		$this->_helper->json(array(
			'success' => $success,
			'message' => $message,
			'cart' => $cart->get()
		));
	}

	/**
	 * Usuń produkt z koszyka
	 *
	 */
	public function removeAction() {
		// wylaczenie widoku
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();

		require_once 'product/models/Cart.php';
		$cart = new Cart();

		$id = $this->_getParam('id');
		if ($cart->removeProduct($id)) {
			$success = true;
			$message = "Produkt został usunięty z koszyka";
		} else {
			$success = false;
			$message = "Produkt nie istnieje w koszyku";
		}

		if (!$this->_request->isXmlHttpRequest()) {
			$this->_helper->flashMessenger->addMessage($message);
			$this->_helper->redirector->gotoUrl(getenv('HTTP_REFERER'));
		}

		$this->_helper->json(array(
			'success' => $success,
			'message' => $message,
			'cart' => $cart->get()
		));
	}
	
	/**
	 * Wyświetl zawartość koszyka
	 *
	 */
	public function cartAction() {
		require_once 'product/models/Cart.php';
		$cart = new Cart();

		if (!$cart->hasProducts()) {
			$this->_helper->viewRenderer->render('cart.empty');
			return;
		}
		
		$this->view->rowset = $cart->getProducts();

		if ($this->_request->isPost()) {
			$quantity = (array) $this->_getParam('quantity');
			$cart->updateProductsQuantity($quantity);
		}
	}
}