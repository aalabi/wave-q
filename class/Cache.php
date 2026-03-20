<?php
namespace Xbook;
/**
 * Cache
 * This class is used for managing cached data
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   2025 Alabian Solutions Limited
 * @version     1.0 => January 2025
 * @link        alabiansolutions.com
 */

class CacheException extends \Exception
{
}

class Cache
{   
    /** @var string the cached data's name  */
    protected string $name;

    /** @var the cached data  */
    protected $data;

    /** @var string the name of the database for storing all cached data, the session index */
    protected static string $dbName;

    /** @var array the database where all cached data are stored  */
    protected static array $db = [];

    /**
     * instantiation of Cache
     *
     * @param string $name the name of the data to be cached
     * @param $data been cached
     * @throws CacheException If the specified cache name does not exist in the cache database
     */
    public function __construct(string $name, $data = null)
    {
        if (!isset(self::$db[$name])) {
            throw new CacheException("Non-existing cache: $name");
        }
        
        $this->name = $name;
        $this->data = $data ? $data : self::$db[$name];
        self::$dbName = self::generateDbName();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION[self::$dbName] = [$name => $data];
        self::$db = $_SESSION[self::$dbName];
    }

    /**
     * for getting the name used in storing the cache in session
     *
     * @return string the cache name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * for getting the data in the cache
     *
     * @return the cached data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * for getting the cache database name
     *
     * @return string the cache database name
     */
    public static function getDbName(): array
    {
        return self::$db;
    }

    /**
     * for getting the cache database
     *
     * @return array the cache database
     */
    public static function getDb(): array
    {
        return self::$db;
    }

    /**
     * for generating the name used in storing the cache in session
     *
     * @return string the cache db 
     * @throws CacheException If the setting file does not exist
     */
    protected static function generateDbName(): string
    {
        if (!file_exists(SETTING_FILE)) {
            throw new CacheException("Settings file not found");
        }

        $Settings = new \Settings(SETTING_FILE);
        $token = $Settings->getDetails()->token;
        return "cache_{$token}";
    }

    /**
     * for adding a new data to the cache
     *
     * @param string $name the name of the cache
     * @param string $data the data to be stored in the cache
     * @return Cache
     * @throws CacheException If cache been added already exists
     */
    public static function add(string $name, $data): Cache
    {
        if (self::has($name)) {
            throw new CacheException("Cache already exists: $name");
        }

        self::$db[$name] = $data;        
        return new Cache($name, $data);
    }

    /**
     * Removes a specific cache by name
     *
     * @param string $name the name of the cache to remove
     */
    public function remove(string $name)
    {
        unset(self::$db[$name]);
        $_SESSION[$this->dbName] = self::$db;
    }

    /**
     * Updates the data of the cache
     *
     * @param string $data The new data for the cache
     */
    public function change(string $data)
    {        
        self::$db[$this->name] = $data;
        $_SESSION[$this->dbName] = self::$db;
        $this->data = $data;
    }

    /**
     * clear the cache
     *
     * @return void
     */
    public static function clearAll()
    {
        self::$db = [];
        $_SESSION[self::$dbName] = [];
    }

    /**
     * Checks if a cache with the specified name exists in the database.
     *
     * @param string $name The name of the cache to check
     * @return bool true if the cache exists, false otherwise
     */
    public static function has(string $name): bool
    {
        return isset(self::$db[$name]);
    }
}
