<?php

/**
 * Authentication
 *
 * A class managing users' authentication
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2022, 1.1 => November 2023
 * @link        alabiansolutions.com
*/

class AuthenticationExpection extends Exception
{
}

class Authentication
{
    /** @var DbConnect an instance of DbConnect  */
    protected DbConnect $dbConnect;

    /** @var Query an instance of Query  */
    protected Query $query;

    /** @var Table an instance of Table  */
    protected Table $tblLogger;

    /** @var Table an instance of Table  */
    protected Table $tblProfile;

    /** @var int logger table id*/
    protected int $id;

    /** @var string|null logger table email*/
    protected string|null $email;

    /** @var string|null logger table phone*/
    protected string|null $phone;

    /** @var string|null logger table username*/
    protected string|null $username;

    /** @var string field's value used in identifying a logger (logger.email, logger.phone or logger.username) */
    protected string $identity;

    /** @var string field's name used in identifying a logger (logger.email, logger.phone or logger.username) */
    public string $identityType;

    /** @var array all the value for the field's name used in identifying a logger (logger.email, logger.phone or logger.username) */
    public const ALL_IDENTITY_TYPE = ['email', 'phone','username', 'id', 'email'=>'email', 'phone'=>'phone', 'username'=>'username', 'id'=>'id'];

    /**
     * instantiation of Authentication
     *
     * @param string|int identity the value of either id, email, phone or username
     * @param string identityType the type of identity either id, email, phone or username
     */
    public function __construct(string|int $identity, string $identityType = 'email')
    {
        try {
            $DbConnect = DbConnect::getInstance(SETTING_FILE);
        }
        catch (DbConnectExpection $e) {
            throw new AuthenticationExpection("Authentication Error: " . $e->getMessage());
        }
        $this->dbConnect = $DbConnect;
        $this->query = new Query();
        
        $errors = [];
        $this->tblLogger = new Table(LoggerMgr::TABLE);
        $this->tblProfile = new Table(ProfileMgr::TABLE);
        if (!in_array($identityType, Authentication::ALL_IDENTITY_TYPE)) {
            $errors[] = "invalid identityType parameter";
        }
       
        if ($identityType == 'email') {
            if ($info = $this->tblLogger->select([], ['email'=>['=', $identity, 'isValue']])) {
                $id = $info[0]['id'];
                $email = $info[0]['email'];
                $phone = $info[0]['phone'];
                $username = $info[0]['username'];
            } else {
                $errors[] = "unknown '".'email'."' supplied for identity parameter";
            }
        }
        if ($identityType == 'phone') {
            if ($info = $this->tblLogger->select([], ['phone'=>['=', $identity, 'isValue']])) {
                $id = $info[0]['id'];
                $email = $info[0]['email'];
                $phone = $info[0]['phone'];
                $username = $info[0]['username'];
            } else {
                $errors[] = "unknown '".'phone'."' supplied for identity parameter";
            }
        }
        if ($identityType == 'username') {
            if ($info = $this->tblLogger->select([], ['username'=>['=', $identity, 'isValue']])) {
                $id = $info[0]['id'];
                $email = $info[0]['email'];
                $phone = $info[0]['phone'];
                $username = $info[0]['username'];
            } else {
                $errors[] = "unknown '".'username'."' supplied for identity parameter";
            }
        }
        if ($identityType == 'id') {
            if ($info = $this->tblLogger->select([], ['id'=>['=', $identity, 'isValue']])) {
                $id = $info[0]['id'];
                $email = $info[0]['email'];
                $phone = $info[0]['phone'];
                $username = $info[0]['username'];
            } else {
                $errors[] = "unknown '".'id'."' supplied for identity parameter";
            }
        }

        if ($errors) {
            throw new AuthenticationExpection("Authentication instantiation error: ".implode(", ", $errors));
        }

        $this->setIdentityType($identityType);
        $this->setIdentity($identity);
        $this->id = $id;
        $this->email = $email;
        $this->phone = $phone;
        $this->username = $username;
    }

    /**
     * get logger identitier which is either email, phone or username
     *
     * @return string either email, phone or username
     */
    public function getIdentity():string
    {
        return $this->identity;
    }

    /**
     * set logger identitier which is either email, phone or username
     *
     * @param string $identity either email, phone or username
     * @return void
     */
    public function setIdentity(string $identity)
    {
        $where = [$this->identityType=>['=', $identity, 'isValue']];
        if (!$this->tblLogger->select([], $where)) {
            throw new AuthenticationExpection("invalid user's {$this->identityType}");
        }
        $this->identity = $identity;
    }

