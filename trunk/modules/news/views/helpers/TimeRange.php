<?php
require_once 'Zend/View/Helper/Abstract.php';
class News_View_Helper_TimeRange extends Zend_View_Helper_Abstract {
	public function timeRange(Zend_Db_Table_Rowset_Abstract $rowset = null) {
		if (null === $rowset) {
			$rowset = $this->_fetchTimeRange();
		}
		if (null !== $rowset) {
			$rowset = $this->_prepareRowset($rowset);
		}
		$this->view->rowset = $rowset;
		return $this;
	}

	protected function _fetchTimeRange() {
		require_once 'news/models/News.php';
		$news = new News();
		try {
			return $news->fetchTimeRange();
		} catch (Zend_Db_Table_Exception $e) {
			return array();
		}
	}

	protected function _prepareRowset(Zend_Db_Table_Rowset_Abstract $rowset) {
		$result = array();
		foreach ($rowset as $row) {
			if (!isset($result[$row->year])) {
				$result[$row->year] = array();
			}
			$result[$row->year][] = $row->month;
		}
		return $result;
	}

	public function __toString() {
		return (string) $this->view->render('_helpers/time-range.phtml');
	}
}