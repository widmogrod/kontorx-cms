<?php
// zalerznosci
require_once 'user/models/User.php';
require_once 'language/models/Language.php';

require_once 'KontorX/Db/Table/Abstract.php';
class News extends KontorX_Db_Table_Abstract  {
	protected $_name 	 = 'news';
	protected $_rowClass = 'News_Row';

	protected $_dependentTables = array(
		'NewsHasCategory',
		'NewsComment'
	);
	
	protected $_referenceMap    = array(
        'Language' => array(
            'columns'           => 'language_url',
            'refTableClass'     => 'Language',
            'refColumns'        => 'url',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        ),
        'User' => array(
            'columns'           => 'user_id',
            'refTableClass'     => 'User',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'username'
        )
    );

    public function fetchTimeRange() {
    	$select = $this->select()
    		->from($this->_name)
    		->columns(array(
    			'year' => 'YEAR(t_create)',
    			'month' => 'MONTH(t_create)'
    		))
    		->group('DATE_FORMAT(t_create,"%Y-%m")')
    		->order('t_create DESC');

    	return $this->fetchAll($select);
    }
    
	/**
     * Wyłowienie widocznego publicznie rekordu
     *
     * @param string $url
     * @param string $language
     * @param Zend_Db_Select $select
     * @return KontorX_Db_Table_Tree_Row_Abstract
     */
    public function fetchRowPublic($url, $language, Zend_Db_Select $select = null) {
    	$select = (null === $select)
    		? $this->select()
    		: $select;

    	$select = $this->selectPublic($language, $select)
			->where('url = ?', $url);

		return $this->fetchRow($select);
    }

    /**
     * Przygotowuje ogólne zapytanie @see Zend_Db_Select
     * 
     * Przygotowuje ogólne zapytanie dla interfejsu
     * publicznego
     *
     * @param string $language
     * @param Zend_Db_Select $select
     * @return Zend_Db_Select
     */
    public function selectPublic($language, Zend_Db_Select $select = null) {
    	$select = (null === $select)
    		? $this->select()
    		: $select;

    	$select
			->where('language_url = ?', $language)
			->where('publicated = 1');

		return $select;
    }
    
	/**
	 * Przypisz do aktualnosc do kategorii
	 *
	 * @param integer $newsId
	 * @param array $categories
	 */
	public function attachToCategories($newsId, array $categories) {
    	require_once 'news/models/NewsCategory.php';
		$category = new NewsHasCategory();

		$db = $this->getAdapter();
		$db->beginTransaction();

		$where = $db->quoteInto('news_id = ?', $newsId);
		try {
			$category->delete($where);
			foreach ($categories as $cateogryId) {
				$data = array(
					'news_id' => $newsId,
					'news_category_id' => $cateogryId
				);
				
				$category->insert($data);
			}
			$db->commit();
		} catch(Zend_Db_Table_Exception $e) {
			$db->rollBack();
			throw new Zend_Db_Table_Exception($e->getMessage());
		} catch(Zend_Db_Statement_Exception $e) {
			$db->rollBack();
			throw new Zend_Db_Table_Exception($e->getMessage());
		}
    }
}

require_once 'Zend/Db/Table/Row/Abstract.php';
class News_Row extends Zend_Db_Table_Row_Abstract {

	/**
	 * Znajduje id kategorii do ktorych nalerzy rekord
	 *
	 * @return array
	 */
	public function findDependentCategoriesArray() {
		$result = array();
		$rowset = $this->findDependentRowset('NewsHasCategory');
		foreach ($rowset as $row) {
			$result[] = $row->news_category_id;
		}
		return $result;
	}

//	/**
//	 * Klonowanie rekordu
//	 *
//	 * Klonowanie rekordu, przydatne przy duplikacji
//	 */
//	public function __clone() {
//		// tylko dlatego by byl insert
//		$this->_cleanData = array();
//
//		// przygotowanie danych do duplikacji
//		unset($this->_data['id']);
//		$this->_data['name'] = $this->_data['name'] . ' [duplikat]';
//		$this->_data['url']  = $this->_data['url']  . '-duplikat';
//
//		// ustawienie modyfikowanych kluczy
//		$this->_modifiedFields = array_combine(
//			array_keys($this->_data),
//			array_fill(0,count($this->_data),true));
//	}
}