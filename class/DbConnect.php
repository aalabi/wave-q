<?php

/**
 * DbConnect
 *
 * A class for handling connection to the database
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2022
 * @link        alabiansolutions.com
 */

class DbConnectExpection extends Exception
{
}

class DbConnect extends PDO
{
    /** @var  string indication of mysql dsn for connection */
    public const MYSQL = "mysql";

    /** @var  string indication of mssql dsn for connection */
    public const MSSQL = "mssql";

    /** @var PDO a static variable to hold single instance  */
    private static PDO|null $instance = null;

    /**
     * instantiation of DbConnect
     * @param string string path the absolute path from the system root directory for the setting json file
     * @param string dbms the database mgt software been connected
     */
    public function __construct(string $path, string $dbms = DbConnect::MYSQL)
    {
        $Settings = new Settings($path, true);
        $db = $Settings->getDetails()->database;

        if ($dbms == DbConnect::MYSQL) {
            try {
                parent::__construct("mysql:dbname={$db->name};charset=utf8mb4;host={$db->host}", $db->username, $db->password);
            } catch (PDOException $e) {
                throw new DbConnectExpection("Db Connection Error: " . $e->getMessage());
            }
        }
        
        if ($dbms == DbConnect::MSSQL) {
            try {
                if (!$db->port) {
                    parent::__construct("sqlsrv:Server={$db->host};Database={$db->name}", $db->username, $db->password);
                } else {
                    parent::__construct("sqlsrv:Server={$db->host},{$db->port};Database={$db->name}", $db->username, $db->password);
                }
            } catch (PDOException $e) {
                throw new DbConnectExpection("Db Connection Error: " . $e->getMessage());
            }
        }
    }

    /**
     * getting a singleton instant of DbConnect
     * @param string string path the absolute path from the system root directory for the setting json file
     * @param string dbms the database mgt software been connected to (mysql, mssql)
     */
    public static function getInstance(string $path, string $dbms = DbConnect::MYSQL):PDO
    {
        if (!in_array($dbms, [DbConnect::MYSQL, DbConnect::MSSQL])) {
            throw new DbConnectExpection("Db Connection Error: unsupport RDMS");
        }

        if (!(self::$instance instanceof DbConnect)) {
            try{
                self::$instance = new DbConnect($path, $dbms);
            }
            catch (DbConnectExpection $e) {
                throw new DbConnectExpection("Db Connection Error: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
