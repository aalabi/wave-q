<?php

/**
 * Player
 *
 * A class managing player
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => April 2024
 * @link        alabiansolutions.com
*/

class Player extends \User
{    
    /** @var int player profile type id*/
    public const PROFILE_TYPE_ID = 2;
    
    /** @var string player table*/
    public const TABLE = "player";

    /** @var array collection of mode values */
    protected const DISPLAY_MODES = ['light', 'dark', 'light'=>'light', 'dark'=>'dark'];

    /** @var array collection of two factors values */
    protected const TWO_FACTORS = ['yes', 'no', 'yes'=>'yes', 'no'=>'no'];

    /** @var \Table an instance of \Table  */
    protected \Table $tblPlayer;


    /**
     * instantiation of Player
     *
     * @param int $id the profile table id of a user of player profile type
     */
    public function __construct(int $id)
    {
        parent::__construct($id);

        $this->tblPlayer = new \Table(Player::TABLE);
        $profileTbl = \ProfileMgr::TABLE;
        $playerTbl = Player::TABLE;
        $sql = "SELECT {$profileTbl}.id  
            FROM $playerTbl INNER JOIN $profileTbl ON {$playerTbl}.profile = {$profileTbl}.id
            WHERE {$playerTbl}.profile = :id";
        if (!$this->query->executeSql($sql, ["id"=>$id])['rows']) {
            throw new \UserException("User instantiation error: profile not a player");
        }
    }

    /**
     * for creating a new player
     *
     * @param string name name of the player
     * @param array subProfileType the sub profile type
     * @param string $password unhashed password (min 8 characters, at least one alphabet and one digit)
     * @param string $identity unique identifier value either the email, phone or username
     * @param string $identityType unique identifier type either email, phone or username
     * @return array info of the newly created player ['logger'=>$l, 'profile'=>$p, 'player'=>$ps, 'type'=>$t]
     */
    public static function create(
        string $name,
        array $subProfileType,
        string $password = "",
        string $identity = "",
        string $identityType = \Authentication::ALL_IDENTITY_TYPE['email'],
        string $phone = "",
        string $username = ""
    ):array {
        $userInfo = $loggerInfo = [];
        $DbConnect = DbConnect::getInstance(SETTING_FILE);
        
        try {
            $Query = new Query(LoggerMgr::TABLE);
            $DbConnect->beginTransaction();

            $columnName = "email, phone, username, password, activation_token, activation_time ";
            $columnValue = ":email, :phone, :username, :password, :activationToken, :activationtime ";
            $resultSql = "INSERT INTO " . LoggerMgr::TABLE . "($columnName) VALUES ($columnValue)";
                
            $loggerMgr = new LoggerMgr();
            $result = $loggerMgr->validatePassword($password);
            if ($result['errors']) {
                throw new UserException(implode(', ', $result['errors']));
            }
            $password = $result['data'];

            $otp = random_int(100_000, 999_999);
            $result = $loggerMgr->validateActivationToken($otp);
            if ($result['errors']) {
                throw new UserException(implode(', ', $result['errors']));
            }
            $activationToken = $result['data'];
                
            $result = $loggerMgr->validateActivationTime();
            if ($result['errors']) {
                throw new UserException(implode(', ', $result['errors']));
            }
            $activationTokenTime = $result['data'];

            $result = $loggerMgr->validateEmail($identity);
            if ($result['errors']) {
                throw new UserException(implode(', ', $result['errors']));
            }
            $email = $result['data'];
                                
            $result = $loggerMgr->validatePhone($phone);
            if ($result['errors']) {
                throw new UserException(implode(', ', $result['errors']));
            }
            $phone = $result['data'];
                
            $result = $loggerMgr->validateUsername($username);
            if ($result['errors']) {
                throw new UserException(implode(', ', $result['errors']));
            }
            $username = $result['data'];
            
            $resultBind = ['email'=>$email, 'phone'=>$phone, 'username'=>$username, 'password'=>$password, 
                'activationToken'=>$activationToken, 'activationtime'=>$activationTokenTime];
            $loggerId = $Query->executeSql($resultSql, $resultBind)['lastInsertId'];
            $tblLogger = new Table(LoggerMgr::TABLE);
            $loggerInfo = $tblLogger->retrieveOne($loggerId);    

            $columnName = "profile_type , name, logger";
            $columnValue = ":profile_type, :name, :logger";
            $resultBind = ['profile_type' => self::PROFILE_TYPE_ID, 'name' => $name, 'logger' => $loggerId];
            $resultSql = "INSERT INTO " . ProfileMgr::TABLE . "($columnName) VALUES ($columnValue)";
            $profileId = $Query->executeSql($resultSql, $resultBind)['lastInsertId'];

            $profileNo = "PLA".str_pad($profileId, 4, "0", STR_PAD_LEFT);
            (new \Table(\ProfileMgr::TABLE))->updateOne($profileId, ['profile_no' => [$profileNo, 'isValue']]);

            $Query->setTable(self::TABLE);
            $sql = "INSERT INTO ".self::TABLE." (profile, type) VALUES (:profile, :type)";
            $bind = ['profile'=>$profileId, 'type'=>'player' ];
            $profileTypeTblId = $Query->executeSql($sql, $bind)['lastInsertId'];
            
            $DbConnect->commit();
        } catch (Exception $e) {
            $DbConnect->rollBack();
            throw new UserException($e->getMessage());
        }

        $playerTbl = new Table(self::TABLE);
        $tblProfile = new Table(ProfileMgr::TABLE);
        $userInfo = [
            'logger'=>$loggerInfo,
            'profile'=>$tblProfile->retrieveOne($profileId),
            'player'=>$playerTbl->retrieveOne($profileTypeTblId),
            'type'=>'player'
        ];

        parent::sendLoggerCreationMail($userInfo);
        return $userInfo;
    }

