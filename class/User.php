<?php

use Mpdf\Cache;

/**
 * User
 *
 * A class managing users
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2022, 1.1 => February 2023, 1.3 => November 2023
 * @link        alabiansolutions.com
*/

class UserException extends Exception
{
}

class User
{
    /** @var DbConnect an instance of DbConnect  */
    protected DbConnect $dbConnect;

    /** @var Query an instance of Query  */
    protected Query $query;

    /** @var Table an instance of Table  */
    protected Table $tblLogger;

    /** @var Table an instance of Table  */
    protected Table $tblProfile;

    /** @var int profile table id*/
    protected int $id;

    /** @var array collection of status values */
    public const STATUS = ['active', 'inactive', 'active'=>'active', 'inactive'=>'inactive'];

    /**
     * instantiation of User
     *
     * @param int $id the profile table id
     */
    public function __construct(int $id)
    {
        $DbConnect = DbConnect::getInstance(SETTING_FILE);
        $this->dbConnect = $DbConnect;
        $this->query = new Query();
        
        $errors = [];
        $this->tblLogger = new Table(LoggerMgr::TABLE);
        $this->tblProfile = new Table(ProfileMgr::TABLE);
        if (!$this->tblProfile->retrieveOne($id)) {
            $errors[] = "invalid profile ";
        }
                
        if ($errors) {
            throw new UserException("User instantiation error: ".implode(", ", $errors));
        }
        
        $this->id = $id;
    }

    /**
     * for getting a user profile id
     *
     * @return integer the user profile id
     */
    public function getId():int
    {
        return $this->id;
    }
    
    /**
     * for creating a new user
     *
     * @param string name name of the user
     * @param array profileType [type=>val, subType=>val] the profile type and sub type
     * @param string $password unhashed password (min 8 characters, at least one alphabet and one digit)
     * @param string $identity unique identifier value either the email, phone or username
     * @param string $identityType unique identifier type either email, phone or username
     * @return array info of the newly created user ['logger'=>$l, 'profile'=>$p, 'profileSub'=>$ps, 'type'=>$t]
     */
    public static function create(
        string $name,
        array $profileType,
        string $password = "",
        string $identity = "",
        string $identityType = Authentication::ALL_IDENTITY_TYPE['email']
    ):array {
        $userInfo = $loggerInfo = [];
        $DbConnect = DbConnect::getInstance(SETTING_FILE);
        
        try {
            $Query = new Query(LoggerMgr::TABLE);
            $DbConnect->beginTransaction();
            if ($password && $identity && $identityType) {
                if (!in_array($identityType, Authentication::ALL_IDENTITY_TYPE)) {
                    throw new UserException("invalid identity type");
                }
                $columnName = "$identityType , password, activation_token, activation_time ";
                $columnValue = ":$identityType , :password, :activationToken, :activationtime ";
                $resultSql = "INSERT INTO " . LoggerMgr::TABLE . "($columnName) VALUES ($columnValue)";
                $loggerMgr = new LoggerMgr();
                $result = $loggerMgr->validatePassword($password);
                if ($result['errors']) {
                    throw new UserException(implode(', ', $result['errors']));
                }
                $password = $result['data'];
                $otp = rand(100_000, 999_999);
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

                if ($identityType == 'email') {
                    $result = $loggerMgr->validateEmail($identity);
                    if ($result['errors']) {
                        throw new UserException(implode(', ', $result['errors']));
                    }
                    $identity = $result['data'];
                }
                if ($identityType == 'phone') {
                    $result = $loggerMgr->validatePhone($identity);
                    if ($result['errors']) {
                        throw new UserException(implode(', ', $result['errors']));
                    }
                    $identity = $result['data'];
                }
                if ($identityType == 'username') {
                    $result = $loggerMgr->validateUsername($identity);
                    if ($result['errors']) {
                        throw new UserException(implode(', ', $result['errors']));
                    }
                    $identity = $result['data'];
                }
                $resultBind = [$identityType=>$identity, 'password'=>$password,
                    'activationToken'=>$activationToken, 'activationtime'=>$activationTokenTime];
                $loggerId = $Query->executeSql($resultSql, $resultBind)['lastInsertId'];
                $tblLogger = new Table(LoggerMgr::TABLE);
                $loggerInfo = $tblLogger->retrieveOne($loggerId);
            }

            $profileTypeMgt = new ProfileTypeMgt();
            $allProfileTypesInfo = $profileTypeMgt->getAllProfileTypeInfo(true);
            if (!isset($allProfileTypesInfo[$profileType['type']])) {
                throw new UserException("invalid profile type");
            } else {
                $profileSubtypes = json_decode($allProfileTypesInfo[$profileType['type']]['subs'], true);
                if (!in_array($profileType['subType'], $profileSubtypes)) {
                    throw new UserException("invalid sub profile type");
                }
            }
            
            $Query->setTable(ProfileMgr::TABLE);
            $profileTypeId = $allProfileTypesInfo[$profileType['type']]['id'];
            $tblProfile = new Table(ProfileMgr::TABLE);
            if ($errors = ColumnMgr::nonEmptyVarcharMax255($name, "name")['errors']) {
                throw new UserException(implode(", ", $errors));
            }
            $columnName = "profile_type , name";
            $columnValue = ":profile_type, :name";
            $resultBind = ['profile_type' => $profileTypeId, 'name' => $name];
            if ($loggerInfo) {
                $columnName .= ", logger";
                $columnValue .= ", :logger";
                $resultBind['logger'] = $loggerId;
            }
            $resultSql = "INSERT INTO " . ProfileMgr::TABLE . "($columnName) VALUES ($columnValue)";
            $profileId = $Query->executeSql($resultSql, $resultBind)['lastInsertId'];

            $Query->setTable($profileType['type']);
            $sql = "INSERT INTO {$profileType['type']} (profile, type) VALUES (:profile, :type)";
            $bind = ['profile'=>$profileId, 'type' =>$profileType['subType']];
            $profileTypeId = $Query->executeSql($sql, $bind)['lastInsertId'];
            
            $DbConnect->commit();
        } catch (Exception $e) {
            $DbConnect->rollBack();
            throw new UserException($e->getMessage());
        }

        $profileTypeTable = new Table($profileType['type']);
        $userInfo = [
            'logger'=>$loggerInfo,
            'profile'=>$tblProfile->retrieveOne($profileId),
            $profileType['type']=>$profileTypeTable->retrieveOne($profileTypeId),
            'type'=>$profileType['type']
        ];
        return $userInfo;
    }

