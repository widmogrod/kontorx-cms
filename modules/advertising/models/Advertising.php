<?php
class Advertising_Model_Advertising_Exception extends Exception {}

/**
 * Description of Advertising
 *
 * @author gabriel
 */
class Advertising_Model_Advertising {
    // katalog zapisu danych
    const CONFIG_DIRNAME = TMP_PATHNAME;
    // nazwa pliku
    const CONFIG_FILENAME = 'advertising_data.php';

    // reklama ograniczona ilością kliknięć
    const CLICK = 'CLICK';
    // reklama ograniczona ilością wyświetleń
    const DISPLAY = 'DISPLAY';
    // reklama ograniczona czasem
    const TIME = 'TIME';

    /**
     * @param Zend_Config $config
     */
    private function  __construct(Zend_Config $config = null) {
        if (null !== $config) {
            $this->setConfig($config);
        }
    }

    /**
     * @var Advertising_Model_Advertising
     */
    private static $_instance = null;

    /**
     * @param Zend_Config $config
     * @return Advertising_Model_Advertising
     */
    final public static function getInstance(Zend_Config $config = null) {
        if (null === self::$_instance) {
            self::$_instance = new self($config);
        }
        return self::$_instance;
    }

    /**
     * @return Zend_Config
     */
    private $_config = null;

    /**
     * @return Zend_Config
     */
    public function getConfig() {
        return $this->_config;
    }

    /**
     * @return Advertising_Model_Advertising
     */
    public function setConfig(Zend_Config $config) {
        if (isset($config->dirname)) {
            $this->_setDirname($config->dirname);
        }
        if (isset($config->filename)) {
            $this->_setFilename($config->filename);
        }

        $this->_config = $config;
        return $this;
    }

    /**
     * Zwraca tresci reklam dla bloku reklamowego
     * @return array
     */
    public function getBlockContents($block) {
        $block = strtolower($block);

        $data = $this->_getData();
        if (!isset($data->{$block})) {
            return "";
        }

        $result = array();
        foreach ($data->{$block} as $row) {
            if (null !== ($rowData = $this->_prepareData($row->toArray()))) {
                $result[] = $row->content;
                // flagana modyfikowany
                $this->_setModify($block, $row->id);
            }
        }
        return $result;
    }

