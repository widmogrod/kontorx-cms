<?php
/**
 * Operacje pomocnicze dla koszyka produktow w widoku
 *
 */
class Product_View_Helper_Cart {
	
	/**
	 * Zend_View_Interface
	 *
	 * @var Zend_View_Interface
	 */
	public $view;

	/**
	 * Zawartosc koszyka
	 *
	 * @var array
	 */
	protected $_cart = array();

	/**
	 * Suma wszystkich kosztow
	 *
	 * @var real
	 */
	protected $_pricesSum = 0;
	
	public function __construct() {
		$session = new Zend_Session_Namespace('Cart');
		$this->_cart = (array) $session->cart;
	}

	/**
	 * Zwraca wlasny obiekt
	 *
	 * @return Product_View_Helper_Cart
	 */
	public function cart() {
		return $this;
	}

	/**
	 * Liczebnosc produktu w koszyku
	 *
	 * @param mixed $row Może byc typu object
	 * @return integer|null
	 */
	public function getQuantity($row) {
		$id = is_object($row) ? $row->id : $row;
		return array_key_exists($id, $this->_cart) ? $this->_cart[$id]['quantity'] : null;
	}

	/**
	 * Zwraca cene przemnozona przez ilosc produktow
	 *
	 * @param object $row
	 * @return real
	 */
	public function getPrice($row) {
		if (!is_object($row)) {
			$error = 'Product_View_Helper_Cart::getPrice argument `$row` must be a object';
			trigger_error($error,E_USER_WARNING);
			return;
		}

		$productId = $row->id;
		$price = $row->price;

		if (array_key_exists($productId, $this->_cart)) {
			// podliczanie ceny
			$price = $this->_cart[$productId]['quantity'] * $price;
		}
		// TODO Raczej else nie powinno wystapic ale .. przezorny to ubespieczony
		// dodac walidacje

		// sumowanie wszystkich kosztow
		$this->_pricesSum += $price;
		return $price;
	}

	/**
	 * Zwraca zsumowaną cenę
	 *
	 * @return real
	 */
	public function getPricesSum() {
		return $this->_pricesSum;
	}

	/**
     * Set the view object
     *
     * @param Zend_View_Interface $view
     * @return void
     */
    public function setView(Zend_View_Interface $view){
        $this->view = $view;
    }
}
?>