<?php

/**
 * Settings
 *
 * A class for handling the app setting information
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2022
 * @link        alabiansolutions.com
 */

class SettingsExpection extends Exception
{
}

class Settings
{
    /** @var string absolute path of the setting file */
    protected string $path;

    /** @var bool determine if setting details are returns as object or array  */
    protected bool $isObject;

    /**
     * instantiation of Setting
     *
     * @param string path the absolute path from the system root directory for the setting json file
     * @param boolean isObject true then an object will be return else array
     */
    public function __construct(string $path, bool $isObject = true)
    {
        $this->path = $path;
        $this->isObject = $isObject;
    }

    /**
     * get the absolute path from the system root directory for the setting json file
     *
     * @return string
     */
    public function getPath():string
    {
        return $this->path;
    }

    /**
     * set the absolute path from the system root directory for the setting json file
     *
     * @param string path the absolute path from the system root directory for the setting json file
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * check the data type been returned by the class; true for Object, false for Array
     *
     * @return bool true for Object false for Array
     */
    public function getIsObject():bool
    {
        return $this->isObject;
    }

    /**
     * set the data type to be returned by the class; true for Object, false for Array
     *
     * @param bool isObject true for Object false for Array
     */
    public function setIsObject(bool $isObject)
    {
        $this->isObject = $isObject;
    }

    /**
     * get app setting information
     *
     * @return array|object app setting information
     */
    public function getDetails(): array|object
    {
        $details = [];
        
        if (($fileHandle = fopen($this->path, 'r')) === false) {
            throw new SettingsExpection("failed to open settings.json file for reading");
        } else {
            if (pathinfo($this->path, PATHINFO_EXTENSION) !== 'json') {
                throw new SettingsExpection("setting file '{$this->path}' is not a json file");
            }
            $details = json_decode(fread($fileHandle, filesize($this->path)), true);
            if (!$details) {
                throw new SettingsExpection("failed to decode setting.json file");
            }
            fclose($fileHandle);

            if (!in_array($details['mode'], ['production', 'development'])) {
                throw new SettingsExpection("mode must be production or development");
            }
            if ($details['mode'] == 'development') {
                unset($details['production']);
                $infos = $details['development'];
                unset($details['development']);
            } else {
                unset($details['development']);
                $infos = $details['production'];
                unset($details['production']);
            }
        }

        foreach ($infos as $key => $info) {
            $details[$key] = $info;
        }
        $details = $this->isObject ?  json_decode((json_encode($details))) : $details;
        return $details;
    }

    /**
     * get all app setting information without removing some
     *
     * @return array|object app setting information
     */
    public function getAllDetails(): array|object
    {
        $details = [];
        
        if (($fileHandle = fopen($this->path, 'r')) === false) {
            throw new SettingsExpection("failed to open settings.json file for reading");
        } else {
            if (pathinfo($this->path, PATHINFO_EXTENSION) !== 'json') {
                throw new SettingsExpection("setting file '{$this->path}' is not a json file");
            }
            $details = json_decode(fread($fileHandle, filesize($this->path)), true);
            if (!$details) {
                throw new SettingsExpection("failed to decode setting.json file");
            }
            fclose($fileHandle);
        }

        $details = $this->isObject ?  json_decode((json_encode($details))) : $details;
        return $details;
    }
}