    /**
     * get logger identity type which is either email, phone or username
     *
     * @return string either email, phone or username
     */
    public function getIdentityType():string
    {
        return $this->identityType;
    }

    /**
     * set logger identity type which is either email, phone or username
     *
     * @param string $identityType either email, phone or username
     * @return void
     */
    public function setIdentityType(string $identityType)
    {
        if (!in_array($identityType, Authentication::ALL_IDENTITY_TYPE)) {
            throw new AuthenticationExpection("invalid identity type");
        }
        $this->identityType = $identityType;
    }

    /**
     * for login a logger
     *
     * @param string $password the user's
     * @return array an array of authentication info ['sessionId'=>$s, accessToken'=>$a, accessTokenExpiry'=>$ae, 'refreshToken'=>$r, 'refreshTokenExpiry'=>$re 'userInfo'=>$u, 'error'=>$e];
     */
    public function login(string $password, bool $addUserInfo = true):array
    {
        $userInfo = $errorMsg = [];
        $sessionId = $sessionInfo['access_token'] = $sessionInfo['refresh_token'] = $sessionInfo['access_token_expiry'] =
            $sessionInfo['refresh_token_expiry'] = $accessToken = $refreshToken = "";
        $loggerInfo = $this->tblLogger->retrieveOne($this->id);
        $sessionName = Functions::getSessionIdName();
        $loggerIdName = Functions::getLoggerIdSessionName();
        $fingerprintName = Functions::getFingerprintSessionName();

        if ($loggerInfo['status'] == LoggerMgr::STATUS_VALUES['inactive']) {
            $errorMsg[] = "Inactive user";
        } else {
            if (!password_verify($password, $loggerInfo['password'])) {
                $errorMsg[] = "Login failed";
            } else {
                $sessionTbl = new Table(SessionMgr::TABLE);
                $sessionMgr = new SessionMgr();
                $accessToken = $sessionMgr->validateAccessToken()['data'];
                $refreshToken = $sessionMgr->validateRefreshToken()['data'];
                $columns = [
                    'logger'=>[$this->id, 'isValue'],
                    'access_token'=>[hash('sha256', $accessToken), 'isValue'],
                    'access_token_expiry'=>[$sessionMgr->validateAccessTokenExpiry()['data'], 'isValue'],
                    'refresh_token'=>[hash('sha256', $refreshToken), 'isValue'],
                    'refresh_token_expiry'=>[$sessionMgr->validateRefreshTokenExpiry()['data'], 'isValue'],
                ];
                $sessionId = $sessionTbl->create($columns);
                $sessionInfo = $sessionTbl->retrieveOne($sessionId);
                $userInfo = $addUserInfo ? (new User(User::profileIdFrmLoginId($this->id)))->getInfo() : [];
                $_SESSION[$loggerIdName] = $this->id;
                $_SESSION[$sessionName] = $sessionId;
                $token = (new Settings(SETTING_FILE))->getDetails()->token;
                $_SESSION[$fingerprintName] =  hash('sha512', "{$this->id}{$sessionId}{$loggerInfo['password']}{$token}");
            }
        }

        return [
            $sessionName=>$sessionId, 'accessToken'=>$accessToken, 'refreshToken'=>$refreshToken, 'accessTokenExpiry'=>$sessionInfo['access_token_expiry'], 
            'refreshTokenExpiry'=>$sessionInfo['refresh_token_expiry'], 'userInfo'=>$userInfo, 'error'=>implode(", ", $errorMsg)];
    }

    /**
     * check if session id is valid for current user
     *
     * @param string $sessionId the session id
     * @param string $fingerprint the current user session's fingerprint
     * @return boolean true if valid
     */
    public function isFingerprintValid(string $sessionId, string $fingerPrint):bool
    {
        $valid = false;
        $thereIsError = false;
        $loggerIdName = Functions::getLoggerIdSessionName();
        $fingerprintName = Functions::getFingerprintSessionName();

        if (!isset($_SESSION[$loggerIdName]) || !isset($_SESSION[Functions::getSessionIdName()]) ||
            !isset($_SESSION[$fingerprintName])) {
            $thereIsError = true;
        }
        $sessionInfo = (new Table(SessionMgr::TABLE))->retrieveOne($sessionId);
        if (!isset($sessionInfo['logger'])) {
            $thereIsError = true;
        } else {
            $loggerInfo = $this->tblLogger->retrieveOne($sessionInfo['logger']);
            if (isset($_SESSION[$loggerIdName]) &&
                ($_SESSION[$loggerIdName] != $this->id || $_SESSION[$loggerIdName] != $sessionInfo['logger'])) {
                $thereIsError = true;
            }
        }

        $token = (new Settings(SETTING_FILE))->getDetails()->token;
        if (!$thereIsError) {
            if ($fingerPrint == hash('sha512', "{$this->id}{$sessionId}{$loggerInfo['password']}{$token}")) {
                $valid = true;
            }
        }
        
        return $valid;
    }