    public function updateDB() {
        $data = $data = $this->_getData();
        if (!count($data)) {
            return;
        }

        $advertise = new AdvertisingAdvertise();

        $db = $advertise->getAdapter();
        $db->beginTransaction();
        try {
            foreach ($data->toArray() as $group => $rows) {
                foreach ((array) $rows as $row) {
                    $data = array();

                    // aktualizuje liczbe wyswietlen
                    $views = (int) $row['views'];
                    if ($views > 0) {
                       $data['views'] = $views;
                    }

                    $where = $db->quoteInto("id = ?", (int) $row['id']);
                    $advertise->update($data, $where);
                }
            }
            $db->commit();
        } catch (Zend_Db_Table_Exception $e) {
            $db->rollBack();

            Zend_Registry::get('logger')
            ->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::CRIT);

            $message = "DB error occure in update, check logs!";
            throw new Advertising_Model_Advertising_Exception($message);
        }
    }

    /**
     * Aktualizije plik z danymi reklamowymi
     * @return void
     */
    public function  updateData() {
        if (!count($modify = $this->_getModify())
                || null === $this->_data) {
            return;
        }

        $data = $this->_getData();
        foreach ($modify as $block => $advId) {
            if (isset($data->{$block})) {
                foreach ($data->{$block} as $row) {
                    if (in_array($row->id, $advId)) {
                        $row->views = 1 +(int) $row->views;
                    }
                }
            }
        }

        try {
            $this->_save($this->_data);
        } catch (Exception $e) {
            Zend_Registry::get('logger')
            ->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::CRIT);
        }
    }

    /**
     * Generuje plik konfiguracujny (z danymi reklam) na podstawie BD
     * @return void
     * @throws Advertising_Model_Advertising_Exception
     */
    public function generateData() {
        $advertise = new AdvertisingAdvertise();

        try {
            $rowset = $advertise->fetchAll();
        } catch (Zend_Db_Table_Exception $e) {
            $message = 'Problem occure: '.$e->getMessage();
            throw new Advertising_Model_Advertising_Exception($message);
        }

        // grupowanie po bloku reklamowym
        $data = array();
        foreach ($rowset as $row) {
            $block = strtolower($row->advertising_block);

            // nowa grupa advertise
            if (!isset($data[$block])) {
                $data[$block] = array();
            }

            $rowData = $row->toArray();
            $rowData['type'] = $row->advertising_type;
            $rowData['block'] = $block;

            // przygotowanie danych
            if (null !== ($rowData = $this->_prepareData($rowData))) {
                $data[$block][] = $rowData;
            }
        }

        try {
            $this->_save($data);
        } catch (Zend_Config_Exception $e) {
            throw new Advertising_Model_Advertising_Exception($e->getMessage());
        }
    }

    /**
     * Przygotuj dane
     * @return array
     */
    protected function _prepareData(array $row) {
        $result = array();
        
        // dane w tabeli musza posiadac odpowiednik w kodzie..
        // (przynajmniej na razie)
        $type = strtoupper($row['type']);
        switch ($type) {
            case self::CLICK:
                // sprawdza czy warunki sa sprawdzone
                if ($row['clicks'] > $row['clicks_max']) {
                    return;
                }
                $result['clicks']     = $row['clicks'];
                $result['clicks_max'] = $row['clicks_max'];
                break;
            case self::DISPLAY:
                // sprawdza czy warunki sa sprawdzone
                if ($row['views'] > $row['views_max']) {
                    return;
                }
                $result['views_max'] = $row['views_max'];
                break;
            case self::TIME:
                try {
                    require_once 'Zend/Date.php';
                    $date = new Zend_Date();

                    if (!$date->isEarlier($row['t_end']) || !$date->isLater($row['t_start'])) {
                        return;
                    }

                    $result['t_start'] = $row['t_start'];
                    $result['t_end']   = $row['t_end'];
                } catch (Zend_Date_Exception $e) {
                    return;
                }
                break;
        }

         $result['id']      = $row['id'];
         $result['type']    = $row['type'];
         $result['views']   = $row['views'];
         $result['content'] = $row['content'];

         return $result;
    }

    /**
     * Zapisuje dane
     * @param Zend_Config|array $data
     * @return void
     * @throws Zend_Config_Exception
     */
    protected function _save($data) {
        $data = ($data instanceof Zend_Config)
            ? $data : new Zend_Config($data);

        require_once 'Zend/Config/Writer/Array.php';
        $writer = new Zend_Config_Writer_Array();
        $writer->write($this->_getPathname(), $data, true);
    }

    /**
     * @var string
     */
    private $_filename = null;

    /**
     * @param string $filename
     * @return Advertising_Model_Advertising
     */
    private function _setFilename($filename) {
        $this->_filename = $filename;
    }

    /**
     * @return string
     */
    private function _getFilename() {
        if (null === $this->_filename) {
            $this->_filename = self::CONFIG_FILENAME;
        }
        return $this->_filename;
    }

    /**
     * @var string
     */
    private $_dirname = null;

    /**
     * @param string $dirname
     * @return Advertising_Model_Advertising
     */
    private function _setDirname($dirname) {
        $this->_dirname = $dirname;
    }

    /**
     * @return string
     */
    private function _getDirname() {
        if (null === $this->_dirname) {
            $this->_dirname = self::CONFIG_DIRNAME;
        }
        return $this->_dirname;
    }

    /**
     * @return string
     */
    private function _getPathname() {
        return $this->_getDirname() . DIRECTORY_SEPARATOR . $this->_getFilename();
    }

    /**
     * @var Zend_Config
     */
    private $_data = null;

    /**
     * @return Zend_Config
     */
    private function _getData() {
        if (null === $this->_data) {
            try {
                $filename = $this->_getPathname();
                if (file_exists($filename)) {
                    $this->_data = new Zend_Config(require $this->_getPathname(), true);
                }
            } catch (Zend_Config_Exception $e) {
                trigger_error($e->getMessage(), E_USER_NOTICE);
            }
        }
        return $this->_data;
    }

    /**
     * @var array
     */
    private $_modify = array();

    /**
     * Ustawia informacje jaka reklama zostala wyswietlona
     *
     * (obecnie tylko wyswietlanie)
     * @param string $blockName
     * @return void
     */
    private function _setModify($blockName, $advId) {
        if (isset($this->_modify[$blockName])) {
            if (!in_array($advId,$this->_modify[$blockName])) {
                $this->_modify[$blockName][] = $advId;
            }
        } else {
            $this->_modify[$blockName] = array($advId);
        }
    }

    /**
     * @return array
     */
    private function _getModify() {
        return $this->_modify;
    }
}