    /**
     * Sends an email to the newly created logger user
     * @param array $userInfo - The user information
     */
    public static function sendLoggerCreationMail(array $userInfo):void
    {
        $settings = (new \Settings(SETTING_FILE, true))->getDetails();
        $body = "
            <div style='font-family:Arial, Helvetica, sans-serif; background:#f4f6fb; padding:30px;'>
                <div style='max-width:600px; margin:auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,0.08);'>
                    <div style='background:#3300c9; padding:25px; text-align:center; color:#ffffff;'>
                        <h2 style='margin:0;'>Welcome to {$settings->sitename}</h2>
                    </div>
                    <div style='padding:35px;'>
                        <p style='font-size:15px;'>Hello <strong>{$userInfo['profile']['name']}</strong>,</p>
                        <p style='font-size:15px; line-height:1.6; color:#444;'>
                            Congratulations! Your account has been successfully created on 
                            <strong>{$settings->sitename}</strong>. You're just one step away from joining the quiz arena.
                        </p>
                        <p style='font-size:15px; line-height:1.6; color:#444;'>
                            Your account is currently <strong>inactive</strong>. Use the One-Time Password (OTP) below to activate your account after your first login.
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
                                {$userInfo['logger']['activation_token']}
                            </div>
                            <p style='margin-top:12px; font-size:13px; color:#666;'>
                                Your Activation Code
                            </p>
                        </div>
                        <p style='font-size:14px; color:#666; line-height:1.6;'>
                            If you did not create this account, please ignore this email.
                        </p>
                    </div>
                    <div style='background:#f4f6fb; padding:18px; text-align:center; font-size:12px; color:#777;'>
                        © " . date('Y') . " {$settings->sitename}
                    </div>
                </div>
            </div>";
        $notification = new \Notification();
        $to = [$userInfo['profile']['name']=>$userInfo['logger']['email']];
        $from = [$settings->sitename=>"{$settings->emails[0]}@{$settings->domain}"];
        $notification->sendMail(['to'=>$to, 'from'=>$from], 'Account Creation', $body);
    }

