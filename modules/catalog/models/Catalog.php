<?php
require_once 'user/models/User.php';

require_once 'KontorX/Db/Table/Abstract.php';
class Catalog extends KontorX_Db_Table_Abstract {
    protected $_name = 'catalog';
    protected $_rowClass = 'Catalog_Row';

    protected $_dependentTables = array(
        'CatalogTime',
        'CatalogSite',
        'CatalogImage',
        'CatalogServiceCost',
        'CatalogPromoTime',
        'CatalogHasCatalogOptions',
        'CatalogStaff',
        'CatalogHasCatalogStaff'
    );

    protected $_referenceMap    = array(
        'CatalogDistrict' => array(
            'columns'           => 'catalog_district_id',
            'refTableClass'     => 'CatalogDistrict',
            'refColumns'        => 'id',
            'refColumnsAsName'  => 'name',
            'onDelete'		=> self::CASCADE
        ),
        'CatalogImage' => array(
            'columns'           => 'catalog_image_id',
            'refTableClass'     => 'CatalogImage',
            'refColumns'        => 'id',
            'refColumnsAsName'  => 'image'
        ),
        'CatalogType' => array(
            'columns'           => 'catalog_type_id',
            'refTableClass'     => 'CatalogType',
            'refColumns'        => 'id',
            'refColumnsAsName'  => 'name',
            'onDelete'          => self::CASCADE
        ),
        'CatalogOption1' => array(
            'columns'           => 'catalog_option1_id',
            'refTableClass'     => 'CatalogOptions',
            'refColumns'        => 'id',
            'refColumnsAsName'  => 'name'
        ),
        'CatalogOption2' => array(
            'columns'           => 'catalog_option2_id',
            'refTableClass'     => 'CatalogOptions',
            'refColumns'        => 'id',
            'refColumnsAsName'  => 'name'
        ),
        'CatalogOption3' => array(
            'columns'           => 'catalog_option3_id',
            'refTableClass'     => 'CatalogOptions',
            'refColumns'        => 'id',
            'refColumnsAsName'  => 'name'
        ),
        'User' => array(
            'columns'           => 'user_id',
            'refTableClass'     => 'User',
            'refColumns'        => 'id',
            'refColumnsAsName'  => 'username'
        )
    );

    /**
     * Zwraca zapytanie pobierajace rekordy promo plus
     *
     * @param KontorX_Db_Table_Tree_Row_Abstract $row
     * @return Zend_Db_Select
     */
    public function selectForListPromoPlus(KontorX_Db_Table_Tree_Row_Abstract $row = null) {
        require_once 'Zend/Db/Select.php';
        $select = new Zend_Db_Select($this->getAdapter());

        $select
        ->from(array('c' => 'catalog'),'*')
        ->join(array('cd' => 'catalog_district'),
            'cd.id = c.catalog_district_id',
            array('district_url' => 'cd.url',
                'district' => 'cd.name'))
        ->joinInner(array('cpt' => 'catalog_promo_time'),
            'c.id = cpt.catalog_id '.
            'AND NOW() BETWEEN cpt.t_start AND cpt.t_end',
            array('cpt.catalog_promo_type_id'))
        ->joinLeft(array('cs' => 'catalog_site'),
            'c.id = cs.catalog_id',
            array('cs.url'))

        /** Opcje */
        ->joinLeft(array('co1' => 'catalog_options'),
            'co1.id = c.catalog_option1_id',
            array('option1'=>'co1.name'))
        ->joinLeft(array('co2' => 'catalog_options'),
            'co2.id = c.catalog_option2_id',
            array('option2'=>'co2.name'))
        ->joinLeft(array('co3' => 'catalog_options'),
            'co3.id = c.catalog_option3_id',
            array('option3'=>'co3.name'))

        ->joinLeft(array('ci' => 'catalog_image'),
            'ci.id = c.catalog_image_id',
            array('image' => 'ci.image'))

        /***/
        ->order('cpt.catalog_promo_type_id DESC')
        ->order('c.name ASC')
        ->where('cpt.catalog_promo_type_id = 3');	// tylko promocujne +

        // dodatkowy filtr na obszary - podobszary! bo promo!
        if (null !== $row) {
            $select->where('c.catalog_district_id = ?', $row->id, Zend_Db::INT_TYPE);
        }

        return $select;
    }

