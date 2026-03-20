<?php

/**
 * ProfileTypeMgt
 *
 * A class for managing profile types and their sub-types
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2022
 * @link        alabiansolutions.com
*/

class ProfileTypeMgtExpection extends Exception
{
}

class ProfileTypeMgt
{
    /** @var Table an instance of Table  */
    protected Table $tblProfileType;

    /** @var string path to setting.json  */
    protected string $path;

    /** @var string table name*/
    public const TABLE = "profile_type";

    /**
     * instantiation of ProfileTypeMgt
     *
     * @param string path the path to setting.json
     */
    public function __construct(string $path = SETTING_FILE)
    {
        $DbConnect = DbConnect::getInstance(SETTING_FILE);
        $this->tblProfileType = new Table(ProfileTypeMgt::TABLE);
        $this->setPath($path);
    }

    /**
     * get the path to the setting.json file
     *
     * @return string path to setting.json file
     */
    public function getPath():string
    {
        return $this->path;
    }

    /**
     * set the path to the setting.json file
     *
     * @param string path the path to the setting.json file
     */
    public function setPath(string $path)
    {
        if (!file_exists($path)) {
            throw new ProfileTypeMgtExpection("setting file missing");
        }
        $this->path = $path;
    }

    /**
     * for retrieving a profile type information
     *
     * @param int $id the profile type id
     * @return array info of the profile type
    */
    public function getInfo(int $id):array
    {
        $info = $this->tblProfileType->retrieveOne($id);
        return $info;
    }

    /**
     * for retrieving a profile type information from profile name
     *
     * @param string $name the profile type name
     * @return array info of the profile type
    */
    public function getInfoFrmName(string $name):array
    {
        $info = $this->tblProfileType->retrieve([], ['name'=>['=', $name, 'isValue']]);
        return $info ? $info[0] : $info;
    }
    
    /**
     * get all the profile types information indexed by profile type id by default
     *
     * @param bool $indexByProfileName determines if array is indexed by profile type name
     * @return array collection of profile types and sub types [type1=>[subType1, subType2,...],...] or [type1, type2...]
     */
    public function getAllProfileTypeInfo(bool $indexByProfileName = false):array
    {
        $info = [];
        
        if ($result = $this->tblProfileType->retrieve()) {
            foreach ($result as $aResult) {
                if ($indexByProfileName) {
                    $info[$aResult['name']] = $aResult;
                } else {
                    $info[$aResult['id']] = $aResult;
                }
            }
        }
        return $info;
    }

    /**
     * get all the sub profile type of a profile type
     *
     * @param string $profile  the profile type
     * @return array collection of sub profile types [subType1, subType2,...]
     */
    public function getSubProfiles(string $profile):array
    {
        $info = [];
        
        if ($result = $this->tblProfileType->retrieve(['subs'], ['name' => ['=', $profile, 'isValue']])) {
            $types = json_decode($result[0]['subs'], true);
            foreach ($types as $aType) {
                $info[] = $aType;
            }
        }

        return $info;
    }

    /**
     * get all the profile types and sub types
     *
     * @param bool includeSubType if the profile sub type should be included in returned
     * @return array collection of profile types and sub types [type1=>[subType1, subType2,...],...] or [type1, type2...]
     */
    public function getAllProfileTypes(bool $includeSubType = true):array
    {
        $allSubs = [];
        $Settings = new Settings($this->path, false);
        
        if ($includeSubType) {
            foreach ($Settings->getDetails()['profileType'] as $type => $aSubType) {
                $allSubs[$type] = $aSubType;
            }
        } else {
            foreach ($Settings->getDetails()['profileType'] as $type => $aSubType) {
                $allSubs[] = $type;
            }
        }
        return $allSubs;
    }

    /**
     * get all the profile types id
     *
     * @return array collection of profile types their id [type1=>id1, type2=>id2...]
     */
    public function getAllProfileTypeIds():array
    {
        $types = [];
        if ($result = $this->tblProfileType->select(['id', 'name'])) {
            foreach ($result as $aResult) {
                $types[$aResult['name']] = $aResult['id'];
            }
        }
        return $types;
    }
     
