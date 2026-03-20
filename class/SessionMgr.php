<?php

/**
 * SessionMgr
 *
 * A class managing session table columns
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2023
 * @link        alabiansolutions.com
*/

class SessionMgrExpection extends Exception
{
}

class SessionMgr extends ColumnMgr
{
    /** @var dbConnect an instance of DbConnect  */
    protected DbConnect $dbConnect;

    /** @var query an instance of Query  */
    protected Query $query;

    /** @var string table name*/
    public const TABLE = "session";

    /**
     * instantiation of SessionMgr
     *
     */
    public function __construct()
    {
        $DbConnect = DbConnect::getInstance(SETTING_FILE);
        $this->dbConnect = $DbConnect;
        $this->query = new Query();
    }

    /**
     * for creating a table called SessionMgr using the structure supplied
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
     * for generating access and refresh tokens
     *
     * @return string the generated tokens
     */
    public static function generateToken(int $characters = 32):string
    {
        return bin2hex(random_bytes($characters));
    }

    /**
     * for validating access_token column
     *
     * @param string|null $accessToken the access_token value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateAccessToken(string|null $accessToken = ""):array
    {
        $accessToken = !empty(trim($accessToken)) ? $accessToken : $this->generateToken();
        $validation = parent::nonEmptyVarcharMax255($accessToken, "access token");
        return ['data'=>$validation['data'], 'errors'=>($validation['errors'] ? $validation['errors'] : [])];
    }

    /**
     * for validating access_token_expiry column
     *
     * @param string $accessTokenExpiry the access_token_expiry value
     * @return array ["data"=>$data(hashed password), "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateAccessTokenExpiry(DateTime $accessTokenExpiry = null):array
    {
        $validation['data'] = $accessTokenExpiry ? $accessTokenExpiry :
            (new DateTime())->add(new DateInterval("PT" . Functions::ACCESS_TOKEN_LIMIT . "S"));
        $validation['errors'] = [];
        if(!SqlType::isDateTimeOk($validation['data'])) {
            $validation['data'] = null;
            $validation['errors'] = ['invalid access token expiration time'];
        }

        return ['data'=>($validation['data'] ? $validation['data']->format('Y-m-d H:i:s') : null), 'errors'=>$validation['errors']];
    }

    /**
     * for validating refresh_token column
     *
     * @param string|null $refreshToken the refresh_token value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateRefreshToken(string|null $refreshToken = ""):array
    {
        $refreshToken = !empty(trim($refreshToken)) ? $refreshToken : $this->generateToken(64);
        $validation = parent::nonEmptyVarcharMax255($refreshToken, "refresh token");
        return ['data'=>$validation['data'], 'errors'=>($validation['errors'] ? $validation['errors'] : [])];
    }

    /**
     * for validating refresh_token_expiry column
     *
     * @param string $refreshTokenExpiry the refresh_token_expiry value
     * @return array ["data"=>$data(hashed password), "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateRefreshTokenExpiry(DateTime $refreshTokenExpiry = null):array
    {
        $validation['data'] = $refreshTokenExpiry ? $refreshTokenExpiry :
            (new DateTime())->add(new DateInterval("PT" . Functions::REFRESH_TOKEN_LIMIT . "S"));
        $validation['errors'] = [];
        if(!SqlType::isDateTimeOk($validation['data'])) {
            $validation['data'] = null;
            $validation['errors'] = ['invalid refresh token expiration time'];
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
        if(!isset($row['logger'])) {
            $errors[] = "logger is required";
        } else {
            if($error = SessionMgr::validateLogger($row['logger'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if(!isset($row['access_token'])) {
            $data['access_token'] = [SessionMgr::validateAccessToken()['data'], "isValue"];
        } else {
            $result = SessionMgr::validateAccessToken($row['access_token'][0]);
            if($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['access_token'][0] = $result['data'];
            }
        }
        if(!isset($row['access_token_expiry'])) {
            $data['access_token_expiry'] = [SessionMgr::validateAccessTokenExpiry()['data'], "isValue"];
        } else {
            $result = SessionMgr::validateAccessTokenExpiry($row['access_token_expiry'][0]);
            if($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['access_token_expiry'][0] = $result['data'];
            }
        }
        if(!isset($row['refresh_token'])) {
            $data['refresh_token'] = [SessionMgr::validateRefreshToken()['data'], "isValue"];
        } else {
            $result = SessionMgr::validateRefreshToken($row['refresh_token'][0]);
            if($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['refresh_token'][0] = $result['data'];
            }
        }
        if(!isset($row['refresh_token_expiry'])) {
            $data['refresh_token_expiry'] = [SessionMgr::validateRefreshTokenExpiry()['data'], "isValue"];
        } else {
            $result = SessionMgr::validateRefreshTokenExpiry($row['refresh_token_expiry'][0]);
            if($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['refresh_token_expiry'][0] = $result['data'];
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
            if($error = SessionMgr::validateLogger($cols['logger'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if(isset($cols['access_token'])) {
            $result = SessionMgr::validateAccessToken($cols['access_token'][0]);
            if($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['access_token'][0] = $result['data'];
            }
        }
        if(isset($cols['access_token_expiry'])) {
            $result = SessionMgr::validateAccessTokenExpiry($cols['access_token_expiry'][0]);
            if($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['access_token_expiry'][0] = $result['data'];
            }
        }
        if(isset($cols['refresh_token'])) {
            $result = SessionMgr::validateRefreshToken($cols['refresh_token'][0]);
            if($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['refresh_token'][0] = $result['data'];
            }
        }
        if(isset($cols['refresh_token_expiry'])) {
            $result = SessionMgr::validateRefreshTokenExpiry($cols['refresh_token_expiry'][0]);
            if($result['errors']) {
                $errors[] = implode(", ", $result['errors']);
            } else {
                $data['refresh_token_expiry'][0] = $result['data'];
            }
        }
        
        return ['data'=>$data, 'errors'=>$errors];
    }
}