    /**
     * Activates a player with the given token
     * @param string $token the activation token
     * @throws UserException if the activation fails
     */
    public function activate(string $token):void{
        $tblLogger = LoggerMgr::TABLE;
        $loggerId = parent::loggerIdUsernamePhoneFrmProfileId($this->id)['logger'];
        $sql = "UPDATE $tblLogger SET status = :status WHERE id = :id AND activation_token = :token";
        $param = ['id' => $loggerId, 'token' => $token, 'status' => parent::STATUS['active']];
        
        if($this->query->executeSql($sql, $param)['rowCount']){
            //TODO: send notification
        }
        else{
            throw new UserException("player activation failed");
        }
    }

    public function resendActivationToken():void{
        $otp = random_int(100_000, 999_999);
        $tokenTime = (new LoggerMgr())->validateActivationTime()['data'];
        
        $loggerId = parent::loggerIdUsernamePhoneFrmProfileId($this->id)['logger'];
        $sql = "UPDATE ".LoggerMgr::TABLE." SET activation_token = :token, activation_time = :tokenTime WHERE id = :id AND status = :status";
        $param = ['id' => $loggerId, 'token' => $otp, 'tokenTime' => $tokenTime, 'status' => parent::STATUS['inactive']];

        if($this->query->executeSql($sql, $param)['rowCount']){
            $settings = (new \Settings(SETTING_FILE, true))->getDetails();
            $userInfo = $this->getInfo();
            $body = "
                <div style='font-family:Arial, Helvetica, sans-serif; background:#f4f6fb; padding:30px;'>
                    <div style='max-width:600px; margin:auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,0.08);'>                    
                        <div style='padding:35px;'>
                            <p style='font-size:15px;'>Hello <p>Hello {$userInfo['profile']['name']},</p>,</p>
                            <p style='font-size:15px; line-height:1.6; color:#444;'>
                                You requested for another OTP. Your account is still currently <strong>inactive</strong>. Use the One-Time Password (OTP) below to 
                                activate your account.
                            </p>
                            <div style='text-align:center; margin:35px 0;'>
                                <div style='display:inline-block;
                                    font-size:32px;
                                    letter-spacing:8px;
                                    padding:18px 28px;
                                    background:#ff3b30;
                                    color:#ffffff;
                                    border-radius:10px;
                                    font-weight:bold;'>
                                    $otp
                                </div>
                                <p style='margin-top:12px; font-size:13px; color:#666;'>
                                    Activation OTP
                                </p>
                            </div>
                            <p style='font-size:14px; color:#666; line-height:1.6;'>
                                If you did not request for an OTP, please ignore this email.
                            </p>
                        </div>
                        <div style='background:#f4f6fb; padding:18px; text-align:center; font-size:12px; color:#777;'>
                            &copy; " . date('Y') . " {$settings->sitename}
                        </div>
                    </div>
                </div>";
            $notification = new \Notification();
            $to = [$userInfo['profile']['name']=>$userInfo['logger']['email']];
            $from = [$settings->sitename=>"{$settings->emails[0]}@{$settings->domain}"];
            $notification->sendMail(['to'=>$to, 'from'=>$from], 'Activation OTP', $body);
        }
        else{
            throw new UserException("player activation failed");
        }
    }

