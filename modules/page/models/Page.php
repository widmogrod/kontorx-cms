<?php
// zalerznosci
require_once 'user/models/User.php';
require_once 'language/models/Language.php';

require_once 'KontorX/Db/Table/Tree/Abstract.php';
class Page extends KontorX_Db_Table_Tree_Abstract {
	protected $_name = 'page';
	protected $_level = 'path';

	protected $_dependentTables = array(
		'PageBlock'
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
     * Nazwa kolumny przechowującej rodzaj widoczności rekordu
     *
     * @var string
     */
    protected $_columnForSpecialCredentials = 'visible';
}