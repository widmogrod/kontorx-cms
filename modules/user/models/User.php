<?php
// TODO required_once zaleznosci
require_once 'user/models/Role.php';
require_once 'page/models/Page.php';
require_once 'gallery/models/Gallery.php';
require_once 'news/models/News.php';

require_once 'Zend/Db/Table/Abstract.php';
class User extends Zend_Db_Table_Abstract {
	// przyprawy dla haskowania kluczy
	const SALT_PASSWORD = "!@#Kn_)<>?@!#_   >_)@#NJOIksa|}{P";
	const SALT_KEY 		= "!@#><M@21.3,m .3,m12 3.,m@!#.,m231.1 2.3,m";

	// wartosc w tozsamosci
	const AUTH_USERNAME 	= 'username';
	const AUTH_USERNAME_ID  = 'id';
	const AUTH_EMAIL 		= 'email';
	const AUTH_ROLE 		= 'role';
	// extended
	const AUTH_INTERFACE	= 'interface';
	
	/**
	 * Uprawnienia do edycji obcych rekordow
	 */
	const PRIVILAGE_MODERATE = 'privilage_moderate';

	/**
	 * Pokazuje że można wyłowić z BD tylko rekordy dla goscia
	 */
	const PRIVILAGE_VIEW_GUEST  = 'privilage_view_guest';

	/**
	 * Pokazuje że można wyłowić z BD tylko rekordy dla zalogowanego użytkownika
	 */
	const PRIVILAGE_VIEW_MEMBER  = 'privilage_view_member';

	/**
	 * Pokazuje że można wyłowić z BD tylko "specjalne rekordy"
	 */
	const PRIVILAGE_VIEW_SPECIAL  = 'privilage_view_special';

	/**
	 * Nazwa uprawnienia jakiej wartosci pola odpowiada w BD
	 *
	 * @var array
	 */
	protected static $_privilagesDBValues = array(
		self::PRIVILAGE_VIEW_GUEST 	=> 0,
		self::PRIVILAGE_VIEW_MEMBER 	=> 1,
		self::PRIVILAGE_VIEW_SPECIAL => 2
	);
	
	/**
	 * Instancja @see Zend_Auth
	 * 
	 * @var Zend_Auth
	 */
	protected static $_auth = null;

	/**
	 * Tablica dostępnych wartość tożsamości
	 *
	 * @var array
	 */
	protected static $_authAvalible = array(
		self::AUTH_USERNAME,
		self::AUTH_USERNAME_ID,
		self::AUTH_EMAIL,
		self::AUTH_ROLE,
		// ext
		self::AUTH_INTERFACE
	);

	protected $_name = 'user';

	protected $_dependentTables = array(
//		'UserPersonal',
//		'Calendar',
		'Page',
		'Gallery',
//		'Image',
		'News',
//		'Group','GroupHasUser'
	);
	
	protected $_referenceMap    = array(
        'Role' => array(
            'columns'           => 'role',
            'refTableClass'     => 'Role',
            'refColumns'        => 'name_sanitized',
			'refColumnsAsName'  => 'name'
        )
    );
	
	/**
	 * Generowanie hasła
	 *
	 * @param string $username
	 * @param string $password
	 * @return string
	 */
	public static function saltPassword($username, $password) {
		return md5($username . self::SALT_PASSWORD . $password);
	}

	/**
	 * Generowanie klucza do aktywacji konta
	 *
	 * @param string $username
	 * @param string $password
	 * @return string
	 */
	static public function genKey($username, $password) {
		return md5($username . self::SALT_KEY . $password);
	}

	/**
	 * Zwraca autoryzację lub wartosc z tozsamosci autoryzacji
	 *
	 * @param string $value
	 * @param mixed $default
	 * @return Zend_Auth|string|null
	 */
	static public function getAuth($value = null, $default = null) {
		// pobieranie @see Zend_Auth
		if (null === self::$_auth) {
			self::$_auth = Zend_Auth::getInstance();
		}
		// return  @see Zend_Auth
		if (null === $value) {
			return self::$_auth;
		}
		// sprawdzam czy jest dostepny klucz w tozsamosci
		if (!in_array($value, self::$_authAvalible)
				|| !self::$_auth->hasIdentity()) {
			// TODO Moze exception ?
			return false;
		}

		$identity = self::$_auth->getIdentity();
		return isset($identity->{$value})
			? $identity->{$value}
			: $default;
	}