    /**
     * for retrieving an player information
     *
     * @param bool removePassword if true password will be removed from the user information
     * @return array info of the user ['l'=>$u, 'profile'=>$p, 'player'=>$player]
     */
    public function getInfo(bool $removePassword = true):array
    {
        $loginInfo = [];
        if ($loginId = \User::loginIdFrmProfileId($this->id)) {
            $loginInfo  = $this->tblLogger->retrieveOne($loginId);
            if ($removePassword) {
                unset($loginInfo['password']);
            }
        }
        
        $profileInfo = $this->tblProfile->retrieveOne($this->id);

        $sql = "SELECT * FROM player WHERE profile = :profile";
        $profileTypeInfo = $this->query->executeSql($sql, ["profile"=>$profileInfo['id']])['rows'][0];

        $playerInfo['logger'] = $loginInfo;
        $playerInfo['profile'] = $profileInfo;
        $playerInfo['player'] = $profileTypeInfo;
        $playerInfo['type'] = 'player';
        return $playerInfo;
    }

    /**
     * Updates a player's information
     *
     * @param array $data an associative array containing the information to be updated
     * @throws UserException if the update fails
     */
    public function update(array $data):void{
        $profileSql = $playerSql = "";
        $profileParam = $playerParam = [];
        
        if(isset($data['name'])){
            if($profileSql){
                $profileSql .= " ,name = :name ";
                $profileParam['name'] = $data['name'];
            }
            else{
                $profileSql .= " UPDATE ".ProfileMgr::TABLE." SET name = :name ";
                $profileParam['name'] = $data['name'];
            }
        }

        if(isset($data['picture'])){
            if($profileSql){
                $profileSql .= " ,picture = :picture ";
                $profileParam['picture'] = $data['picture'];
            }
            else{
                $profileSql .= " UPDATE ".ProfileMgr::TABLE." SET picture = :picture ";
                $profileParam['picture'] = $data['picture'];
            }
        }

        if(isset($data['mode'])){
            if(!in_array($data['mode'], self::DISPLAY_MODES)){
                throw new UserException("invalid mode");
            }

            if($playerSql){
                $playerSql .= " ,mode = :mode ";
                $playerParam['mode'] = $data['mode'];
            }
            else{
                $playerSql .= " UPDATE ".self::TABLE." SET mode = :mode ";
                $playerParam['mode'] = $data['mode'];
            }
        }

        if(isset($data['twofactor'])){
            if(!in_array($data['twofactor'], self::TWO_FACTORS)){
                throw new UserException("invalid mode");
            }

            if($playerSql){
                $playerSql .= " ,twofactor = :twofactor ";
                $playerParam['twofactor'] = $data['twofactor'];
            }
            else{
                $playerSql .= " UPDATE ".self::TABLE." SET twofactor = :twofactor ";
                $playerParam['twofactor'] = $data['twofactor'];
            }
        }        

        try{
            if($profileSql && $profileParam){
                $profileSql .= " WHERE id = :id";
                $profileParam['id'] = $this->id;
                $this->query->executeSql($profileSql, $profileParam);
            }
    
            if($playerSql && $playerParam){
                $playerSql .= " WHERE profile = :profile";
                $playerParam['profile'] = $this->id;
                $this->query->executeSql($playerSql, $playerParam);
            }
        }
        catch(Exception $e){
            throw new UserException("Player info update failed");
        }
    }

    /**
     * for retrieving several player information
     *
     * @param integer $start the profile.id to start the user info from, $start been inclusive
     * @param integer $count the number of players info to retrieve
     * @return array
     */
    public static function getAllPlayerInfo(int $start = 0, int $count = 10_000):array
    {
        $playersInfo = [];
        $query = new \Query();

        $profileTyptMgt = new \ProfileTypeMgt();
        $profileTypeId = $profileTyptMgt->getInfoFrmName("player")['id'];

        $sql = "SELECT id FROM profile WHERE profile_type = :profileType ORDER BY id DESC LIMIT $count OFFSET $start";
        if ($result = $query->executeSql($sql, ['profileType'=>$profileTypeId])['rows']) {
            foreach ($result as $aResult) {
                $player = new Player($aResult['id']);
                $playersInfo[] = $player->getInfo();
            }
        }
        return $playersInfo;
    }