    /**
     * Zwraca zapytanie pobierające rekordy dal domyślnych rekordów
     * z sortowaniem rekordów promo (NIE promo plus)
     *
     * @param KontorX_Db_Table_Tree_Row_Abstract $row
     * @return Zend_Db_Select
     */
    public function selectForListDefault(KontorX_Db_Table_Tree_Row_Abstract $row = null) {
        require_once 'Zend/Db/Select.php';
        $select = new Zend_Db_Select($this->getAdapter());

        $select
        ->from(array('c' => 'catalog'),'*')
        ->join(array('cd' => 'catalog_district'),
            'cd.id = c.catalog_district_id',
            array('district_url' => 'cd.url',
                  'district' => 'cd.name'))
        ->joinLeft(array('cpt' => 'catalog_promo_time'),
            'c.id = cpt.catalog_id '.
            'AND cpt.catalog_promo_type_id <> 3 '. // bez promo +
            'AND NOW() BETWEEN cpt.t_start AND cpt.t_end',
            array('cpt.catalog_promo_type_id'))

                        /** Opcje */
        ->joinLeft(array('co1' => 'catalog_options'),
            'co1.id = c.catalog_option1_id',
            array('option1'=>'co1.name'))
        ->joinLeft(array('co2' => 'catalog_options'),
            'co2.id = c.catalog_option2_id',
            array('option2'=>'co2.name'))
        ->joinLeft(array('co3' => 'catalog_options'),
            'co3.id = c.catalog_option3_id',
            array('option3'=>'co3.name'))

        ->joinLeft(array('ci' => 'catalog_image'),
            'ci.id = c.catalog_image_id',
            array('image' => 'ci.image'))

        ->order('cpt.catalog_promo_type_id DESC')
        ->order('c.name ASC');
        //			->where('cpt.catalog_promo_type_id < 3')
        //			->orWhere('cpt.catalog_promo_type_id = NULL'); // default i promo (NIE promocujne +) ??


        // dodatkowy filtr na obszary + podobszary
        if (null !== $row) {
            $db = $this->getAdapter();
//            $select->where('c.catalog_district_id = ?', $row->id, Zend_Db::INT_TYPE);
            try {
                $where = array();
                $where[] = $db->quoteInto('c.catalog_district_id = ?', $row->id, Zend_Db::INT_TYPE);
                foreach ($row->findChildrens() as $row) {
                    $where[] = $db->quoteInto('c.catalog_district_id = ?', $row->id, Zend_Db::INT_TYPE);
                }
                $select->where(implode(" OR ", $where));
            } catch (Exception $e) {
                Zend_Registry::get('logger')
                ->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
            }
        }

        return $select;
    }

