<?php

/**
 * Session
 *
 * A class managing session
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => March 2026
 * @link        alabiansolutions.com
*/

class SessionExpection extends Exception
{
}

class Session
{
    /** @var DbConnect an instance of DbConnect  */
    protected DbConnect $dbConnect;

    /** @var Query an instance of Query  */
    protected Query $query;

    /** @var int the id for a session  */
    protected int $id;

    /** @var string plain access token  */
    protected string|null $accessToken;

    /** @var string plain refresh token  */
    protected string|null $refreshToken;

    /** @var array an array of this session info  */
    protected array $info;

    /** @var string table name*/
    public const TABLE = SessionMgr::TABLE;

    /**
     * instantiation of Session
     *
     * @param int $id the session id to be used
     *
     * @throws SessionExpection if session id is invalid
     */
    public function __construct(int $id, bool $addProfileInfo = false)
    {
        $DbConnect = DbConnect::getInstance(SETTING_FILE);
        $this->dbConnect = $DbConnect;
        $this->query = new Query();

        $errors = [];
        if (!(new Table(self::TABLE))->retrieveOne($id)) {
            $errors[] = "invalid session id ";
        }
        else{
            $this->id = $id;
            $this->info = $this->computeInfo($addProfileInfo);
        }
                
        if ($errors) {
            throw new SessionExpection("Session instantiation error: ".implode(", ", $errors));
        }
    }

    /**
     * for getting this session id
     *
     * @return integer this session id
     */
    public function getId():int
    {
        return $this->id;
    }

    /**
     * creates a new session using the provided password, identifier value and identifier type
     * 
     * @param string $password the password to use for the session
     * @param string $identifierValue the value of the identifier to use for the session
     * @param string $identifierType the type of the identifier to use for the session
     * @return Session the newly created session
     * @throws SessionExpection if unable to create the session
     */
    public static function create(string $password, string $identifierValue, string $identifierType = Authentication::ALL_IDENTITY_TYPE['email']):Session{
        try{
            $authenticator = new Authentication($identifierValue, $identifierType);
        } catch (AuthenticationExpection $e) {
            throw new SessionExpection($e->getMessage());
        }

        $authenticateInfo = $authenticator->login($password, true);

        if ($authenticateInfo['error']) {
            throw new SessionExpection($authenticateInfo['error']);
        }
        else{
            $sessionId = $authenticateInfo[Functions::getSessionIdName()];
        }
        
        $session = new Session($sessionId, true);
        $session->accessToken = $authenticateInfo['accessToken'];
        $session->refreshToken = $authenticateInfo['refreshToken'];
        return $session;
    }

    /**
     * for getting the plain access token associated with this session
     *
     * @return string|null the plain access token associated with this session, or null if the access token is not set
     */
    public function getAccessToken():string|null
    {
        return $this->accessToken;
    }

    /**
     * for getting the plain refresh token associated with this session
     *
     * @return string|null the plain refresh token associated with this session, or null if the refresh token is not set
     */
    public function getRefreshToken():string|null
    {
        return $this->refreshToken;
    }

    /**
     * Delete this session using access token
     *
     *
     * @param string $accessToken the access token
     * @throws SessionExpection if the supplied arguments are not associated with this session
     * @return void
     */
    public function delete(string $accessToken)
    {
        try{
            self::deleteAnySession($this->id, $accessToken);
        } catch (SessionExpection $e) {
            throw new SessionExpection("supplied argument is not associated with this session");
        }
    }

    /**
     * Delete any session using session id and access token (if supplied)
     *
     * This function will delete any session with the supplied session id. If an access token is supplied, it will be used to validate the session before deletion.
     *
     * @param int $id the session id to be deleted
     * @param string $accessToken the access token (optional)
     * @throws SessionExpection if the supplied arguments are not associated with any session
     * @return void
     */
    public static function deleteAnySession(int $id, string $accessToken = "")
    {
        $table = new Table(self::TABLE);
        if ($accessToken) {
           $noOfDeletes =  $table->delete(['id'=>['=', $id,'isValue', 'AND'], 'access_token'=>['=', (hash('sha256', $accessToken)),'isValue']]);
        } else {
            $noOfDeletes = $table->deleteById($id);
        }

        if (!$noOfDeletes) {
            throw new SessionExpection("supplied argument is not associated with any session");
        }
    }

