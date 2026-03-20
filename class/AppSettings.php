<?php

/**
 * AppSettings
 *
 * A class managing various app settings in app_settings table
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2024
 * @link        alabiansolutions.com
*/

class AppSettingsException extends Exception
{
}

class AppSettings
{
    /** @var dbConnect an instance of DbConnect  */
    protected DbConnect $dbConnect;

    /** @var query an instance of Query  */
    protected Query $query;

    /** @var string the name of the settings  */
    protected string $name;

    /** @var string|array the value of the settings  */
    protected string|array $value;

    /** @var int the id of a settings in the app_settings table  */
    protected int $id;

    /** @var array an app settings info  */
    protected array $info;

    /** @var array costing method values*/
    public const COSTING_METHODS = ['lifo','fifo','average','arbitrary','lifo'=>'lifo','fifo'=>'fifo','average'=>'average','arbitrary'=>'arbitrary'];

    /** @var string table*/
    public const TABLE = "app_settings";

    /**
     * instantiation of AppSettings
     *
     * @param string $settings the name of the settings
     */
    public function __construct(string $name)
    {
        $DbConnect = DbConnect::getInstance(SETTING_FILE);
        $this->dbConnect = $DbConnect;
        $this->query = new Query();

        $table = new Table(AppSettings::TABLE);
        if (!($result = $table->select(where:['setting'=>['=', $name, 'isValue']]))) {
            throw new AppSettingsException("invalid settings");
        }

        $result = $result[0];
        $this->id = $result['id'];
        $this->name = $result['setting'];
        $this->value = $result['value'];
        $this->info = $result;
    }

    /**
     * sql for creating task table
     *
     * @return string
     */
    public static function appSettingsTableSql()
    {
        $sql = "
            START TRANSACTION;
            DROP TABLE IF EXISTS `app_settings`;
            CREATE TABLE IF NOT EXISTS `app_settings` (
                `id` int NOT NULL AUTO_INCREMENT,
                `setting` varchar(255) NOT NULL,
                `value` text NOT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
            ALTER TABLE `app_settings` ADD UNIQUE(`setting`);
            COMMIT;            
        ";

        return $sql;
    }

    /**
     * for getting the id of a setting from the database table
     *
     * @return integer
     */
    public function getId():int
    {
        return $this->id;
    }

    /**
     * for getting the name of a setting from the database table
     *
     * @return string
     */
    public function getName():string
    {
        return $this->name;
    }

    /**
     * for getting the value of a setting from the database table
     *
     * @param bool $isJson true when value from the db table is json then the return value is array
     * @return string|array
     */
    public function getValue(bool $isJson=false):string|array
    {
        $value = $this->value;
        if ($isJson == true) {
            $value = json_decode($value, true);
        }
        return $value;
    }

    /**
     * for changing the value of a setting
     *
     * @param string|array $value the new value for the setting
     * @return void
     */
    public function setValue(string|array $value):void
    {
        $errors = [];

        if ($this->name == "costing_method" && !in_array($value, AppSettings::COSTING_METHODS)) {
            $errors[] = "invalid costing_method value";
        }

        if ($errors) {
            throw new AppSettingsException(implode(", ", $errors));
        }

        $this->value = $value;
        if (is_array($value)) {
            $value = json_encode($value);
            $this->value = $value;
        }

        $table = new Table(AppSettings::TABLE);
        $table->updateOne($this->id, ['value' => [$value, 'isValue']]);
    }

    /**
     * for getting the entire row of a setting from the database table
     *
     * @return array
     */
    public function getInfo():array
    {
        return $this->info;
    }

    /**
     * for getting multiplies settings's value at once
     *
     * @param array $settings a collection of settings to be gotten if empty all settings are returned
     * @param boolean $indexBySetting if the returned array be indexed by setting or row id
     * @return array
     */
    public static function getSettingsValue(array $settings=[], bool $indexBySetting=true):array
    {
        $values = [];
        
        $where = "";
        if ($settings) {
            $where = " WHERE ";
            $kanter = 0;
            $bind = [];
            foreach ($settings as $aSetting) {
                $where .= " setting = :setting{$kanter} OR ";
                $bind["setting{$kanter}"] = $aSetting;
                ++$kanter;
            }
            $where = rtrim($where, "OR ");
        }
        
        $table = AppSettings::TABLE;
        $sql = "SELECT * FROM $table $where";
        $result = $settings ? (new Query())->executeSql($sql, $bind)['rows'] : (new Query())->executeSql($sql)['rows'];

        if ($result) {
            foreach ($result as $aRow) {
                if ($indexBySetting) {
                    $values[$aRow['setting']] = $aRow['value'];
                } else {
                    $values[$aRow['id']] = $aRow;
                }
            }
        }

        return $values;
    }

    /**
     * Retrieves all settings from the database table
     *
     * @param bool $indexBySetting if the returned array be indexed by setting or row id
     * @return array collection of all settings
     */
    public static function getAllSettings(bool $indexBySetting=true):array
    {
        return self::getSettingsValue([], $indexBySetting);
    }
}