    /**
     * Change the user's password and notify them if needed
     *
     * @param string $password The new password for the user
     * @param bool $notify If true, notify the user of the password change via email
     *
     * @throws UserException If the password change fails
     */
    public function changePlayerPassword(string $oldPassword, string $newPassword): void
    {
        $tblLogger = LoggerMgr::TABLE;
        $tblProfile = ProfileMgr::TABLE;
        $sql = "SELECT {$tblLogger}.*, {$tblProfile}.name  FROM $tblLogger INNER JOIN $tblProfile ON {$tblLogger}.id = {$tblProfile}.logger WHERE {$tblProfile}.id = :id";

        if ($loggerInfo = $this->query->executeSql($sql, ['id'=>$this->id])['rows']) {
             $loggerInfo = $loggerInfo[0];   
        }
        else{
            throw new UserException("Unable to resolve user credentials");
        }

        $storedHash = $loggerInfo['password'];
        if (!password_verify($oldPassword, $storedHash)) {
            throw new UserException("Old password is incorrect");
        }

        if (password_verify($newPassword, $storedHash)) {
            throw new UserException("New password cannot be the same as old password");
        }
        
        $loggerMgr = new LoggerMgr();
        $result = $loggerMgr->validatePassword($newPassword);

        if ($result['errors']) {
            throw new UserException(implode(', ', $result['errors']));
        }

        $newHashedPassword = $result['data'];
        $sql = "UPDATE $tblLogger SET password = :password WHERE id = :id";
        $this->query->executeSql($sql, ['password' => $newHashedPassword, 'id' => $loggerInfo['id']]);

        // =====================
        // OPTIONAL: INVALIDATE SESSIONS
        // =====================
        // Example (if you store access tokens):
        // $this->invalidateSessions($loggerId);

        self::sendChangedPasswordMail($loggerInfo['email'], $loggerInfo['name']);
    }

    /**
     * Request a password reset for a user
     *
     * @param string $email The email address of the user to request a password reset for
     *
     * This function sends a password reset email to the user with a one-time password (OTP) that can be used to reset their password.
     * The OTP is valid for 15 minutes. If the user does not request a password reset, they can ignore this email. Their account remains secure.
     */
    public static function requestPasswordReset(string $email): void
    {
        $tblLogger = LoggerMgr::TABLE;
        $tblProfile = ProfileMgr::TABLE;
        $query = new Query();
        $sql = "SELECT {$tblLogger}.*, {$tblProfile}.name  
            FROM $tblLogger INNER JOIN $tblProfile ON {$tblLogger}.id = {$tblProfile}.logger 
            WHERE {$tblLogger}.email = :email";

        if ($loggerInfo = $query->executeSql($sql, ['email'=>$email])['rows']) {
             $loggerInfo = $loggerInfo[0];   
        }
        else{
            return;
        }

        $token = random_int(100_000, 999_999);
        $expiry = (new DateTime('+15 minutes'))->format('Y-m-d H:i:s');

        $sql = "UPDATE $tblLogger SET reset_token = :token, reset_time = :expiry WHERE id = :id";
        $query->executeSql($sql, ['token' => $token, 'expiry' => $expiry, 'id' => $loggerInfo['id']]);

        $settings = (new Settings(SETTING_FILE, true))->getDetails();
        $body = "
            <div style='font-family:Arial, Helvetica, sans-serif; background:#f4f6fb; padding:30px;'>
                <div style='max-width:600px; margin:auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,0.08);'>
                    
                    <div style='padding:35px;'>
                        <p style='font-size:15px;'>Hello <strong>{$loggerInfo['name']}</strong>,</p>

                        <p style='font-size:15px; line-height:1.6; color:#444;'>
                            We received a request to reset your password on <strong>{$settings->sitename}</strong>.
                        </p>

                        <p style='font-size:15px; line-height:1.6; color:#444;'>
                            Please use the One-Time Password (OTP) below to reset your password:
                        </p>

                        <div style='text-align:center; margin:30px 0;'>
                            <div style='display:inline-block;
                                font-size:32px;
                                letter-spacing:8px;
                                padding:18px 28px;
                                background:#007bff;
                                color:#ffffff;
                                border-radius:10px;
                                font-weight:bold;'>
                                $token
                            </div>
                            <p style='margin-top:12px; font-size:13px; color:#666;'>
                                This OTP expires in 15 minutes
                            </p>
                        </div>

                        <p style='font-size:14px; color:#666; line-height:1.6;'>
                            If you did not request a password reset, please ignore this email. Your account remains secure.
                        </p>

                        <p style='font-size:14px; color:#666; line-height:1.6; margin-top:20px;'>
                            If you need help, contact our support team at 
                            <strong>{$settings->emails[1]}@{$settings->domain}</strong>.
                        </p>
                    </div>

                    <div style='background:#f4f6fb; padding:18px; text-align:center; font-size:12px; color:#777;'>
                        &copy; " . date('Y') . " {$settings->sitename}
                    </div>

                </div>
            </div>
        ";

        $Notification = new Notification();
        $to = [$loggerInfo['name'] => $loggerInfo['email']];
        $from = [$settings->sitename => "{$settings->emails[0]}@{$settings->domain}"];
        $Notification->sendMail(['to' => $to, 'from' => $from], 'Password Reset', $body);        
    }