    /**
     * Check if the access token is valid for this session
     *
     * @param string $accessToken the access token to check
     * @return bool true if the access token is valid or false otherwise
     */
    public function isValidAccessToken(string $accessToken):bool
    {
        $isValid = false;
        $info = isset($this->info['session']) ? $this->info['session'] : $this->info;
        $hashedAccessToken = hash('sha256', $accessToken);
        $storedAccessToken = (new Table(self::TABLE))->retrieveOne($this->id)['access_token'];
        if ($storedAccessToken === $hashedAccessToken && new DateTime() < new DateTime($info['access_token_expiry'])) {
            $isValid = true;
        }
        return $isValid;
    }

    /**
     * Check if the supplied access token is the same as the one stored in the current session
     *
     * @param string $accessToken the access token to check
     * @return bool true if the supplied access token is the same as the one stored in the current session, false otherwise
     */
    public function isItMyAccessToken(string $accessToken):bool
    {
        $itIs = false;
        $hashedAccessToken = hash('sha256', $accessToken);
        $storedAccessToken = (new Table(self::TABLE))->retrieveOne($this->id)['access_token'];
        if ($storedAccessToken === $hashedAccessToken) {
            $itIs = true;
        }
        return $itIs;
    }

    /**
     * Checks if an access token is valid. An access token is valid if it is found in the session table and has not expired.
     *
     * @param string $accessToken the access token to check
     * @return bool true if the access token is valid or false otherwise
     */
    public static function isAnAccessTokenValid(string $accessToken):bool
    {
        $isValid = false;
        $hashedAccessToken = hash('sha256', $accessToken);
        if($info = (new Table(self::TABLE))->retrieve(where:['access_token'=> ['=',$hashedAccessToken,'isValue']])){
            $info = $info[0];
            if (new DateTime() < new DateTime($info['access_token_expiry'])) {
                $isValid = true;
            }
        }
        return $isValid;
    }

    /**
     * Check if the access token is valid for this session
     *
     * @param string $accessToken the access token to check
     * @return bool true if the access token is valid or false otherwise
     */
    public static function profileIdFromAccessToken(string $accessToken):int
    {
        $sessionTbl = self::TABLE;
        $loggerTbl = LoggerMgr::TABLE;
        $profileTbl = ProfileMgr::TABLE;
        $sql = "SELECT {$profileTbl}.id 
            FROM $loggerTbl
                JOIN $profileTbl ON {$loggerTbl}.id = {$profileTbl}.logger
                JOIN $sessionTbl ON {$loggerTbl}.id = {$sessionTbl}.logger
            WHERE {$sessionTbl}.access_token = :accessToken";

        if ($result = (new Query())->executeSql($sql, ['accessToken' => hash('sha256', $accessToken)])['rows']) {
            return $result[0]['id'];
        }
        else{
            throw new SessionExpection("Invalid access token");
        }
    }

    /**
     * Check if the access token and refresh token are valid for this session
     *
     * @param string $accessToken the access token to check
     * @param string $refreshToken the refresh token to check
     * @return bool true if the access token and refresh token are valid or false otherwise
     */
    public function isValidRefreshToken(string $accessToken, string $refreshToken):bool
    {
        $isValid = false;
        $info = isset($this->info['session']) ? $this->info['session'] : $this->info;

        $hashedAccessToken = hash('sha256', $accessToken);
        $hashedRefreshToken = hash('sha256', $refreshToken);
        $storedToken = (new Table(self::TABLE))->retrieveOne($this->id);
        $storedAccessToken = $storedToken['access_token'];
        $storedRefreshToken = $storedToken['refresh_token'];

        $accessTokenOk =  $this->isItMyAccessToken($accessToken); // $storedAccessToken === $hashedAccessToken && new DateTime() < new DateTime($info['access_token_expiry']);
        $refreshTokenOk = $storedRefreshToken === $hashedRefreshToken && new DateTime() < new DateTime($info['refresh_token_expiry']);
        if ($accessTokenOk && $refreshTokenOk) {
            $isValid = true;
        }
        return $isValid;
    }