    /**
     * logout a logger if no session id is specified the user is logout out from all active session
     *
     * @param string|null $sessionId the session ID, if not specified logger is logged out from all sessions
     * @param string $reDirect the url to redirect after the user is logged out
     * @return void
     */
    public function logout(string|null $sessionId = null, string $reDirect = "")
    {
        $sessionTbl = new Table(SessionMgr::TABLE);
        if ($sessionId) {
            $sessionTbl->delete(['id'=>['=', $sessionId,'isValue']]);
        } else {
            $sessionTbl->delete(['logger'=>['=', $this->id,'isValue']]);
        }

        $_SESSION = [];
        session_destroy();

        $reDirect = !$reDirect ? (new Settings(SETTING_FILE))->getDetails()->machine->url : $reDirect;
        header('Location: '.$reDirect);
    }

    /**
      * generate the hash version of a password
      *
      * @param string $password the plain text password
      * @return string the hash password
      */
    public static function generatePasswordHash(string $password):string
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        return $hash;
    }

    /**
     * for refreshing the access token and generating a new refresh token
     *
     * @param integer $sessionId the session ID
     * @param string $accessToken the access token
     * @param string $refreshToken the refresh token
     * @return array an array [accessToken'=>$a, accessTokenExpiry'=>$ae, 'refreshToken'=>$r, 'refreshTokenExpiry'=>$re, 'error'=>$e];
     */
    public function refreshAccessToken(int $sessionId, string $accessToken, string $refreshToken):array
    {
        $columns['access_token'][0] = $columns['access_token_expiry'][0] = $columns['refresh_token'][0] =
            $columns['refresh_token_expiry'][0] = "";
        $errorMsg = "access token cannot be refreshed";
        
        if ($this->canAccessTokenBeRefresh($sessionId, $accessToken)) {
            $sessionTbl = new Table(SessionMgr::TABLE);
            $sessionMgr = new SessionMgr();
            $columns = [
                'access_token'=>[$sessionMgr->validateAccessToken()['data'], 'isValue'],
                'access_token_expiry'=>[$sessionMgr->validateAccessTokenExpiry()['data'], 'isValue'],
                'refresh_token'=>[$sessionMgr->validateRefreshToken()['data'], 'isValue'],
                'refresh_token_expiry'=>[$sessionMgr->validateRefreshTokenExpiry()['data'], 'isValue']
            ];
            $sessionTbl->update($columns, ['id'=>['=',$sessionId,'isValue']]);
        }

        return [
            'accessToken'=>$columns['access_token'][0], 'acccessTokenExpiry'=>$columns['access_token_expiry'][0],
            'refreshToken'=>$columns['refresh_token'][0], 'refreshTokenExpiry'=>$columns['refresh_token_expiry'][0],
            'error'=>$errorMsg];
    }

    /**
     * check if the access token has not expired
     *
     * @param integer $sessionId the session ID
     * @param string $accessToken the access token
     * @return boolean true if the access token has not expired or false otherwise
     */
    public function isAccessTokenValid(int $sessionId, string $accessToken):bool
    {
        $isValid = false;
        if ($sessionInfo = (new Table(SessionMgr::TABLE))->retrieveOne($sessionId)) {
            if ($accessToken === $sessionInfo['access_token'] && new DateTime() < new DateTime($sessionInfo['access_token_expiry'])) {
                $isValid = true;
            }
        }
        return $isValid;
    }

    /**
     * check if the access token's refresh token has not expired ie if the access token is still refreshable
     *
     * @param integer $sessionId the session ID
     * @param string $accessToken the access token
     * @return boolean true if the access token can still be refreshed or false otherwise
     */
    public function canAccessTokenBeRefresh(int $sessionId, string $accessToken):bool
    {
        $isValid = false;
        if ($sessionInfo = (new Table(SessionMgr::TABLE))->retrieveOne($sessionId)) {
            if ($accessToken === $sessionInfo['access_token'] && new DateTime() < new DateTime($sessionInfo['refresh_token_expiry'])) {
                $isValid = true;
            }
        }
        return $isValid;
    }

    /**
     * change reset token and reset token time for a logger
     *
     * @return void
     */
    public function setResetToken()
    {
        $loggerMgr = new LoggerMgr();
        $columns = [
            'reset_token'=>[$loggerMgr->validateResetToken()['data'], 'isValue'],
            'reset_time'=>[$loggerMgr->validateResetTime()['data'], 'isValue'],
        ];
        $this->tblLogger->update($columns, ['id' =>['=', $this->id, 'isValue']]);
    }

    /**
     * check if the reset token for a logger is valid
     *
     * @param bool $checkTime whether to use expiration time when checking for validity of reset token
     * @return boolean
     */
    public function isResetTokenValid(string $resetToken, bool $checkTime = false):bool
    {
        $isValid = false;
        $userTableInfo = $this->tblLogger->retrieveOne($this->id);
        if ($resetToken === $userTableInfo['reset_token']) {
            $isValid = true;
        }
        if ($checkTime) {
            if (new DateTime() > new DateTime($userTableInfo['reset_time'])) {
                $isValid = false;
            }
        }
        return $isValid;
    }

    /**
     * for changing a logger password
     *
     * @param string $password the new password in plain text
     * @return void
     */
    public function changePassword(string $password)
    {
        $validation = (new LoggerMgr())->validatePassword($password);
        if ($validation['errors']) {
            throw new AuthenticationExpection(implode(", ", $validation['errors']));
        } else {
            $password = $validation['data'];
        }
        $this->tblLogger->update(['password'=>[$password, 'isValue']], ['id'=>['=',$this->id, 'isValue']]);
        if ($this->email) {
            //TODO: implement email notification about password change later
        }
    }

    /**
     * for changing a logger status
     *
     * @param string $status the status which is either 'active' or 'inactive'
     * @return void
     */
    public function changeStatus(string $status)
    {
        $validation = (new LoggerMgr())->validateStatus($status);
        if ($validation['errors']) {
            throw new AuthenticationExpection(implode(", ", $validation['errors']));
        } else {
            $status = $validation['data'];
        }
        $this->tblLogger->update(['status'=>[$status, 'isValue']], ['id'=>['=',$this->id, 'isValue']]);
        if ($this->email) {
            //TODO: implement email notification about status change later
        }
    }

    /**
     * ensure only users with a valid session id and fingerprint can access the web page
     *
     * @param string $sessionId the session id
     * @param string $fingerprint the current user session's fingerprint
     * @param string $reDirectUrl the web page the session id user is redirected to if fingerprint is invalid
     * @return boolean true if valid
     */
    public function webPageLock(int $sessionId, string $fingerPrint, string $reDirectUrl)
    {
        if (!$this->isFingerprintValid($sessionId, $fingerPrint)) {
            header("Location: $reDirectUrl");
            exit;
        }
    }

    /**
     * restrict access by checking if sub profile is among the array of sub profiles
     *
     * @param array $urProfileAndSubType the user sub profile [profileType=>subPT]
     * @param array $allowedProfileTypes array of allowed sub profiles [profileType1=>[subPT11,subPT12..], profileType2=>[subPT21,subPT22..]]
     * @return boolean
     */
    public function gateAccess(array $urProfileAndSubType, array $allowedProfileTypes):bool
    {
        $canAcess = false;
        $profileType = array_keys($urProfileAndSubType)[0];
        $subProfileType = $urProfileAndSubType[$profileType];
        if (isset($allowedProfileTypes[$profileType])) {
            if (in_array($subProfileType, $allowedProfileTypes[$profileType])) {
                $canAcess = true;
            }
        }

        return $canAcess;
    }

    /**
     * for login an endpoint data send back
     *  ['sessionId'=>$s, accessToken'=>$a, accessTokenExpiry'=>$ae, 'refreshToken'=>$r, 'refreshTokenExpiry'=>$re 'userInfo'=>[$u]];
     *
     * @param string $username the logger username
     * @param string $password the logger password
     */
    public static function loginEndPoint(string $username, string $password)
    {
        //TODO: will be improved later
        $sessionId = 0;
        $accessToken = "";
        $accessTokenExpiry = "";
        $refreshToken = "";
        $refreshTokenExpiry = "";
        $userInfo = [];
        $Response = new Response();
        if (!($userInfo = (new User(User::loginIdFrmUsername($username)))->getInfo(false))) {
            Response::sendBadResponse(Response::UNAUTHORIZED, ["Invalid access credentials"]);
            exit;
        }
        if ($userInfo[LoggerMgr::TABLE]['status'] != LoggerMgr::STATUS_VALUES["inactive"]) {
            $Response->sendBadResponse(Response::UNAUTHORIZED, ["Inactive app"]);
            exit;
        } else {
            if (!password_verify($password, $userInfo[LoggerMgr::TABLE]['password'])) {
                Response::sendBadResponse(Response::UNAUTHORIZED, ["access denied"]);
                exit;
            } else {
                $Session = new Table(SessionMgr::TABLE);
                $sessionMgr = new SessionMgr();
                $columns = [
                    'id'=>[$userInfo[LoggerMgr::TABLE]['id'], 'isValue'],
                    'access_token'=>[$sessionMgr->validateAccessToken()['data'], 'isValue'],
                    'access_token_expiry'=>[$sessionMgr->validateAccessTokenExpiry()['data'], 'isValue'],
                    'refresh_token'=>[$sessionMgr->validateRefreshToken()['data'], 'isValue'],
                    'refresh_token_expiry'=>[$sessionMgr->validateRefreshTokenExpiry()['data'], 'isValue'],
                ];
                $sessionId = $Session->insert($columns);
                $sessionInfo = $Session->retrieveOne($sessionId);
                $accessToken = $sessionInfo['access_token'];
                $accessTokenExpiry = $sessionInfo['access_token_expiry'];
                $refreshToken = $sessionInfo['refresh_token'];
                $refreshTokenExpiry = $sessionInfo['refresh_token_expiry'];
                $sessionName = Functions::getSessionIdName();
                $messages = [];
                $data = [
                    $sessionName=>$sessionId, 'accessToken'=>$accessToken, 'refreshToken'=>$refreshToken,
                    'accessTokenExpiry'=>$accessTokenExpiry, 'refreshTokenExpiry'=>$refreshTokenExpiry, 'userInfo'=>$userInfo];
                Response::sendGoodResponse($messages, $data);
            }
        }
    }

    /**
     * for access control to endpoint via access token
     *
     * @param string $accessToken a session access token
     * @return int the profile id of the user
     */
    public static function endPointLockAccessToken($accessToken = ""): int
    {
        //TODO: will be improved later
        $Response = new Response();
        $Query = new Query();
        $accessToken = $accessToken ? $accessToken : Authentication::getAccessToken();
        
        $sql = "SELECT logger.id, session.logger, session.access_token_expiry,  logger.status
            FROM session, logger
            WHERE sesion.logger = logger.id AND session.access_token  = :accessToken";
        $result = $Query->executeSql($sql, ['accessToken' => $accessToken]);

        if (!$result['rows']) {
            $Response->sendBadResponse(Response::UNAUTHORIZED, ["Invalid access token"]);
            exit;
        }
        $result = $result['rows'][0];

        if ($result['status'] === LoggerMgr::STATUS_VALUES['inactive']) {
            $Response->sendBadResponse(Response::UNAUTHORIZED, ["Inactive account"]);
            exit;
        }

        if (strtotime($result['access_token_expiry']) < time()) {
            $Response->sendBadResponse(Response::UNAUTHORIZED, ["Expired access token"]);
            exit;
        }

        $Query->setTable(ProfileMgr::TABLE);

        $where = ['logger'=> ['=', $result['id'], 'isValue']];
        $profileId = (new Table(ProfileMgr::TABLE))->select(['id'], $where)[0]['id'];
        return $profileId;
    }

    /**
     * for getting the  app access token
     *
     * @return string the access token
     */
    private static function getAccessToken():string
    {
        if (!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) < 1) {
            $errors = [];
            (!isset($_SERVER['HTTP_AUTHORIZATION']) ? $errors[] = "Missing access token error" : false);
            (empty($_SERVER['HTTP_AUTHORIZATION']) ? $errors[] = "Blank access token error" : false);
            Response::sendBadResponse(Response::UNAUTHORIZED, $errors);
            exit;
        }
        return $_SERVER['HTTP_AUTHORIZATION'];
    }

    /**
     * for getting the app passkey sent via Bearer Token Authorization
     *
     * @return string the passkey
     */
    private static function getPassKey():string
    {
        if (!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) < 1) {
            $errors = [];
            (!isset($_SERVER['HTTP_AUTHORIZATION']) ? $errors[] = "Missing passkey" : false);
            (empty($_SERVER['HTTP_AUTHORIZATION']) ? $errors[] = "Blank passkey" : false);
            Response::sendBadResponse(Response::UNAUTHORIZED, $errors);
            exit;
        }
        $passKey = substr($_SERVER['HTTP_AUTHORIZATION'], 7);
        return $passKey;
    }
}
