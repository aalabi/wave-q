<?php

/**
 * LoggerMgr
 *
 * A class managing logger table columns
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2023
 * @link        alabiansolutions.com
*/

class LoggerMgrExpection extends Exception
{
}

class LoggerMgr extends ColumnMgr
{
    /** @var dbConnect an instance of DbConnect  */
    protected DbConnect $dbConnect;

    /** @var query an instance of Query  */
    protected Query $query;

    /** @var array collection of permitted value for status */
    public const STATUS_VALUES = ['inactive', 'active', 'inactive'=>'inactive', 'active'=>'active'];

    /** @var string table name*/
    public const TABLE = "logger";

    /**
     * instantiation of LoggerMgr
     *
     */
    public function __construct()
    {
        $DbConnect = DbConnect::getInstance(SETTING_FILE);
        $this->dbConnect = $DbConnect;
        $this->query = new Query();
    }

    /**
     * for creating a table called LoggerMgr using the structure supplied
     *
     * @param array $structure a specification of the table structure
     * @return void
     */
    public static function createTable(array $structure)
    {
        //TODO: implement this later
    }

    /**
     * for validating email column
     *
     * @param string $email the email value
     * @param string $isUnique if true check ensure email is unique
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateEmail(string $email, bool $isUnique = true):array
    {
        $validation = parent::nonEmptyVarcharMax255($email, "email");
        if ($validation['data']) {
            if (!filter_var($validation['data'], FILTER_VALIDATE_EMAIL)) {
                $validation['data'] = null;
                $validation['errors'] = ['invalid email'];
            } else {
                $logger = new Table(LoggerMgr::TABLE);
                if ($isUnique && $logger->find("email", $email, ['id'])) {
                    $validation['data'] = null;
                    $validation['errors'] = ['email associated with another logger'];
                }
            }
        }
        return ['data'=>$validation['data'], 'errors'=>($validation['errors'] ? $validation['errors'] : [])];
    }

    /**
     * for validating username column
     *
     * @param string $username the username value
     * @param string $isUnique if true check ensure username is unique
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateUsername(string $username, bool $isUnique = true):array
    {
        $validation = parent::nonEmptyVarcharMax255($username, "username");
        if ($validation['data']) {
            $logger = new Table(LoggerMgr::TABLE);
            if ($isUnique && $logger->findFirst("username", $username, ['id'])) {
                $validation['data'] = null;
                $validation['errors'] = ['username associated with another logger'];
            }
        }
        return ['data'=>$validation['data'], 'errors'=>($validation['errors'] ? $validation['errors'] : [])];
    }

    /**
     * for validating phone column
     *
     * @param string $phone the phone value
     * @param string $isUnique if true check ensure phone is unique
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validatePhone(string $phone, bool $isUnique = true):array
    {
        $validation = parent::nonEmptyVarcharMax255($phone, "phone");
        if ($validation['data']) {
            $logger = new Table(LoggerMgr::TABLE);
            if ($isUnique && $logger->findFirst("phone", $phone, ['id'])) {
                $validation['data'] = null;
                $validation['errors'] = ['phone associated with another logger'];
            }
        }
        return ['data'=>$validation['data'], 'errors'=>($validation['errors'] ? $validation['errors'] : [])];
    }

    /**
     * for validating status column
     *
     * @param array $ownership the status value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateStatus(string $status):array
    {
        $data = $status;
        $errors = [];
        if (!in_array($status, LoggerMgr::STATUS_VALUES)) {
            $data = null;
            $errors[] = "invalid status value";
        }

        return ['data'=>$data, 'errors'=>$errors];
    }

    /**
     * for validating password column
     *
     * @param string $password the plain text password value (min 8 characters, at least one alphabet and one digit)
     * @return array ["data"=>$data(hashed password), "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validatePassword(string $password):array
    {
        $validation['data'] = $password;
        $validation['errors'] = [];
        if (!preg_match("/^(?=.*[A-Za-z])(?=.*[0-9]).{8,}$/", $password)) {
            $validation['data'] = null;
            $validation['errors'] = ["failed password rule (min 8 characters, at least one alphabet and one digit)"];
        }
        $hashed = $validation['errors'] ? null : password_hash($validation['data'], PASSWORD_DEFAULT);
        return ['data'=>$hashed, 'errors'=>($validation['errors'] ? $validation['errors'] : [])];
    }

    /**
     * for validating reset_token column
     *
     * @param string|null $resetToken the reset_token value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateResetToken(string|null $resetToken = ""):array
    {
        $resetToken = !empty(trim($resetToken)) ? $resetToken : implode("", Functions::asciiCollection(8));
        $validation = parent::nonEmptyVarcharMax255($resetToken, "reset token");
        return ['data'=>$validation['data'], 'errors'=>($validation['errors'] ? $validation['errors'] : [])];
    }

    /**
     * for validating reset_time column
     *
     * @param string $resetTime the reset_time value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateResetTime(?DateTime $resetTime = null):array
    {
        $validation['data'] = $resetTime ? $resetTime : (new DateTime())->add(new DateInterval("PT" . Functions::RESET_TIME_LIMIT . "S"));
        $validation['errors'] = [];
        if (!SqlType::isDateTimeOk($validation['data'])) {
            $validation['data'] = null;
            $validation['errors'] = ['invalid reset time'];
        }

        return ['data'=>($validation['data'] ? $validation['data']->format('Y-m-d H:i:s') : null), 'errors'=>$validation['errors']];
    }

    /**
     * for validating activation_token column
     *
     * @param string|null $activationToken the activation_token value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateActivationToken(string|null $activationToken = ""):array
    {
        $activationToken = !empty(trim($activationToken)) ? $activationToken : implode("", Functions::asciiCollection(8));
        $validation = parent::nonEmptyVarcharMax255($activationToken, "activation token");
        return ['data'=>$validation['data'], 'errors'=>($validation['errors'] ? $validation['errors'] : [])];
    }

    /**
     * for validating activation_time column
     *
     * @param string $activationTime the activation_time value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateActivationTime(?DateTime $activationTime = null):array
    {
        $validation['data'] = $activationTime ? $activationTime : (new DateTime())->add(new DateInterval("PT" . Functions::RESET_TIME_LIMIT . "S"));
        $validation['errors'] = [];
        if (!SqlType::isDateTimeOk($validation['data'])) {
            $validation['data'] = null;
            $validation['errors'] = ['invalid activation time'];
        }

        return ['data'=>($validation['data'] ? $validation['data']->format('Y-m-d H:i:s') : null), 'errors'=>$validation['errors']];
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
        if (isset($row['email'])) {
            if ($error = LoggerMgr::validateEmail($row['email'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if (isset($row['username'])) {
            if ($error = LoggerMgr::validateUsername($row['username'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if (isset($row['phone'])) {
            if ($error = LoggerMgr::validatePhone($row['phone'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if (!isset($row['email']) && !isset($row['username']) && !isset($row['phone'])) {
            $errors[] = "either email, username or phone is required";
        }
        if (isset($row['status'])) {
            if ($error = LoggerMgr::validateStatus($row['status'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        } else {
            $data['status'] = [LoggerMgr::STATUS_VALUES[0], "isValue"];
        }
        if (!isset($row['password'])) {
            $errors[] = "password is required";
        } else {
            $result = LoggerMgr::validatePassword($row['password'][0]);
            if ($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['password'][0] = $result['data'];
            }
        }
        if (!isset($row['reset_token'])) {
            $data['reset_token'] = [LoggerMgr::validateResetToken()['data'], "isValue"];
        } else {
            $result = LoggerMgr::validateResetToken($row['reset_token'][0]);
            if ($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['reset_token'][0] = $result['data'];
            }
        }
        if (!isset($row['reset_time'])) {
            $data['reset_time'] = [LoggerMgr::validateResetTime()['data'], "isValue"];
        } else {
            $result = LoggerMgr::validateResetTime($row['reset_time'][0]);
            if ($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['reset_time'][0] = $result['data'];
            }
        }
        if (!isset($row['activation_token'])) {
            $data['activation_token'] = [LoggerMgr::validateActivationToken()['data'], "isValue"];
        } else {
            $result = LoggerMgr::validateActivationToken($row['activation_token'][0]);
            if ($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['activation_token'][0] = $result['data'];
            }
        }
        if (!isset($row['activation_time'])) {
            $data['activation_time'] = [LoggerMgr::validateActivationTime()['data'], "isValue"];
        } else {
            $result = LoggerMgr::validateActivationTime($row['activation_time'][0]);
            if ($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['activation_time'][0] = $result['data'];
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
        if (isset($cols['email'])) {
            if ($error = LoggerMgr::validateEmail($cols['email'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if (isset($cols['username'])) {
            if ($error = LoggerMgr::validateUsername($cols['username'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if (isset($cols['phone'])) {
            if ($error = LoggerMgr::validatePhone($cols['phone'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if (isset($cols['status'])) {
            if ($error = LoggerMgr::validateStatus($cols['status'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if (isset($cols['password'])) {
            $result = LoggerMgr::validatePassword($cols['password'][0]);
            if ($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['password'][0] = $result['data'];
            }
        }
        if (isset($cols['reset_token'])) {
            $result = LoggerMgr::validateResetToken($cols['reset_token'][0]);
            if ($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['reset_token'][0] = $result['data'];
            }
        }
        if (isset($cols['reset_time'])) {
            $result = LoggerMgr::validateResetTime($cols['reset_time'][0]);
            if ($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['reset_time'][0] = $result['data'];
            }
        }
        if (isset($cols['activation_token'])) {
            $result = LoggerMgr::validateActivationToken($cols['activation_token'][0]);
            if ($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['activation_token'][0] = $result['data'];
            }
        }
        if (isset($cols['activation_time'])) {
            $result = LoggerMgr::validateActivationTime($cols['activation_time'][0]);
            if ($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['activation_time'][0] = $result['data'];
            }
        }
        
        return ['data'=>$data, 'errors'=>$errors];
    }
}