    /**
     * creation of a new profile type
     *
     * @param string type the new profile type
     * @return bool true if the profile type was created successfully
     */
    public function createProfileType(string $type):bool
    {
        $created = false;
        $Settings = new Settings($this->path, false);
        $settingInfo = $Settings->getAllDetails();
        try {
            $this->tblProfileType->insert(['name' => [$type, 'isValue']]);
            //implement the creation of this profile table
            $created =true;
        } catch (\Throwable $th) {
            $created = false;
        }
        
        if ($created) {
            $settingInfo['profileType'][$type] = [];
            $handle = fopen($this->path, "w");
            fwrite($handle, json_encode($settingInfo));
            fclose($handle);
        }
        return $created;
    }

    /**
     * deletion of a profile type
     *
     * @param string type the profile type to be deleted
     */
    public function deleteProfileType(string $type)
    {
        $Settings = new Settings($this->path, false);
        $settingInfo = $Settings->getAllDetails();
        if ($this->tblProfileType->select([], ['name' => ['=', $type, 'isValue']])) {
            unset($settingInfo['profileType'][$type]);
            $handle = fopen($this->path, "w");
            fwrite($handle, json_encode($settingInfo));
            fclose($handle);
            $this->tblProfileType->delete(['name' => ['=', $type, 'isValue']]);
        } else {
            throw new ProfileTypeMgtExpection("invalid profile type");
        }
    }

    /**
     * creation of a new sub profile type
     *
     * @param string type the profile type
     * @param string subType the new profile sub type
     * @return bool true if the profile type was created successfully
     */
    public function createSubProfileType(string $type, string $subType):bool
    {
        $created = false;
        $Settings = new Settings($this->path, false);
        $settingInfo = $Settings->getAllDetails();

        if ($typeInfo = $this->tblProfileType->select([], ['name' => ['=', $type, 'isValue']])) {
            if (array_search($subType, $settingInfo['profileType'][$type]) === false) {
                $handle = fopen($this->path, "w");
                $settingInfo['profileType'][$type][] = $subType;
                fwrite($handle, json_encode($settingInfo));
                fclose($handle);
                
                $subs = $typeInfo[0]['subs'] ? json_decode($typeInfo[0]['subs'], true) : [];
                $subs[] = $subType;
                $this->tblProfileType->update(['subs'=>[$subs, 'isValue']], ['name' => ['=', $type, 'isValue']]);
                $created = false;
            } else {
                throw new ProfileTypeMgtExpection("Profile Type '$type' already have sub type '$subType'");
            }
        } else {
            throw new ProfileTypeMgtExpection("invalid profile type");
        }
        
        return $created;
    }

    /**
     * deletion of a sub profile type
     *
     * @param string type the profile type
     * @param string subType the profile sub type
     */
    public function deleteSubProfileType(string $type, string $subType)
    {
        $created = false;
        $Settings = new Settings($this->path, false);
        $settingInfo = $Settings->getAllDetails();

        if ($typeInfo = $this->tblProfileType->select([], ['name' => ['=', $type, 'isValue']])) {
            if (($index = array_search($subType, $settingInfo['profileType'][$type])) !== false) {
                $handle = fopen($this->path, "w");
                unset($settingInfo['profileType'][$type][$index]);
                fwrite($handle, json_encode($settingInfo));
                fclose($handle);
                
                $subs = $typeInfo[0]['subs'];
                $subs = json_decode($subs, true);
                $index = array_search($subType, $subs);
                unset($subs[$index]);
                $this->tblProfileType->update(['subs'=>[$subs, 'isValue']], ['name' => ['=', $type, 'isValue']]);
                $created = false;
            } else {
                throw new ProfileTypeMgtExpection("Profile Type '$type' does not have sub type '$subType'");
            }
        } else {
            throw new ProfileTypeMgtExpection("invalid profile type");
        }
    }
}