    /**
     * for getting a user login id from their profile id
     *
     * @param integer $profileId the user profile id
     * @return int the user login id or 0 if not found
     */
    public static function loginIdFrmProfileId(int $profileId):int
    {
        $loginId = (new Table(ProfileMgr::TABLE))->retrieveOne($profileId)['logger'];
        $loginId = $loginId ? $loginId : 0;
        return $loginId;
    }

    /**
     * for getting a user login id from their username
     *
     * @param string $username the user's username
     * @return int the user login id or 0 if not found
     */
    public static function loginIdFrmUsername(string $username):int
    {
        $result = (new Table(LoggerMgr::TABLE))->select(['id'], ['username'=>['=', $username, 'isValue']]);
        $loginId = $result ? $result[0]['id'] : 0;
        return $loginId;
    }

    /**
     * for getting a user login id from their phone
     *
     * @param string $phone the user's phone
     * @return int the user login id or 0 if not found
     */
    public static function loginIdFrmPhone(string $phone):int
    {
        $result = (new Table(LoggerMgr::TABLE))->select(['id'], ['phone'=>['=', $phone, 'isValue']]);
        $loginId = $result ? $result[0]['id'] : 0;
        return $loginId;
    }

    /**
     * for getting a user profile id from their login id
     *
     * @param integer $loginId the user login id
     * @return int the user profile id or 0 if not found
     */
    public static function profileIdFrmLoginId(int $loginId):int
    {
        
        $profileId = (new Table(ProfileMgr::TABLE))->select(['id'], ['logger' => ['=', $loginId, 'isValue']])[0]['id'];
        $profileId = $profileId ? $profileId : 0;
        return $profileId;
    }

    /**
     * for getting a user profile no from their profile id
     *
     * @param integer $profileId the user profile id
     * @return string the user profile id or emtpy string if not found
     */
    public static function profileNoFrmProfileId(int $profileId):string
    {
        $profileNo = "";
        if ($info = (new Table(ProfileMgr::TABLE))->retrieveOne($profileId)) {
            $profileNo = $info['profile_no'];
        }
        return $profileNo;
    }

    /**
     * for getting a user profile info from their username
     *
     * @param string $email the user's email
     * @return array profile info array
     */
    public static function profileInfoFrmEmail(string $email):array
    {
        $sql = "SELECT profile.* FROM profile 
                JOIN logger ON profile.logger = logger.id
            WHERE logger.email = :email";
        $bind = ['email'=>$email];
        $result = (new Query())->executeSql($sql, $bind)['rows'];
        return $result ? $result[0] : [];
    }

    /**
     * for getting a user profile id from their profile no
     *
     * @param integer $profileNo the user profile no
     * @return int the user profile no or 0 if not found
     */
    public static function profileIdFrmProfileNo(string $profileNo):int
    {
        $profileId = 0;
        if ($info = (new Table(ProfileMgr::TABLE))->retrieve(where:['profile_no'=>['=', $profileNo, 'isValue']])) {
            $profileId = $info[0]['id'];
        }
        return $profileId;
    }

    /**
     * for getting a user username, phone and profile id from their logger id
     *
     * @param integer $loggerId the user logger id
     * @return array [username=>val, phone=>val, profile=>val] or [] if not found
     */
    public static function usernamePhoneProfileIdFrmLoggerId(int $loggerId):array
    {
        $info = [];
        $sql = "SELECT logger.username, logger.phone, profile.id 
            FROM logger INNER JOIN profile ON logger.id = profile.logger 
            WHERE logger.id = :id";
        $bind = ['id' => $loggerId];
        $query = new Query();
        if ($result = $query->executeSql($sql, $bind)['rows']) {
            $info = ['username'=>$result[0]['username'], 'phone'=>$result[0]['phone'], 'profile'=>$result[0]['id']];
        }
        return $info;
    }

    /**
     * for getting a user logger id, username and phone from their profile id
     *
     * @param integer $profileId the user profile id
     * @return array [logger=>val, username=>val, phone=>val] or [] if not found
     */
    public static function loggerIdUsernamePhoneFrmProfileId(int $profileId):array
    {
        $info = [];
        $sql = "SELECT logger.username, logger.phone, logger.id 
            FROM logger INNER JOIN profile ON logger.id = profile.logger 
            WHERE profile.id = :id";
        $bind = ['id' => $profileId];
        $query = new Query();
        if ($result = $query->executeSql($sql, $bind)['rows']) {
            $info = ['username'=>$result[0]['username'], 'phone'=>$result[0]['phone'], 'logger'=>$result[0]['id']];
        }
        return $info;
    }

