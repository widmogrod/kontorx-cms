<?php
/**
 * Sortuje wydarzenia w lini czasu
 *
 */
class Calendar_View_Helper_TimeLine extends Zend_View_Helper_Abstract {
	protected $_rowset = null;

	protected $_columnTimeStart = null;

	protected $_timeFormat = 'Y-m-d H:i:s';
	
	/**
	 * Przechowuje wydarzenia w określonych okresach czasu
	 *
	 * @var unknown_type
	 */
	protected $_storeTime = array(
		'today' => array(),
		'tomorow' => array(),
		'default' => array(),
		'nextweek' => array(),
		'thisweek' => array(),
		'ealier' => array()
	);

	/**
	 * "Kontruktor"
	 *
	 * @param Zend_Db_Table_Rowset_Abstract $rowset
	 * @param string $columnTimeStart
	 * @return Calendar_View_Helper_TimeLine
	 */
	public function timeLine(Zend_Db_Table_Rowset_Abstract $rowset, $columnTimeStart = null) {
		$this->_rowset = $rowset;
		if (null !== $columnTimeStart) {
			$this->setColumnTimeStart($columnTimeStart);
		}
		return $this;
	}

	/**
	 * Ustawia nazwe kolumny przechowujacej czas
	 *
	 * @param string $column
	 * @return Calendar_View_Helper_TimeLine
	 */
	public function setColumnTimeStart($column) {
		$this->_columnTimeStart = $column;
		return $this;
	}

	/**
	 * Zwraca UNIX timestamp z wiersza DB 
	 *
	 * @param Zend_Db_Table_Row_Abstract $row
	 * @return integer
	 */
	public function getRowTimeStart(Zend_Db_Table_Row_Abstract $row) {
		return strtotime($row->{$this->_columnTimeStart});
	}
	
	/**
	 * Ustawia format czasu
	 *
	 * @param string $timeFormat
	 * @return Calendar_View_Helper_TimeLine
	 */
	public function setTimeFormat($timeFormat) {
		$this->_timeFormat = $timeFormat;
		return $this;
	}

	/**
	 * Inicjuje sortowanie czas
	 *
	 * @return Calendar_View_Helper_TimeLine
	 */
	public function init() {
		require_once 'Zend/Date.php';
		$date  = new Zend_Date();
		$today = new Zend_Date();
		$week  = new Zend_Date(mktime(0,0,0, date('m'), date('d')+7, date('Y')), Zend_Date::TIMESTAMP);

		foreach ($this->_rowset as $row) {
			$rowTimeStart = $this->getRowTimeStart($row);
			$date->set($rowTimeStart);
			switch (true) {
				case $date->isToday(): 			$this->_storeTime['today'][]	= $row; break;
				case $date->isTomorrow(): 		$this->_storeTime['tomorow'][] 	= $row; break;
				case $date->isEarlier($today): 	$this->_storeTime['ealier'][] 	= $row; break;
				case $date->isEarlier($week): 	$this->_storeTime['thisweek'][] = $row; break;
				case $date->isLater($week): 	$this->_storeTime['nextweek'][] = $row; break;
				// nieprzypisane
				default:
					$this->_storeTime['default'][] = $row;
			}
		}
		
		return $this;
	} 

	/**
	 * Zwróć rekordy z: dzisiaj
	 *
	 * @return array
	 */
	public function getToday() {
		return $this->_storeTime['today'];
	}

	/**
	 * Zwróć rekordy z: jutro
	 *
	 * @return array
	 */
	public function getTomorow() {
		return $this->_storeTime['tomorow'];
	}

	/**
	 * Zwróć rekordy z: tego tygodnia
	 *
	 * @return array
	 */
	public function getThisWeek() {
		return $this->_storeTime['thisweek'];
	}

	/**
	 * Zwróć rekordy z: przyszłego tygodnia
	 *
	 * @return array
	 */
	public function getNextWeek() {
		return $this->_storeTime['nextweek'];
	}

	/**
	 * Zwróć rekordy z: przed dzisiaj
	 *
	 * @return array
	 */
	public function getEalier() {
		return $this->_storeTime['ealier'];
	}

	/**
	 * Zwróć rekordy z: nieprzypisane
	 *
	 * @return array
	 */
	public function getDefault() {
		return $this->_storeTime['default'];
	}
}