	/**
	 * Ustawia wartości tożsamości
	 *
	 * @param Zend_Auth_Adapter_Interface|array|string $identity
	 */
	public static function setIdentity($identity) {
		// jezeli identity
		if($identity instanceof Zend_Auth_Adapter_Interface) {
			$identity = $identity->getResultRowObject(null, array('password'));
		}

		if (is_array($identity)) {
			$data = new stdClass();
			foreach ($identity as $key => $value) {
				$data->{$key} = $value;
			}
		} else
		if (is_object($identity)){
			$data = $identity;
		} else {
			$data = (array) $identity;
		}

		self::getAuth()
			->getStorage()
			->write($data);
	}

	/**
	 * Sprawdza czy urzytkownik posiada uprawnienia
	 * 
	 * Przydatne w akcjach, które wymagaja dodatkowego
	 * sprawdzenia uprawnien - tj. uprawnien do jakiejś
	 * sekcji.
	 *
	 * @param string $privilege
	 * @param Zend_Controller_Request_Abstract|string $controller
	 * @param string|null $module
	 * @return bool
	 */
	public static function hasCredential($privilege, $controller = null, $module = null) {
		if ($controller instanceof Zend_Controller_Request_Abstract) {
			$module 	= $controller->getModuleName();
			$controller = $controller->getControllerName();
		}

		$acl 	  = KontorX_Acl::getInstance();
		$role 	  = self::getAuth(self::AUTH_ROLE);
		$resource = strtolower("{$module}_{$controller}");

		if ($acl->has($resource)
				&& $acl->hasRole($role)
					&& $acl->isAllowed($role, $resource, $privilege)) {
			return true;
		}
		return false;
	}

	public static function selectForRowOwner(Zend_Db_Select $select, $controller, $module) {
		
	}
	
	/**
	 * Zmienia zapytanie @see Zend_Select
	 * 
	 * Określa jakie rekordy można wyłowic na podstawie uprawnien uzytkownika
	 * Warunkiem, który musi być spełniony to to, że tabela BD musi posiadać
	 * takie pola, w których będą zapisane dane o widoczności default "visible"!
	 *
	 * @param Zend_Db_Select $select
	 * @param string $field
	 * @param Zend_Controller_Request_Abstract|string $controller
	 * @param string $module
	 */
	public static function selectForSpecialCredentials(Zend_Db_Select $select, $column, $controller, $module) {
		if ($controller instanceof Zend_Controller_Request_Abstract) {
			$module 	= $controller->getModuleName();
			$controller = $controller->getControllerName();
		}

		$acl 	  = KontorX_Acl::getInstance();
		$role 	  = self::getAuth(self::AUTH_ROLE);
		$resource = strtolower("{$module}_{$controller}");

		$result = array();
		$result[] = $column . ' = ' .  self::$_privilagesDBValues[self::PRIVILAGE_VIEW_GUEST];

		// czy jest dozstęp
		if ($acl->has($resource) && $acl->hasRole($role)) {
			// czy użytkownik posiada odpowiednie przywileje ?
			if ($acl->isAllowed($role, $resource, self::PRIVILAGE_VIEW_MEMBER)) {
				$result[] = ($column . ' = ' .  self::$_privilagesDBValues[self::PRIVILAGE_VIEW_MEMBER]);
			}
			if ($acl->isAllowed($role, $resource, self::PRIVILAGE_VIEW_SPECIAL)) {
				$result[] = ($column . ' = ' .  self::$_privilagesDBValues[self::PRIVILAGE_VIEW_SPECIAL]);
			}
		}

		$select->where(implode(' OR ', $result));
	}
}