    /**
     * for changing a user's logger information
     *
     * @param array $info a collection of the user logger information [col=>val,...]
     * @param bool $identifierUniqueness a check if identifier should be unique
     * @return array [col=>error,...] a collection of error that prevented the change
     */
    public function changeLoggerInfo(array $info, $identifierUniqueness = true): array
    {
        $columns = $errors = [];
        $loggerMgr = new LoggerMgr();
        if (array_key_exists('email', $info)) {
            $validation = $loggerMgr->validateEmail($info['email'], $identifierUniqueness);
            $validation['errors'] ? $errors['email'] = $validation['errors'] : $columns['email'] = [$validation['data'], 'isValue'];
        }
        if (array_key_exists('phone', $info)) {
            $validation = $loggerMgr->validatePhone($info['phone'], $identifierUniqueness);
            $validation['errors'] ? $errors['phone'] = $validation['errors'] : $columns['phone'] = [$validation['data'], 'isValue'];
        }
        if (array_key_exists('username', $info)) {
            $validation = $loggerMgr->validateUsername($info['username'], $identifierUniqueness);
            $validation['errors'] ? $errors['username'] = $validation['errors'] : $columns['username'] = [$validation['data'], 'isValue'];
        }
        if (array_key_exists('status', $info)) {
            $validation = $loggerMgr->validateStatus($info['status']);
            $validation['errors'] ? $errors['status'] = $validation['errors'] : $columns['status'] = [$validation['data'], 'isValue'];
        }
        if (array_key_exists('password', $info)) {
            $validation = $loggerMgr->validatePassword($info['password']);
            $validation['errors'] ? $errors['password'] = $validation['errors'] : $columns['password'] = [$validation['data'], 'isValue'];
        }
        if (array_key_exists('reset_token', $info)) {
            $validation = $loggerMgr->validateResetToken($info['reset_token']);
            $validation['errors'] ? $errors['reset_token'] = $validation['errors'] : $columns['reset_token'] = [$validation['data'], 'isValue'];
        }
        if (array_key_exists('reset_time', $info)) {
            $validation = $loggerMgr->validateResetTime($info['reset_time']);
            $validation['errors'] ? $errors['reset_time'] = $validation['errors'] : $columns['reset_time'] = [$validation['data'], 'isValue'];
        }
        if (array_key_exists('activation_token', $info)) {
            $validation = $loggerMgr->validateActivationToken($info['activation_token']);
            $validation['errors'] ? $errors['activation_token'] = $validation['errors'] : $columns['activation_token'] = [$validation['data'], 'isValue'];
        }
        if (array_key_exists('activation_time', $info)) {
            $validation = $loggerMgr->validateActivationTime($info['activation_time']);
            $validation['errors'] ? $errors['activation_time'] = $validation['errors'] : $columns['activation_time'] = [$validation['data'], 'isValue'];
        }
        
        if (!$errors && $columns) {
            $this->tblLogger->update($columns, ['id'=>['=', User::loginIdFrmProfileId($this->id), 'isValue']]);
        }

        return $errors;
    }

    /**
     * Change the user's password and notify them if needed
     *
     * @param string $password The new password for the user
     * @param bool $notify If true, notify the user of the password change via email
     *
     * @throws UserException If the password change fails
     */
    public function changePassword(string $password, bool $notify = true): void
    {
        if ($error = $this->changeLoggerInfo(['password' => $password])) {
            throw new UserException($error['password']);
        }

        if ($notify) {
            $userInfo = $this->getInfo();
            $settings = (new Settings(SETTING_FILE, true))->getDetails();

            $body = "
                <p style='margin-bottom:10px; margin-top:10px;'>Hello {$userInfo['profile']['name']},</p>

                <p style='margin-bottom:10px;'>
                    This is to notify you that your password on <strong>{$settings->sitename}</strong> was successfully changed on 
                    <strong>" . (new DateTime())->format("Y-m-d H:i:s") . "</strong>.
                </p>

                <p style='margin-bottom:10px;'>
                    If you made this change, no further action is required.
                </p>

                <p style='margin-bottom:10px;'>
                    If you did <strong>not</strong> request this password change, please reset your password immediately by visiting:
                    <a href='{$settings->machine->url}' style='text-decoration:underline; color:white;'>{$settings->machine->url}</a>
                    or contact our support team at <strong>{$settings->emails[1]}@{$settings->domain}</strong>.
                </p>

                <p style='margin-bottom:60px;'>
                    For your security, we never include passwords in emails. Keep your account details safe and never share your login information.
                </p>
            ";

            $Notification = new Notification();
            $to = [$userInfo['profile']['name'] => $userInfo['logger']['email']];
            $from = [$settings->sitename => "{$settings->emails[0]}@{$settings->domain}"];
            $Notification->sendMail(['to' => $to, 'from' => $from], 'Your Password Has Been Changed', $body);
        }
    }

