<?php
require_once 'KontorX/Db/Table/Tree/Abstract.php';
class CatalogHasCatalogOptions extends KontorX_Db_Table_Abstract {
    protected $_name = 'catalog_has_catalog_options';

    protected $_referenceMap    = array(
            'Catalog' => array(
                'columns'           => 'catalog_id',
                'refTableClass'     => 'Catalog',
                'refColumns'        => 'id',
                            'refColumnsAsName'  => 'name',
                            'onDelete'			=> self::CASCADE
        ),
            'CatalogOptions' => array(
                'columns'           => 'catalog_options_id',
                'refTableClass'     => 'CatalogOptions',
                'refColumns'        => 'id',
                            'refColumnsAsName'  => 'name',
                            'onDelete'			=> self::CASCADE
        ),
    );

    /**
     * Zwraca tablice z wszystkimi zaznaczonymi rekordami dla opcji
     * @return array
     */
    public function fetchRowOptionsArray($catalogId) {
        $result = array();
        try {
            $rowset = $this->fetchAll($this->select()->where('catalog_id = ?', $catalogId));
            foreach ($rowset as $row) {
                $result[] = $row->catalog_options_id;
            }
        } catch(Zend_Db_Table_Exception $e) {
            Zend_Registry::get('logger')
                ->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
        }
        return $result;
    }
}