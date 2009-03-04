<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class User_AdminController extends KontorX_Controller_Action_CRUD {
	public $skin = array(
		'layout' => 'admin_user'
	);

	protected $_modelClass = 'User';
	
	public function indexAction() {
		$this->_forward('list');
	}

	/**
	 * @Overwrite
	 * 
	 * TODO Dodać minimalne wielkości do walidacji etc.
	 */
	protected function _addGetForm() {
		// generuje forularz
		$form = parent::_addGetForm();

		// usuniecie niepotrzebnych elementow
		$form->removeElement('key');
		// zmiana pola password na @see Zend_Form_Element_Password
		$password = $form->getElement('password');
		$passwordName = $password->getName();
		$passwordLabel = $password->getLabel();
		$form->addElement('password', $passwordName, array('label' => $passwordLabel));

		// wzbogacenie o walidację email
		$email = $form->getElement('email');
		$email->setAllowEmpty(false);
		$email->addValidator('EmailAddress');

		// sprawdzenie czy email istnieje juz w BD
		$model = $this->_getModel();
		$where = $model->getAdapter()->quoteInto('email = ?', $this->_request->getPost('email'));
		$email->addValidator(new KontorX_Validate_DbTable($model,$where));
		
		return $form;
	}

	/**
	 * @Overwrite
	 */
    protected function _listFetchAll() {
    	$page = $this->_getParam('page',1);
    	$rowCount = $this->_getParam('rowCount',30);

    	$model = $this->_getModel();
    	$db = $model->getAdapter();
    	
    	// select dla danych
		$select = $model->select();
		$select
			->limitPage($page, $rowCount);
    	$rowset = $model->fetchAll($select);

    	// select dla paginacji
		$this->_preparePagination($select);
    	
    	return $rowset;
    }

        /**
	 * @Overwrite
	 */
	protected function _addPrepareData(Zend_Form $form) {
		$data = parent::_addPrepareData($form);
		$data['password'] = User::saltPassword($data['email'],$data['password']);
		return $data;
	}
	
	/**
	 * @Overwrite
	 */
	protected function _editGetForm(Zend_Db_Table_Row_Abstract $row){
		// generuje forularz
		$form = parent::_editGetForm($row);

		// usuniecie niepotrzebnych elementow
		$form->removeElement('key');
		// zmiana pola password na @see Zend_Form_Element_Password
		$password = $form->getElement('password');
		$passwordName = $password->getName();
		$passwordLabel = $password->getLabel();
		$form->addElement('password', $passwordName, array('label' => $passwordLabel));

		// wzbogacenie o walidację email
		$email = $form->getElement('email');
		$email->setAllowEmpty(false);
		$email->addValidator('EmailAddress');

		// sprawdzenie czy email istnieje juz w BD
		// ale nie nalerzy do edytowanego rekordu
		$model = $this->_getModel();
		$db = $model->getAdapter();
		$where = $db->quoteInto('email = ? ', $this->_request->getPost('email')) .
				 $db->quoteInto('AND id <> ?', $this->_getParam('id'));
		$email->addValidator(new KontorX_Validate_DbTable($model, $where));
		
		return $form;
	}

	/**
	 * @Overwrite
	 */
	protected function _editPrepareData(Zend_Form $form, Zend_Db_Table_Row_Abstract $row) {
		$data = $form->getValues();
		$data = array_diff_key($data, array_flip(array('Edytuj')));
    	$data = get_magic_quotes_gpc() ? array_map('stripslashes', $data) : $data;

    	// gdy pole hasla podane puste
    	if (array_key_exists('password', $data)) {
    		if ($data['password'] == '') {
    			unset($data['password']);
    		} else {
    			// hashowanie hasla
    			$data['password'] = User::saltPassword($data['email'],$data['password']);
    		}
    		
    	}
    	
    	return $data;
	}
        
        /**
         * @Overwrite
         */
        protected function _modifyInit() {
                $this->_addModificationRule('registered',self::MODIFY_BOOL);
        }
}
?>