    /**
     * Create password reset token and time, then mail it to the user
     * @return void
     * @throws UserException if the password reset process fails
     */
    public function resetPassword(): void
    {
        $errors = [];
        $loggerMgr = new LoggerMgr();

        $result = $loggerMgr->validateResetToken();
        if ($result['errors']) {
            $errors[] = $result['errors'];
        }
        $resetToken = $result['data'];

        $result = $loggerMgr->validateResetTime();
        if ($result['errors']) {
            $errors[] = $result['errors'];
        }
        $resetTime = $result['data'];

        if ($errors) {
            throw new UserException(implode(", ", $errors));
        }

        $columns = [
            'reset_token' => [$resetToken, 'isValue'],
            'reset_time'  => [$resetTime, 'isValue'],
        ];
        $userInfo = $this->getInfo();
        $this->tblLogger->update($columns, ['id' => ["=", $userInfo['logger']['id'], 'isValue']]);

        // --- Email notification ---
        $settings = (new Settings(SETTING_FILE, true))->getDetails();
        $body = "
            <p style='margin-bottom:10px; margin-top:10px;'>Hello {$userInfo['profile']['name']},</p>

            <p style='margin-bottom:10px;'>
                We received a request to reset your password on <strong>{$settings->sitename}</strong>.
            </p>

            <p style='margin-bottom:10px;'>
                Please use the password reset token below to complete the process:
            </p>

            <p style='font-size:18px; font-weight:bold; margin:20px 0; text-align:center;'>
                {$resetToken}
            </p>

            <p style='margin-bottom:10px;'>
                This token will expire on 
                <strong>" . (new DateTime($resetTime))->format('l, jS F Y \a\t h:i A') . "</strong>.
            </p>

            <p style='margin-bottom:10px;'>
                If you did not request a password reset, please ignore this email. 
                Your password will remain unchanged.
            </p>

            <p style='margin-top:30px;'>
                Regards,<br>
                <strong>{$settings->sitename} Team</strong><br>
                <a href='mailto:{$settings->emails[1]}@{$settings->domain}'>
                    {$settings->emails[1]}@{$settings->domain}
                </a>
            </p>
        ";

        $Notification = new Notification();
        $to = [$userInfo['profile']['name'] => $userInfo['logger']['email']];
        $from = [$settings->sitename => "{$settings->emails[0]}@{$settings->domain}"];
        $Notification->sendMail(['to' => $to, 'from' => $from], 'Password Reset Token', $body);
    }

    /**
     * Verify if the password reset code is valid
     *
     * @param string $resetCode The password reset code to verify
     * @return bool True if the password reset code is valid, false otherwise
     */
    public function verifyPasswordResetCode(string $resetCode): bool
    {
        $ok = false;

        $userInfo = $this->getInfo();
        $sql = "SELECT id FROM ".LoggerMgr::TABLE." WHERE id = :id AND reset_token = :reset_token AND reset_time > :reset_time";
        $bind = ['id' => $userInfo['logger']['id'], 'reset_token' => $resetCode, 'reset_time' => date('Y-m-d H:i:s')];
        if ($result = $this->query->executeSql($sql, $bind)['rows']) {
            $ok = true;
        }

        return $ok;
    }

    /**
     * Verify if the account activation code is valid
     *
     * @param string $code The account activation code to verify
     * @return bool True if the activation code is valid, false otherwise
     */
    public function verifyActivationCode(string $code): bool
    {
        $ok = false;

        $userInfo = $this->getInfo();
        $sql = "SELECT id FROM ".LoggerMgr::TABLE." WHERE id = :id AND activation_token = :activation_token AND activation_time > :activation_time";
        $bind = ['id' => $userInfo['logger']['id'], 'activation_token' => $code, 'activation_time' => date('Y-m-d H:i:s')];
        if ($result = $this->query->executeSql($sql, $bind)['rows']) {
            $ok = true;
        }

        return $ok;
    }

