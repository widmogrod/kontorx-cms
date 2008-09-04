<?php
require_once 'KontorX/Db/Table/Abstract.php';
class NewsHasCategory extends KontorX_Db_Table_Abstract {
	protected $_name = 'news_has_news_category';

	protected $_referenceMap    = array(
        'News' => array(
            'columns'           => 'news_id',
            'refTableClass'     => 'News',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        ),
        'Category' => array(
            'columns'           => 'news_category_id',
            'refTableClass'     => 'NewsCategory',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
        	'onDelete'			=> self::CASCADE
        )
    );
}