     /**
     * Zwraca zapytanie pobierające rekordy dal domyślnych rekordów
     * z sortowaniem rekordów promo (NIE promo plus)
     *
     * @param arary $data
     * @return Zend_Db_Select
     */
    public function selectForSearch(array $data) {
        require_once 'Zend/Db/Select.php';
        $select = new Zend_Db_Select($this->getAdapter());

        $select
        ->from(array('c' => 'catalog'),'*')
        ->join(array('cd' => 'catalog_district'),
               'cd.id = c.catalog_district_id',
            array('district_url' => 'cd.url',
                  'district' => 'cd.name'))
        ->join(array('cpt' => 'catalog_promo_time'),
            'c.id = cpt.catalog_id '.
            'AND NOW() BETWEEN cpt.t_start AND cpt.t_end',
            array('cpt.catalog_promo_type_id'))

        /** Opcje */
        ->joinLeft(array('co1' => 'catalog_options'),
            'co1.id = c.catalog_option1_id',
            array('option1'=>'co1.name'))
        ->joinLeft(array('co2' => 'catalog_options'),
            'co2.id = c.catalog_option2_id',
            array('option2'=>'co2.name'))
        ->joinLeft(array('co3' => 'catalog_options'),
            'co3.id = c.catalog_option3_id',
            array('option3'=>'co3.name'))

        ->joinLeft(array('ci' => 'catalog_image'),
            'ci.id = c.catalog_image_id',
            array('image' => 'ci.image'))

        ->order('cpt.catalog_promo_type_id DESC')
        ->group('c.id');

        // tworzenie filtrow ;]

        $db = $this->getAdapter();

        // nazwa
        if (@$data['name'] != '') {
            $select
                ->where("c.name LIKE ?", "%".$data['name']."%")
                ->orWhere("c.adress LIKE ?", "%".$data['name']."%");
        }

        // obszary
        if (is_numeric(@$data['district'])) {
            $catalogDistrict = new CatalogDistrict();
            try {
                $row = $catalogDistrict->fetchRow(
                    $catalogDistrict->select()->where('id = ?', $data['district'], Zend_Db::INT_TYPE)
                );

                if ($row instanceof KontorX_Db_Table_Tree_Row_Abstract) {
                    $where = array();
                    $where[] = $db->quoteInto('c.catalog_district_id = ?', $row->id, Zend_Db::INT_TYPE);
                    try {
                        foreach ($row->findChildrens() as $row) {
                            $where[] = $db->quoteInto('c.catalog_district_id = ?', $row->id, Zend_Db::INT_TYPE);
                        }
                        $select->where(implode(" OR ", $where));
                    } catch (Exception $e) {
                        Zend_Registry::get('logger')
                        ->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
                    }
                }
            } catch (Zend_Db_Table_Abstract $e) {
                Zend_Registry::get('logger')
                ->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
            }
        }

        // services
        if (count(@$data['service']) > 0) {
            $where = array();
            foreach ((array) $data['service'] as $serviceId) {
                if (is_numeric($serviceId)) {
                    $where[] = 'csc.catalog_service_id = ' . (int) $serviceId;
                }
            }
            if (count($where)) {
                $where = implode(" AND ", $where);
                $select->joinLeft(array('csc' => 'catalog_service_cost'),
                       'c.id = csc.catalog_id', array());
                $select->where($where);
            }
        }

        // opcje
        if (count(@$data['options']) > 0) {
            $where = array();
            foreach ((array) $data['options'] as $optionId) {
                if (is_numeric($optionId)) {
                    $where[] = 'chco.catalog_options_id = ' . (int) $optionId;
                }
            }
            if (count($where)) {
                $where = implode(" AND ", $where);
                $select->joinLeft(array('chco' => 'catalog_has_catalog_options'),
                       'c.id = chco.catalog_id', array());
                $select->where($where);
            }
        }

        // opcje
        if (@$data['hour'] != '' || count(@$data['week']) > 0) {
            // dzien i godzina
            if ($data['hour'] != '' && $data['week'] > 0) {
                $week = ((int)$data['week'])-2;
                $weekName = strtolower(date("l",mktime(0,0,0,0,$week,0,0)));
                $start = "ct.{$weekName}_start";
                $end   = "ct.{$weekName}_end";

                $hour = explode(":", $data['hour']);
                $hour = array_merge($hour, array_fill(0, 2, "00"));
                array_splice($hour, 2, 3);
                $hour = implode(":", $hour);

                $select
                    ->joinLeft(array('ct' => 'catalog_time'),
                        'c.id = ct.catalog_id', array())
                    ->where("TIME(?) BETWEEN $start AND $end", $hour);
            } else {
                // godzina
                if ($data['hour'] != '') {
                    $hour = explode(":", $data['hour']);
                    $hour = array_merge($hour, array_fill(0, 2, "00"));
                    array_splice($hour, 2, 3);
                    $hour = implode(":", $hour);
                    
                    $where = array();
                    for ($i=0;$i<=7;$i++) {
                        $weekName = strtolower(date("l",mktime(0,0,0,0,$i,0,0)));
                        $start = "ct.{$weekName}_start";
                        $end   = "ct.{$weekName}_end";

                        $where[] = $db->quoteInto("TIME(?) BETWEEN $start AND $end", $hour);
                    }

                    $select
                        ->joinLeft(array('ct' => 'catalog_time'),
                            'c.id = ct.catalog_id', array())
                        ->where(implode(" OR ", $where));
                } else
                // dzien
                if ($data['week'] > 0) {
                    $week = ((int)$data['week'])-2;
                    $weekName = strtolower(date("l",mktime(0,0,0,0,$week,0,0)));
                    $start = "ct.{$weekName}_start";
                    $end   = "ct.{$weekName}_end";

                    $select->joinLeft(array('ct' => 'catalog_time'),
                   'c.id = ct.catalog_id', array());
                    $select->where("$start > '00:00:00' AND $end > '00:00:00'");
                }
            }
        }

        return $select;
    }
}

require_once 'Zend/Db/Table/Row/Abstract.php';
class Catalog_Row extends Zend_Db_Table_Row_Abstract {
    public function findNearRowset(Zend_Db_Select $select = null) {
        $table = $this->getTable();
        if (null === $select) {
            $select = $table->select();
        }

        $select
        // szukamy w tej samej dzielnicy
        ->where('catalog_district_id = ?', $this->catalog_district_id)
        ->where('lng AND lat <> 0')
        ->order('lng ASC')
        ->order('lat ASC');

        return $table->fetchAll($select);
    }
}