    /**
     * for changing a user's profile information
     *
     * @param array $info a collection of the user profile information [col=>val,...]
     * @return array [col=error,...] a collection of error that prevented the change
     */
    public function changeProfileInfo(array $info): array
    {
        $columns = $errors = [];
        $profileMgr = new ProfileMgr();
        if (array_key_exists('logger', $info)) {
            $validation = $profileMgr->validateLogger($info['logger']);
            $validation['errors'] ? $errors[] = $validation['errors'] : $columns['logger'] = [$validation['data'], 'isValue'];
        }
        if (array_key_exists('profile_type', $info)) {
            $validation = $profileMgr->validateProfileType($info['profile_type']);
            $validation['errors'] ? $errors[] = $validation['errors'] : $columns['profile_type'] = [$validation['data'], 'isValue'];
            $oldProfileTypeId = $this->getInfo()['profile']['profile_type'];
            if ($oldProfileTypeId != $info['profile_type']) {
                $profileTypeMgt = new ProfileTypeMgt();
                $oldProfileTypeInfo = $profileTypeMgt->getInfo($oldProfileTypeId);
                $newProfileTypeInfo = $profileTypeMgt->getInfo($info['profile_type']);
                $sql = "DELETE FROM {$oldProfileTypeInfo['name']} WHERE profile = :profile";
                $this->query->executeSql($sql, ['profile' => $this->id]);
                $sql = "INSERT INTO {$newProfileTypeInfo['name']} (profile, type) VALUES (:profile, :type)";
                $type = json_decode($newProfileTypeInfo['subs'], true)[0];
                $this->query->executeSql($sql, ['profile' => $this->id, 'type' => $type]);
            }
        }
        if (array_key_exists('name', $info)) {
            $validation = $profileMgr->validateName($info['name']);
            $validation['errors'] ? $errors[] = $validation['errors'] : $columns['name'] = [$validation['data'], 'isValue'];
        }
        if (array_key_exists('picture', $info)) {
            $validation = $profileMgr->validatePicture($info['picture']);
            $validation['errors'] ? $errors[] = $validation['errors'] : $columns['picture'] = [$validation['data'], 'isValue'];
        }
        if (array_key_exists('emails', $info)) {
            $validation = $profileMgr->validateEmails($info['emails']);
            $validation['errors'] ? $errors[] = $validation['errors'] : $columns['emails'] = [$validation['data'], 'isValue'];
        }
        if (array_key_exists('phones', $info)) {
            $validation = $profileMgr->validatePhones($info['phones']);
            $validation['errors'] ? $errors[] = $validation['errors'] : $columns['phones'] = [$validation['data'], 'isValue'];
        }
        if (array_key_exists('gender', $info)) {
            $validation = $profileMgr->validateGender($info['gender']);
            $validation['errors'] ? $errors[] = $validation['errors'] : $columns['gender'] = [$validation['data'], 'isValue'];
        }
        if (array_key_exists('birthday', $info)) {
            $validation = $profileMgr->validateBirthday($info['birthday']);
            $validation['errors'] ? $errors[] = $validation['errors'] : $columns['birthday'] = [$validation['data'], 'isValue'];
        }
        if (array_key_exists('address', $info)) {
            $validation = $profileMgr->validateAddress($info['address']);
            $validation['errors'] ? $errors[] = $validation['errors'] : $columns['address'] = [$validation['data'], 'isValue'];
        }
        
        if (!$errors && $columns) {
            $this->tblProfile->update($columns, ['id'=>['=', $this->id, 'isValue']]);
        }

        return $errors;
    }
    
