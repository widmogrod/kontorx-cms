<?php
class CartException extends Zend_Exception {}

class Cart {
	protected $_session = null;

	public function __construct() {
		$this->_session = new Zend_Session_Namespace('Cart');
	}

	/**
	 * Zwraca dane koszyka
	 *
	 * @return array
	 */
	public function get() {
		return (array) $this->_session->cart;
	}

	/**
	 * Aktualizuj ilosc sztuk produkto w koszyku
	 *
	 * @param array $quantity
	 */
	public function updateProductsQuantity(array $quantity) {
		$cart = (array) $this->_session->cart;

		foreach ($quantity as $id => $value) {
			if (is_numeric($value) && $value > 0) {
				if (array_key_exists($id, $cart)) {
					$cart[$id]['quantity'] = $value;
				}
			}
		}

		$this->_session->cart = $cart;
	}

	/**
	 * Czy w koszyku są produkty
	 *
	 * @return bool
	 */
	public function hasProducts() {
		return !empty($this->_session->cart);
	}
	
	/**
	 * Zwraca listę produktów w koszyku
	 * 
	 * Zwraca listę produktów w koszyku z podstawową kalkulacją sumarycznej
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getProducts() {
		require_once 'product/models/Product.php';
		$product = new Product();

		$db = $product->getAdapter();
		$select = new Zend_Db_Select($db);
		$select
			->from(array('p' => 'product'))
			->joinLeft(
				array('pi' => 'product_image'),
				"(pi.product_id = p.id AND (pi.thumb = 1))",
				array('image' => 'pi.image')
			)
			->where('publicated = 1') // tylko opublikowane
			->order('pi.thumb DESC');

		// id produktów z koszyka
		$cart = (array) $this->_session->cart;
		$whereOrTerms = array();
		foreach ($cart as $productId => $data) {
			$whereOrTerms[] = $db->quoteInto('p.id = ?', $productId);
		}
		$whereClause = '(' . implode(' OR ', $whereOrTerms) . ')';
		$select->where($whereClause);

		try {
			$stmt = $select->query();
			$rowset = $stmt->fetchAll(Zend_Db::FETCH_CLASS);
			// update liczebnosci produktów ..
			foreach ($rowset as &$row) {
				$row->quantity = $cart[$row->id]['quantity'];
			}
			return $rowset;
		} catch (Zend_Db_Table_Exception $e){
			// logowanie zdarzeń
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" .  $e->getTraceAsString(), Zend_Log::ERR);
		}
	}

	/**
	 * Dodaj produkt do koszyka
	 *
	 * @param integer $productId
	 * @return bool
	 */
	public function addProduct($productId, $quantity = null) {
		require_once 'product/models/Product.php';
		$product = new Product();

		$select = $product->select();
		$select
			->where('id = ?', $productId)
			->where('publicated = 1');

		try {
			$row = $product->fetchRow($select);
		} catch (Zend_Db_Table_Exception $e){
			// logowanie zdarzeń
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" .  $e->getTraceAsString(), Zend_Log::ERR);

			return false;
		}

		if (!$row instanceof Zend_Db_Table_Row_Abstract) {
			return false;
		}

		$data = array(
			'id' => $productId,
			'name' => $row->name,
			'price' => $row->price
		);

		$data = ((int) $quantity < 1)
			? $data : $data += array('quantity' => $quantity);

		$this->_add($productId, $data);

		return true;
	}

	/**
	 * Usuń produkt z koszyka
	 * 
	 * @param integer $productId
	 * @return bool
	 */
	public function removeProduct($productId) {
		return $this->_remove($productId);
	}

	/**
	 * Dodaj do koszyka sesii
	 *
	 * @param integer $id
	 */
	protected function _add($id, array $data = array()) {
		$cart = (array) $this->_session->cart;
		$isQuantity = array_key_exists('quantity', $data);

		if (array_key_exists($id, $cart)) {
			$cart[$id] += $data;
			if (!$isQuantity) {
				$cart[$id]['quantity'] = $cart[$id]['quantity'] + 1;
			}
		} else {
			$cart[$id] = $data;
			if (!$isQuantity) {
				$cart[$id]['quantity'] = 1;
			}
		}

		$this->_session->cart = $cart;
	}

	/**
	 * Usuwa z koszyka sesii
	 *
	 * @param integer $id
	 */
	protected function _remove($id) {
		$cart = (array) $this->_session->cart;

		// brak produktu w koszyku
		if (!array_key_exists($id, $cart)) {
			return false;
		}

		// usun zawartosc z koszyka
		unset($cart[$id]);
		$this->_session->cart = $cart;

		return true;
	}
}