    /**
     * Get the session information
     *
     * This function will return the session information associated with this session. The session information is computed when the 
     *  session is created and is stored in the info property of this class.
     *
     * @return array the session information
     */
    public function getInfo():array
    {
        return $this->info;
    }

    /**
     * Trim the "Bearer " prefix from an access token.
     * This function takes an access token and returns the token without the "Bearer " prefix.
     * This is useful when an access token is received from a request and the prefix needs to be removed.
     * @param string $accessToken the access token to trim
     * @return string the access token without the "Bearer " prefix
     */
    public static function trimBearer(string $accessToken):string
    {
        return str_replace('Bearer ', '', $accessToken);
    }

    /**
     * Set the value of a key in the session information.
     * If the key is "access_token" or "refresh_token", then the value will be set in the session information.
     * Otherwise, this function does nothing.
     *
     * @param string $key the key to set the value for
     * @param mixed $value the value to set for the given key
     */
    public function setInfo(string $key, $value):void
    {
        if($key == 'access_token' || $key == 'refresh_token'){            
            $this->localSetInfo($key, $value);
        }
    }

    /**
     * A helper function to set the value of a key in the session information.
     * If the session information is stored in a nested array (i.e. 'session' key exists), then the value will be set in the nested array.
     * Otherwise, the value will be set in the top-level array.
     *
     * @param string $key the key to set the value for
     * @param mixed $value the value to set for the given key
     */
    private function localSetInfo(string $key, $value):void
    {
        if(isset($this->info['session'])){
            $this->info['session'][$key] = $value;
        }
        else{
            $this->info[$key] = $value;
        }
    }

    /**
     * Compute session information
     *
     * If $addProfileInfo is true, the method will also retrieve the profile information associated with the session and add it to the 
     *  returned array.
     *
     * @param bool $addProfileInfo if true, the method will also retrieve the profile information associated with the session and add 
     *  it to the returned array.
     * @return array the session information
     */
    protected function computeInfo(bool $addProfileInfo = false):array
    {
        $info = [];
        $sessionInfo = (new Table(self::TABLE))->retrieveOne($this->id);
        $sessionInfo['access_token'] = null;
        $sessionInfo['refresh_token'] = null;

        if($addProfileInfo){
            $info['session'] = $sessionInfo;
            $user = new User(User::profileIdFrmLoginId($sessionInfo['logger']));
            $userInfo = $user->getInfo();
            foreach ($userInfo as $key => $value) {
                $info[$key] = $value;
            }
        }
        else{
            $info = $sessionInfo;
        }
        return $info;
    }

    /**
     * Renews the access and refresh tokens for this session. The tokens are generated with random 24 byte binary strings and
     *  the current time stamp. The tokens are then stored in the session table and the session information is updated.
     *
     * @throws SessionExpection if unable to refresh the tokens
     */
    public function renewTokens():void{
        $sessionMgr = new SessionMgr();
        $accessToken = $sessionMgr->validateAccessToken()['data'];
        $refreshToken = $sessionMgr->validateRefreshToken()['data'];

        $now = new DateTime();
        $accessTokenExpiry = (clone $now)->modify("+" . Functions::ACCESS_TOKEN_LIMIT . " seconds")->format("Y-m-d H:i:s");
        $refreshTokenExpiry = (clone $now)->modify("+" . Functions::REFRESH_TOKEN_LIMIT . " seconds")->format("Y-m-d H:i:s");

        $table = new Table(self::TABLE);
        $columns = ['access_token' => [hash('sha256', $accessToken), 'isValue'], 'access_token_expiry' => [$accessTokenExpiry, 'isValue'],
            'refresh_token' => [hash('sha256',$refreshToken), 'isValue'], 'refresh_token_expiry' => [$refreshTokenExpiry, 'isValue']];
        if(! $table->updateOne($this->id, $columns)){
            throw new SessionExpection("Unable to refresh tokens"); 
        }

        if(isset($this->info['session'])){
            $this->info['session']['access_token_expiry'] = $accessTokenExpiry;
            $this->info['session']['refresh_token_expiry'] = $refreshTokenExpiry;
        }
        else{
            $this->info['access_token_expiry'] = $accessTokenExpiry;
            $this->info['refresh_token_expiry'] = $refreshTokenExpiry;
        }

        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }
}