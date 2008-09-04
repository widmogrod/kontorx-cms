<?php
class Test_IndexController extends KontorX_Controller_Action {
	public function init() {
		$this->_initLayout();
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}
	
	/**
     * Landing page
     */
    public function indexAction()
    {
        $this->view->form = $this->getForm();
    }

    public function autocompleteAction()
    {
        if ('ajax' != $this->_getParam('format', false)) {
            return $this->_helper->redirector('index');
        }
        if ($this->getRequest()->isPost()) {
            return $this->_helper->redirector('index');
        }

        $match = trim($this->getRequest()->getQuery('test', ''));

        $matches = array();
        foreach ($this->getData() as $datum) {
        	 $matches[] = $datum;
//            if (0 === strpos($datum, $match)) {
//                $matches[] = $datum;
//            }
        }
        $this->_helper->autoCompleteDojo($matches);
    }
	
	protected $_form;

	public function getData() {
		return array(
			'Polska','Alaska','Kanada','Pakistan'
		);
	}
	
    public function getForm() {   
        if (null === $this->_form) {
            require_once 'Zend/Form.php';
            $this->_form = new Zend_Form();
            $this->_form->setMethod('get')
                ->setAction($this->getRequest()->getBaseUrl() . '/test/index/process')
                ->addElements(array(
                    'test' => array('type' => 'text', 'options' => array(
                        'filters'        => array('StringTrim'),
                        'dojoType'       => array('dijit.form.ComboBox'),
                        'store'          => 'testStore',
                        'autoComplete'   => 'false',
                        'hasDownArrow'   => 'true',
                        'label' => 'Your input:',
                    )),
                    'go' => array('type' => 'submit', 'options' => array('label' => 'Go!'))
                ));
        }
        return $this->_form;
    }
}