    public static function apiResetPassword(string $email, string $token, string $newPassword): void
    {
        $tblLogger = LoggerMgr::TABLE;
        $tblProfile = ProfileMgr::TABLE;
        $query = new Query();
        $sql = "SELECT {$tblLogger}.*, {$tblProfile}.name  
            FROM $tblLogger INNER JOIN $tblProfile ON {$tblLogger}.id = {$tblProfile}.logger 
            WHERE {$tblLogger}.email = :email";

        if ($loggerInfo = $query->executeSql($sql, ['email'=>$email])['rows']) {
             $loggerInfo = $loggerInfo[0];   
        }
        else{
            throw new UserException("Invalid credentials");
        }

        if ($loggerInfo['reset_token'] != $token || new DateTime() > new DateTime($loggerInfo['reset_time'])
        ) {
            throw new UserException("Invalid or expired token");
        }

        $result = (new LoggerMgr())->validatePassword($newPassword);
        if ($result['errors']) {
            throw new UserException(implode(', ', $result['errors']));
        }

        $hashedPassword = $result['data'];
        $sql = "UPDATE $tblLogger SET password = :password, reset_token = NULL, reset_time = NULL WHERE id = :id";
        $query->executeSql($sql, ['password' => $hashedPassword, 'id' => $loggerInfo['id']]);

        self::sendChangedPasswordMail($email, $loggerInfo['name']);
    }

    private static function sendChangedPasswordMail($email, $name) {
        $settings = (new Settings(SETTING_FILE, true))->getDetails();
        $body = "
            <div style='font-family:Arial, Helvetica, sans-serif; background:#f4f6fb; padding:30px;'>
                <div style='max-width:600px; margin:auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,0.08);'>
                    <div style='padding:35px;'>P
                        <p style='font-size:15px;'>Hello <strong>$name</strong>,</p>

                        <p style='font-size:15px; line-height:1.6; color:#444; margin-bottom:10px;'>
                            This is to notify you that your password on <strong>{$settings->sitename}</strong> was successfully changed on 
                            <strong>" . (new DateTime())->format("Y-m-d H:i:s") . "</strong>.
                        </p>

                        <p style='font-size:15px; line-height:1.6; color:#444; margin-bottom:10px;'>
                            If you made this change, no further action is required. If you did <strong>not</strong> made this password change, 
                            please reset your password immediately. And contact our support team at 
                            <strong>{$settings->emails[1]}@{$settings->domain}</strong>.
                        </p>

                        <p style='font-size:15px; line-height:1.6; color:#444; margin-bottom:40px;'>
                            For your security, we never include passwords in emails. Keep your account details safe and never share your login 
                            information.
                        </p>
                    </div>
                    <div style='background:#f4f6fb; padding:18px; text-align:center; font-size:12px; color:#777;'>
                        &copy; " . date('Y') . " {$settings->sitename}
                    </div>
                </div>
            </div>
        ";

        $Notification = new Notification();
        $to = [$name => $email];
        $from = [$settings->sitename => "{$settings->emails[0]}@{$settings->domain}"];
        $Notification->sendMail(['to' => $to, 'from' => $from], 'Your Password Has Been Changed', $body);
    }
}
