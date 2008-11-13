<?php
require_once 'user/models/User.php';

require_once 'KontorX/Db/Table/Abstract.php';
class Catalog extends KontorX_Db_Table_Abstract {
	protected $_name = 'catalog';
	protected $_rowClass = 'Catalog_Row';
	
	protected $_dependentTables = array(
		'CatalogImage',
		'CatalogServiceCost'
	);
	
	protected $_referenceMap    = array(
        'CatalogDistrict' => array(
            'columns'           => 'catalog_district_id',
            'refTableClass'     => 'CatalogDistrict',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        ),
        'CatalogType' => array(
            'columns'           => 'catalog_type_id',
            'refTableClass'     => 'CatalogType',
            'refColumns'        => 'id',
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