    /**
     * for deactivating a user login ability
     *
     * @param boolean $notify if true the user will be notified via mail or sms
     * @return void
     */
    public function deactivate(bool $notify = false)
    {
        if (User::loginIdFrmProfileId($this->id)) {
            $columns = ['status'=>['inactive', 'isValue']];
            $this->tblLogger->update($columns, ['id'=>['=', User::loginIdFrmProfileId($this->id), 'isValue']]);

            if ($notify) {
                $userInfo = $this->getInfo();
                $settings = (new Settings(SETTING_FILE, true))->getDetails();

                $body = "
                    <p style='margin-bottom:10px; margin-top:10px;'>Hello {$userInfo['profile']['name']},</p>

                    <p style='margin-bottom:10px;'>
                        This is to notify you that your account on <strong>{$settings->sitename}</strong> has been deactivated.
                    </p>

                    <p style='margin-bottom:10px;'>
                        This mail is for informational purpose only and no further action is required from your side. 
                        If you have any questions, please visit our website at 
                        <a href='{$settings->machine->url}' style='text-decoration:underline; color:white;'>{$settings->machine->url}</a>
                        or contact our support team at <strong>{$settings->emails[1]}@{$settings->domain}</strong>.
                    </p>
                ";

                $Notification = new Notification();
                $to = [$userInfo['profile']['name'] => $userInfo['logger']['email']];
                $from = [$settings->sitename => "{$settings->emails[0]}@{$settings->domain}"];
                $Notification->sendMail(['to' => $to, 'from' => $from], 'Your Password Has Been Changed', $body);
            }
        }
    }

    /**
     * for reactivating a user login ability
     *
     * @param string $activationCode if activationCode is given then is check before activation
     * @param boolean $checkTime if the activation time is checked before activation
     * @param boolean $notify if true the user will be notified via mail or sms
     * @return array an array of reactivated status ['reactivated'=>$r, 'reasons'=>$re];
     */
    public function reactivate(string $activationCode="", bool $checkTime=false, bool $notify = false):array
    {
        $reactivated = true;
        $reasons = [];
        
        if ($loggerId = User::loginIdFrmProfileId($this->id)) {
            $loggerInfo = $this->tblLogger->retrieveOne($loggerId);
            if ($activationCode) {
                if ($activationCode != $loggerInfo['activation_token']) {
                    $reactivated = false;
                    $reasons[] = "invalid activation code";
                }
            }
            if ($checkTime && new DateTime() < new DateTime($loggerInfo['activation_time'])) {
                $reactivated = false;
                $reasons[] = "expired activation code";
            }

            if ($reactivated) {
                $columns = ['status'=>['active', 'isValue']];
                $this->tblLogger->update($columns, ['id'=>['=', User::loginIdFrmProfileId($this->id), 'isValue']]);
            }

            if ($notify) {
                $userInfo = $this->getInfo();
                $settings = (new Settings(SETTING_FILE, true))->getDetails();

                $body = "
                    <p style='margin-bottom:10px; margin-top:10px;'>Hello {$userInfo['profile']['name']},</p>

                    <p style='margin-bottom:10px;'>
                        This is to notify you that your account on <strong>{$settings->sitename}</strong> has been activated.
                    </p>

                    <p style='margin-bottom:10px;'>
                        This mail is for informational purpose only and no further action is required from your side. 
                        If you have any questions, please visit our website at 
                        <a href='{$settings->machine->url}' style='text-decoration:underline; color:white;'>{$settings->machine->url}</a>
                        or contact our support team at <strong>{$settings->emails[1]}@{$settings->domain}</strong>.
                    </p>
                ";

                $Notification = new Notification();
                $to = [$userInfo['profile']['name'] => $userInfo['logger']['email']];
                $from = [$settings->sitename => "{$settings->emails[0]}@{$settings->domain}"];
                $Notification->sendMail(['to' => $to, 'from' => $from], 'Your Password Has Been Changed', $body);
            }
        }
        
        return ['reactivated'=>$reactivated, 'reasons'=>implode(", ", $reasons)];
    }

