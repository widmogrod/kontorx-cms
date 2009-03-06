<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class User_AccessController extends KontorX_Controller_Action_CRUD {
    public $skin = array(
        'layout' => 'admin_user'
    );

    protected $_modelClass = 'RoleAccess';

    public function indexAction() {
        $this->_forward('list');
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

        if ($this->_hasParam('role_resource_id')) {
            $select->where('role_resource_id = ?', $this->_getParam('role_resource_id'));
        }

        $rowset = $model->fetchAll($select);

        // select dla paginacji
        $this->_preparePagination($select);

        return $rowset;
    }

    /**
     * @Overwrite
     * @return Zend_Form
     */
    protected function _addGetForm() {
        $form = parent::_addGetForm();

        $model = $this->_getModel();

        $where = 'name = ? AND role_resource_id = :role_resource_id';

        require_once 'KontorX/Validate/DbTable.php';
        $dbTable = new KontorX_Validate_DbTable($model);
        $dbTable->setWhere($where);
        $dbTable->setAttribs(KontorX_Validate_DbTable::REQUEST);
        $dbTable->setUniqValue();

        $form
        ->getElement('name')
        ->addValidator($dbTable);

        return $form;
    }

    /**
     * @Overwrite
     */
    protected function _addOnIsPost(Zend_Form $form) {
        $form->setDefault('role_resource_id', $this->_getParam('role_resource_id'));
        return parent::_addOnIsPost($form);
    }


    /**
     * @Overwrite
     * @return Zend_Form
     */
    protected function _editGetForm(Zend_Db_Table_Row_Abstract $row) {
        $form = parent::_editGetForm($row);

        $model = $this->_getModel();

        $where = 'name = ? AND role_resource_id = :role_resource_id AND id <> :id';

        require_once 'KontorX/Validate/DbTable.php';
        $dbTable = new KontorX_Validate_DbTable($model);
        $dbTable->setWhere($where);
        $dbTable->setAttribs(KontorX_Validate_DbTable::REQUEST);
        $dbTable->setUniqValue();

        $form
        ->getElement('name')
        ->addValidator($dbTable);
        return $form;
    }
}