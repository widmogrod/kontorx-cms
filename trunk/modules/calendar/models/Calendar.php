<?php
// zaleznosci
require_once 'user/models/User.php';

require_once 'KontorX/Db/Table/Abstract.php';
class Calendar extends KontorX_Db_Table_Abstract {
	protected $_name = 'calendar';

	protected $_dependentTables = array(
		'CalendarContent'
	);
	
	protected $_referenceMap    = array(
		'User' => array(
            'columns'           => 'user_id',
            'refTableClass'     => 'User',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'username',
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
    
    /**
     * Wyszukuje rekordy z okresu czasu
     *
     * @param unknown_type $time
     * @param Zend_Db_Table_Select $select
     */
    public function fetchAllByTime($time, Zend_Db_Table_Select $select = null) {
    	// okres czasowy
		$date 		= date('Y-m-d', $time);

		$timeHour 	= (int) date('H', $time);
		$timeMinute	= (int) date('i', $time);
		$timeDay 	= (int) date('j', $time);
		$daysInMonth= (int) date('t', $time);
		$timeWeek	= (int) round($daysInMonth / $timeDay);
		$timeMonth  = (int) date('n', $time);
		$timeYear 	= (int) date('Y',$time);

		$select = (null === $select)
			? $this->select()
			: $select;

		$select
			->where('t_start <= ?', $date)
			// start blok `t_end`
			->where('(t_end = NULL')
			->orWhere('t_end = "00-00-00 00:00:00"')
			->orWhere('t_end <=  ?)', $date)
			// end blok `t_end`
			->order('t_start ASC');
			
//		$select
//			// wyszukaj rekordy w okreslonym okresie czasu
//			->where('(? BETWEEN t_start AND t_end', $date)
//			// lub gdzie nie ma ustawionego czasu end
//			->orWhere('t_end = "00-00-00 00:00:00" AND t_start > ?)', $date)
//			// wyszukuj powtarzajace się wydarzenia
//			->orWhere('(period_hour = ?', $timeHour)
//			->orWhere('period_day = ?', $timeDay)
//			->orWhere('period_week = ?', $timeWeek)
//			->orWhere('period_month = ?)', $timeMonth);

		return $this->fetchAll($select);
    }

	/**
     * Przygotowuje zapytanie @see Zend_Db_Select
     * 
     * Przygotowuje zapytanie określające, które rekordy
     * mogą zostać wyłowione z BD dla użytkownika.
     * Czy ma uprawnienia do danych rekordów czy nie ..
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Db_Select $select
     * @return Zend_Db_Select
     */
    public function selectForRowOwner(Zend_Controller_Request_Abstract $request, Zend_Db_Select $select) {
    	$select = (null === $select)
    		? $this->select()
    		: $select;

    	$controller = $request->getControllerName();
    	$module	 	= $request->getModuleName();

    	if (!User::hasCredential(User::PRIVILAGE_MODERATE, $controller, $module)) {
    		$userId = User::getAuth(User::AUTH_USERNAME_ID);
    		$select->where('user_id = ?', $userId);
    	}
    	
    	return $select;
    }
}