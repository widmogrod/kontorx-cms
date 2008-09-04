<?php
// zaleznosci
require_once 'user/models/User.php';
require_once 'news/models/News.php';

require_once 'KontorX/Db/Table/Abstract.php';
class Gallery extends KontorX_Db_Table_Abstract {
	protected $_name = 'gallery';
	protected $_rowClass = 'Gallery_Row';
	
	protected $_dependentTables = array(
		'News',
		'GalleryDescription',
		'GalleryImage'
	);

	protected $_referenceMap    = array(
        'Category' => array(
            'columns'           => 'gallery_category_id',
            'refTableClass'     => 'GalleryCategory',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'          => self::CASCADE
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
}

require_once 'KontorX/Db/Table/Row.php';
class Gallery_Row extends KontorX_Db_Table_Row {
	public function findDependentImagesRowset($languageUrl, $galleryId =null, Zend_Controller_Request_Abstract $request) {
		$table = $this->getTable();
		$db    = $table->getAdapter();
		
		require_once 'Zend/Db/Select.php';
		$select = new Zend_Db_Select($db);
		
		$table->selectForSpecialCredentials($request, $select)
			->where('publicated = 1');

		if (is_numeric($galleryId)) {
			$select->where('gallery_id = ?', $galleryId);
		}
			
		$select
			->from(array('gi' => 'gallery_image'))
			->joinLeft(
				array('gid' => 'gallery_image_description'),
				'gi.id = gid.gallery_image_id AND ' . $db->quoteInto('gid.language_url = ?', $languageUrl),
				array('description' => 'gid.description'));

		$stmt = $select->query(Zend_db::FETCH_CLASS);
		return $stmt->fetchAll();
	}
}