    /**
     * for deleting a user
     *
     * @param boolean $notify if true the user will be notified via mail or sms
     * @param boolean $force if true all user info will be removed from other tables to avoid SQL Integrity Violation
     * @return void
     */
    public function remove(bool $notify = false, bool $force = false)
    {
        if ($force) {
            //TODO remove all user id from all related tables
            throw new UserException("force removal not supported");
        }

        try {
            $profileInfo = $this->tblProfile->retrieveOne($this->id);
            $picture = $profileInfo['picture'];
            $profileTypeTable = (new ProfileTypeMgt())->getInfo($profileInfo['profile_type'])['name'];
            $userInfo = $this->getInfo();
            
            $sqlCollection = [];
            $sql = "DELETE FROM $profileTypeTable WHERE profile = :profileId";
            $bind = ['profileId' => $this->id];
            $sqlCollection[] = ['sql'=>$sql, 'bind' => $bind];
            $sql = "DELETE FROM ".ProfileMgr::TABLE." WHERE id = :profileId";
            $bind = ['profileId' => $this->id];
            $sqlCollection[] = ['sql'=>$sql, 'bind' => $bind];
            $sql = "DELETE FROM ".LoggerMgr::TABLE." WHERE id = :loggerId";
            $bind = ['loggerId' => User::loginIdFrmProfileId($this->id)];
            $sqlCollection[] = ['sql'=>$sql, 'bind' => $bind];
            if ($this->query->executeTransaction($sqlCollection)) {
                if ($picture != Functions::DEFAULT_AVATAR && $picture != Functions::DEFAULT_AVATAR_MALE
                    && $picture != Functions::DEFAULT_AVATAR_FEMALE) {
                    unlink(Functions::getAvatarDirectoryPath(true).$picture);
                }
                if ($notify) {
                    $settings = (new Settings(SETTING_FILE, true))->getDetails();

                    $body = "
                    <p style='margin-bottom:10px; margin-top:10px;'>Hello {$userInfo['profile']['name']},</p>

                    <p style='margin-bottom:10px;'>
                        This is to notify you that your account on <strong>{$settings->sitename}</strong> has been deleted.
                    </p>

                    <p style='margin-bottom:10px;'>
                        This mail is for informational purpose only and no further action is required from your side. 
                        If you have any questions, please visit our website at 
                        <a href='{$settings->machine->url}' style='text-decoration:underline; color:white;'>{$settings->machine->url}</a>
                        or contact our support team at <strong>{$settings->emails[1]}@{$settings->domain}</strong>.
                    </p>
                ";

                    $Notification = new Notification();
                    $to = [$userInfo['profile']['name'] => $userInfo['logger']['email']];
                    $from = [$settings->sitename => "{$settings->emails[0]}@{$settings->domain}"];
                    $Notification->sendMail(['to' => $to, 'from' => $from], 'Your Password Has Been Changed', $body);
                }
            } else {
                throw new UserException("user has dependencies");
            }
        } catch (\Throwable $th) {
            throw new UserException("User removal failed: ".$th->getMessage());
        }
    }

    /**
     * for retrieving a user information
     *
     * @param bool removePassword if true password will be removed from the user information
     * @return array info of the user ['l'=>$u, 'profile'=>$p, 'profileSub'=>$ps, 'type'=>$t]
     */
    public function getInfo(bool $removePassword = true):array
    {
        if (Xbook\Cache::has('userGetInfo')) {
            $userInfo = (new Xbook\Cache('userGetInfo'))->getData();
        } else {
            $loginInfo = [];
            if ($loginId = User::loginIdFrmProfileId($this->id)) {
                $loginInfo  = $this->tblLogger->retrieveOne($loginId);
                if ($removePassword) {
                    unset($loginInfo['password']);
                }
            }
            
            $profileInfo = $this->tblProfile->retrieveOne($this->id);

            $profileTypeMgt = new ProfileTypeMgt();
            $profileTypeTable = $profileTypeMgt->getInfo($profileInfo['profile_type'])['name'];
            $sql = "SELECT * FROM $profileTypeTable WHERE profile = :profile";
            $profileTypeInfo = $this->query->executeSql($sql, ["profile"=>$profileInfo['id']])['rows'][0];

            $userInfo['logger'] = $loginInfo;
            $userInfo['profile'] = $profileInfo;
            $userInfo[$profileTypeTable] = $profileTypeInfo;
            $userInfo['type'] = $profileTypeTable;

            Xbook\Cache::add('userGetInfo', $userInfo);
        }

        
        return $userInfo;
    }

    /**
     * for retrieving several user information
     *
     * @param integer $start the profile.id to start the user info from, $start been inclusive
     * @param integer $count the number of users info to retrieve
     * @return array
     */
    public static function getAllUserInfo(int $start = 0, int $count = 10000):array
    {
        $usersInfo = [];
        $Query = new Query();
        $TblLogger = new Table(LoggerMgr::TABLE);
        $ProfileTypeMgr  = new ProfileTypeMgt();
        $allProfileTypes = $ProfileTypeMgr->getAllProfileTypeIds();
        $allProfileTypes = array_flip($allProfileTypes);
        $sql = "SELECT id FROM profile ORDER BY id DESC LIMIT $count OFFSET $start";
        if ($result = $Query->executeSql($sql)['rows']) {
            foreach ($result as $aResult) {
                $user = new User($aResult['id']);
                $usersInfo[] = $user->getInfo();
            }
        }
        return $usersInfo;
    }
}
