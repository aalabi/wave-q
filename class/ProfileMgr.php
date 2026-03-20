<?php

/**
 * ProfileMgr
 *
 * A class managing profile table columns
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2023
 * @link        alabiansolutions.com
*/

class ProfileMgrExpection extends Exception
{
}

class ProfileMgr extends ColumnMgr
{
    /** @var dbConnect an instance of DbConnect  */
    protected DbConnect $dbConnect;

    /** @var query an instance of Query  */
    protected Query $query;

    /** @var array collection of permitted image file extension */
    protected const GENDER_VALUES = ['male','female', 'male'=>'male', 'female'=>'female'];

    /** @var string table name*/
    public const TABLE = "profile";

    /**
     * instantiation of ProfileMgr
     *
     */
    public function __construct()
    {
        $DbConnect = DbConnect::getInstance(SETTING_FILE);
        $this->dbConnect = $DbConnect;
        $this->query = new Query();
    }

    /**
     * for creating a table called ProfileMgr using the structure supplied
     *
     * @param array $structure a specification of the table structure
     * @return void
     */
    public static function createTable(array $structure)
    {
        //TODO: implement this later
    }

    /**
     * for validating logger column
     *
     * @param int $logger the logger value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateLogger(int $logger):array
    {
        $validation['data'] = $logger;
        $validation['errors'] = [];
        if(!(new Table(LoggerMgr::TABLE))->find("id", $logger, ['id'])) {
            $validation['data'] = null;
            $validation['errors'] = ['invalid logger'];
        }
        return ['data'=>$validation['data'], 'errors'=>($validation['errors'] ? $validation['errors'] : [])];
    }

    /**
     * for validating profile_type column
     *
     * @param int $profileType the profile_type value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateProfileType(int $profileType):array
    {
        $validation['data'] = $profileType;
        $validation['errors'] = [];
        if(!(new Table("profile_type"))->find("id", $profileType, ['id'])) {
            $validation['data'] = null;
            $validation['errors'] = ['invalid profile type'];
        }
        return ['data'=>$validation['data'], 'errors'=>($validation['errors'] ? $validation['errors'] : [])];
    }

    /**
     * for validating name column
     *
     * @param string $name the name value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateName(string $name):array
    {
        $validation = parent::nonEmptyVarcharMax255($name, "name");
        return ['data'=>$validation['data'], 'errors'=>($validation['errors'] ? $validation['errors'] : [])];
    }

    /**
     * for validating picture column
     *
     * @param string $picture the picture value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validatePicture(string $picture):array
    {
        $validation = parent::filename($picture, "picture", ColumnMgr::IMG_EXTENSIONS);
        return ['data'=>$validation['data'], 'errors'=>($validation['errors'] ? $validation['errors'] : [])];
    }

    /**
     * for validating emails column
     *
     * @param array $emails the collection of emails value [email1,email2,...]
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateEmails(array $emails):array
    {
        $data = json_encode($emails);
        $errors = [];
        foreach ($emails as $anEmail) {
            if(!filter_var($anEmail, FILTER_VALIDATE_EMAIL)) {
                $data = null;
                $errors[] = "an invalid email in emails collection";
                break;
            }
        }

        return ['data'=>$data, 'errors'=>$errors];
    }

    /**
     * for validating phones column
     *
     * @param array $phones the collection of phones value 	[phone1,phone2,...]
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validatePhones(array $phones):array
    {
        $data = json_encode($phones);
        $errors = [];
        foreach ($phones as $aPhone) {
            if(empty(trim($aPhone))) {
                $data = null;
                $errors[] = "a blank phone no in phones collection";
                break;
            }
        }

        return ['data'=>$data, 'errors'=>$errors];
    }

    /**
     * for validating gender column
     *
     * @param array $gender the gender value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateGender(string $gender):array
    {
        $data = $gender;
        $errors = [];
        if(!in_array($gender, ProfileMgr::GENDER_VALUES)) {
            $data = null;
            $errors[] = "invalid gender value";
        }

        return ['data'=>$data, 'errors'=>$errors];
    }
    
    /**
     * for validating birthday column
     *
     * @param DateTime $birthday the birthday value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateBirthday(DateTime $birthday):array
    {
        $validation = parent::date($birthday, "birthday");
        return ['data'=>$validation['data'], 'errors'=>($validation['error'] == null ? [] : $validation['error'])];
    }

    /**
     * for validating address column
     *
     * @param string $address the address value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateAddress(string $address):array
    {
        $validation = parent::nonEmptyText($address, "address");
        return ['data'=>$validation['data'], 'errors'=>($validation['errors'] ? $validation['errors'] : [])];
    }

    /**
     * for validating the entire row of data to be inserting into the table
     *
     * @param mixed $row the row of data [id=>id, title=>title, description=>description, ... created_at=>created, updated_at=>updated]
     *  id = title = ... = [value, isValue/isFunction], created and updated must be DateTime object
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateRow(array $row): array
    {
        $parentValidation = parent::validateRow($row);
        $data = $parentValidation['data'];
        $errors = $parentValidation['errors'];
        
        if(!isset($row['logger'])) {
            $errors[] = "logger is required";
        } else {
            if($error = ProfileMgr::validateLogger($row['logger'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if(!isset($row['profile_type'])) {
            $errors[] = "profile_type is required";
        } else {
            if($error = ProfileMgr::validateProfileType($row['profile_type'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if(isset($row['name'])) {
            if($error = ProfileMgr::validateName($row['name'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if(isset($row['picture'])) {
            if($error = ProfileMgr::validatePicture($row['picture'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if(isset($row['emails'])) {
            if($validation = ProfileMgr::validateEmails($row['emails'][0])) {
                if($validation['errors']) {
                    $errors[] = implode(", ", $validation['errors']);
                } else {
                    $data['emails'][0] = $validation['data'];
                }
            }
        }
        if(isset($row['phones'])) {
            if($validation = ProfileMgr::validatePhones($row['phones'][0])) {
                if($validation['errors']) {
                    $errors[] = implode(", ", $validation['errors']);
                } else {
                    $data['phones'][0] = $validation['data'];
                }
            }
        }
        if(isset($row['gender'])) {
            if($error = ProfileMgr::validateGender($row['gender'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if(isset($row['birthday'])) {
            $result = ProfileMgr::validateBirthday($row['birthday'][0]);
            if($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['birthday'][0] = $result['data'];
            }
        }
        if(isset($row['address'])) {
            if($error = ProfileMgr::validateAddress($row['address'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        
        return ['data'=>$data, 'errors'=>$errors];
    }

    /**
     * for validating some column(s)'s data for update into the table
     *
     * @param mixed $row the row of data [id=>id, title=>title, description=>description, ... created_at=>created, updated_at=>updated]
     *  id = title = ... = [value, isValue/isFunction], created and updated must be DateTime object
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateCols(array $cols): array
    {
        $parentValidation = parent::validateRow($cols);
        $data = $parentValidation['data'];
        $errors = $parentValidation['errors'];
        if(isset($cols['logger'])) {
            if($error = ProfileMgr::validateLogger($cols['logger'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if(isset($cols['profile_type'])) {
            if($error = ProfileMgr::validateProfileType($cols['profile_type'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if(isset($cols['name'])) {
            if($error = ProfileMgr::validateName($cols['name'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if(isset($cols['picture'])) {
            if($error = ProfileMgr::validatePicture($cols['picture'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if(isset($cols['emails'])) {
            if($validation = ProfileMgr::validateEmails($cols['emails'][0])) {
                if($validation['errors']) {
                    $errors[] = implode(", ", $validation['errors']);
                } else {
                    $data['emails'][0] = $validation['data'];
                }
            }
        }
        if(isset($cols['phones'])) {
            if($validation = ProfileMgr::validatePhones($cols['phones'][0])) {
                if($validation['errors']) {
                    $errors[] = implode(", ", $validation['errors']);
                } else {
                    $data['phones'][0] = $validation['data'];
                }
            }
        }
        if(isset($cols['gender'])) {
            if($error = ProfileMgr::validateGender($cols['gender'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if(isset($cols['birthday'])) {
            $result = ProfileMgr::validateBirthday($cols['birthday'][0]);
            if($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['birthday'][0] = $result['data'];
            }
        }
        if(isset($cols['address'])) {
            if($error = ProfileMgr::validateAddress($cols['address'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        
        return ['data'=>$data, 'errors'=>$errors];
    }
}
