<?php
require_once 'KontorX/Controller/Action.php';
class Catalog_SiteController extends KontorX_Controller_Action {

    public $skin = array (
        'layout' => 'catalog',
        'show' => array(
            'layout' => 'catalog_show',
            'lock' => true
        )
    );

    public function init() {
        $this->view->addHelperPath('KontorX/View/Helper');

        parent::init();
    }

    public function showAction() {
        $url = strtolower($this->_getParam('url'));
        if ($url === 'www') {
            $this->_forward('index','index','catalog');
            return;
        }

        $this->view->url = $url;
    }
}