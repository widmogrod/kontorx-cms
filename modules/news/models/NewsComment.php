<?php
require_once 'KontorX/Db/Table/Abstract.php';
class NewsComment extends KontorX_Db_Table_Abstract  {
	protected $_name = 'news_comment';

	protected $_referenceMap    = array(
        'News' => array(
            'columns'           => 'news_id',
            'refTableClass'     => 'News',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        )
    );
}