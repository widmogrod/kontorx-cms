<?php
require_once 'user/models/User.php';

require_once 'KontorX/Db/Table/Abstract.php';
class Catalog extends KontorX_Db_Table_Abstract {
	protected $_name = 'catalog';
	protected $_rowClass = 'Catalog_Row';
	
	protected $_dependentTables = array(
		'CatalogTime',
		'CatalogImage',
		'CatalogServiceCost',
		'CatalogPromoTime',
		'CatalogHasCatalogOptions'
	);
	
	protected $_referenceMap    = array(
        'CatalogDistrict' => array(
            'columns'           => 'catalog_district_id',
            'refTableClass'     => 'CatalogDistrict',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
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
			'onDelete'			=> self::CASCADE
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
     * @return Zend_Db_Select
     */
    public function selectForListPromoPlus($districtId = null) {
    	require_once 'Zend/Db/Select.php';
		$select = new Zend_Db_Select($this->getAdapter());

		$select
			->from(array('c' => 'catalog'),'*')
			->join(array('cd' => 'catalog_district'),
					'cd.id = c.catalog_district_id',
						array(
							'district_url' => 'cd.url',
							'district' => 'cd.name'))
			->joinInner(array('cpt' => 'catalog_promo_time'),
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

			/***/
			->order('cpt.catalog_promo_type_id DESC')
			->order('c.name ASC')
			->where('cpt.catalog_promo_type_id = 3');	// tylko promocujne +

		// dodatkowy filtr na obszary
   		if (null !== $districtId) {
			$select->where('c.catalog_district_id = ?', $districtId, Zend_Db::INT_TYPE);
		}
			
		return $select;
    }
    
    /**
     * Zwraca zapytanie pobierające rekordy dal domyślnych rekordów
     * z sortowaniem rekordów promo (NIE promo plus)
     * 
     * @return Zend_Db_Select
     */
    public function selectForListDefault($districtId = null) {
    	require_once 'Zend/Db/Select.php';
		$select = new Zend_Db_Select($this->getAdapter());

		$select
			->from(array('c' => 'catalog'),'*')
			->join(array('cd' => 'catalog_district'),
					'cd.id = c.catalog_district_id',
						array(
							'district_url' => 'cd.url',
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
		
	    // dodatkowy filtr na obszary
		if (null !== $districtId) {
			$select->where('c.catalog_district_id = ?', $districtId, Zend_Db::INT_TYPE);
		}
			
		return $select;
    }
    
	/**
     * @return string 
     */
    public function fetchAllForMap() {
    	$db = $this->getAdapter();

    	require_once 'Zend/Db/Select.php';
    	$select = new Zend_Db_Select($db);

    	$select
    		->from('catalog')
    		->joinInner(
    			'catalog_type',
    			'catalog.catalog_type_id = catalog_type.id',
    			array('type_ico' => 'ico','type_name' => 'name')
    		)
    		->joinInner(
    			'catalog_district',
    			'catalog.catalog_district_id = catalog_district.id',
    			array(
    				'district_url' => 'url',
    				'district_name'=> 'name'
    			)
    		)
    		->where('catalog.lng AND catalog.lat <> 0');

    	$stmt = $select->query();

    	// TODO Zrobic jako global
    	$typeHref = 'upload/catalog/ico/';

    	$rowset = array();
    	while ($row = $stmt->fetch()) {
    		$row['type_ico_href'] = $typeHref . $row['type_ico'];
    		$rowset[] = $row;
    	}
    	return $rowset;
    }
    
    /**
     * @return string 
     */
    public function fetchAllAsKml() {
    	// Creates the Document.
		$dom = new DOMDocument('1.0', 'UTF-8');
		
		// Creates the root KML element and appends it to the root document.
		$node = $dom->createElementNS('http://earth.google.com/kml/2.1', 'kml');
		$parNode = $dom->appendChild($node);
		
		// Creates a KML Document element and append it to the KML element.
		$dnode = $dom->createElement('Document');
		$docNode = $parNode->appendChild($dnode);
		
		// creat style/type
		require_once 'CatalogType.php';
    	$catalogType = new CatalogType();
    	$typeRowset  = $catalogType->fetchAll();
    	
    	foreach ($typeRowset as $type) {
    		// Creates the two Style elements, one for restaurant and one for bar, and append the elements to the Document element.
			$restStyleNode = $dom->createElement('Style');
			$restStyleNode->setAttribute('id', $type->id);
			$restIconstyleNode = $dom->createElement('IconStyle');
			$restIconstyleNode->setAttribute('id', $type->id);
			$restIconNode = $dom->createElement('Icon');
			$restHref = $dom->createElement('href', 'http://localhost/upload/catalog/ico/' . $type->ico);
			$restIconNode->appendChild($restHref);
			$restIconstyleNode->appendChild($restIconNode);
			$restStyleNode->appendChild($restIconstyleNode);
			$docNode->appendChild($restStyleNode);
    	}

    	// fetch place nodes
    	$select = $this->select()
			// szukamy w tej samej dzielnicy
			->where('lng AND lat <> 0');
    	$catalogRowset = $this->fetchAll($select);
    	foreach ($catalogRowset as $i => $row) {
    		// Creates a Placemark and append it to the Document.
			$node = $dom->createElement('Placemark');
			$placeNode = $docNode->appendChild($node);
			
			// Creates an id attribute and assign it the value of id column.
			$placeNode->setAttribute('id', 'placemark' . $row->id);
			
			// Create name, and description elements and assigns them the values of the name and address columns from the results.
			$nameNode = $dom->createElement('name',htmlspecialchars($row->name));
			$placeNode->appendChild($nameNode);
			$descNode = $dom->createElement('description', htmlspecialchars($row->adress));
			$placeNode->appendChild($descNode);
			$styleUrl = $dom->createElement('styleUrl', '#' . $row->catalog_type_id . 'Style');
			$placeNode->appendChild($styleUrl);
			
			// Creates a Point element.
			$pointNode = $dom->createElement('Point');
			$placeNode->appendChild($pointNode);
			
			// Creates a coordinates element and gives it the value of the lng and lat columns from the results.
			$coorStr = $row->lng . ','  . $row->lat;
			$coorNode = $dom->createElement('coordinates', $coorStr);
			$pointNode->appendChild($coorNode);
    	}
    	
    	return $dom->saveXML();
    }
    
    public function saveCacheMapData ($data, $format, $path) {
    	switch ($format) {
			case 'json':
				$file = 'data.json';
				require_once 'Zend/Json.php';
				$data = Zend_Json::encode($data);
				break;
			default:
				return false;
		}

		$filename = $path . DIRECTORY_SEPARATOR . $file;
    	if (@file_put_contents($file, $data)) {
			@chmod($file, 0655);
		}
    }
    
    public function clearCacheMapData ($path) {
    	if (!is_dir($path)) {
    		require_once 'Catalog/Exception.php';
    		throw new Catalog_Exception("Katalog nie istnieje");
    	}

    	// nazwy plików jakie są keszowane
    	$files = array('data.json');
    	
    	foreach ($files as $file) {
    		$filename = $path . DIRECTORY_SEPARATOR . $file;
    		if (is_file($filename)) {
    			if (!@unlink($filename)) {
    				require_once 'Catalog/Exception.php';
    				throw new Catalog_Exception("Nie można usunąc pliku '$file'");
    			}
    		}
    	} 
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