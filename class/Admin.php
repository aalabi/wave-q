<?php

/**
 * Admin
 *
 * A class managing admin
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => April 2024
 * @link        alabiansolutions.com
*/

class Admin extends \User
{
    /** @var string admin table*/
    public const TABLE = "admin";

    /** @var int admin profile type id*/
    public const PROFILE_TYPE_ID = 1;

    /** @var array an admin info  */
    protected array $info;

    /**
     * Instantiation of Admin
     *
     * @param int $id The profile table ID of a user with an admin profile type
     * @throws \UserException If an invalid admin is instantiated
     */
    public function __construct(int $id)
    {
        parent::__construct($id);

        $profileTbl = \ProfileMgr::TABLE;
        $adminTbl   = \Admin::TABLE;
        $loggerTbl  = \LoggerMgr::TABLE;
        $sql = "
            SELECT 
                -- Profile fields
                p.id AS {$profileTbl}_id,
                p.status AS {$profileTbl}_status,
                p.profile_no,
                p.logger AS profile_logger_id,
                p.profile_type,
                p.name AS {$profileTbl}_name,
                p.picture AS {$profileTbl}_picture,
                p.emails AS {$profileTbl}_emails,
                p.phones AS {$profileTbl}_phones,
                p.gender AS {$profileTbl}_gender,
                p.birthday AS {$profileTbl}_birthday,
                p.address AS {$profileTbl}_address,
                p.website AS {$profileTbl}_website,
                p.note AS profile_note,
                p.created_at AS {$profileTbl}_created_at,
                p.updated_at AS {$profileTbl}_updated_at,

                -- Admin fields
                a.id AS {$adminTbl}_id,
                a.profile AS {$adminTbl}_profile_id,
                a.type AS {$adminTbl}_type,
                a.created_date AS {$adminTbl}_created_date,
                a.updated_date AS {$adminTbl}_updated_date,

                -- Logger fields
                l.id AS {$loggerTbl}_id,
                l.email AS {$loggerTbl}_email,
                l.phone AS {$loggerTbl}_phone,
                l.username AS {$loggerTbl}_username,
                l.status AS {$loggerTbl}_status,
                l.password AS {$loggerTbl}_password,
                l.reset_token AS {$loggerTbl}_reset_token,
                l.reset_time AS {$loggerTbl}_reset_time,
                l.activation_token AS {$loggerTbl}_activation_token,
                l.activation_time AS {$loggerTbl}_activation_time,
                l.created_at AS {$loggerTbl}_created_at,
                l.updated_at AS {$loggerTbl}_updated_at

            FROM {$profileTbl} p
            JOIN {$adminTbl} a ON p.id = a.profile
            JOIN {$loggerTbl} l ON p.logger = l.id
            WHERE a.{$profileTbl} = :id
        ";

        $result = $this->query->executeSql($sql, ['id' => $id])['rows'];
        if (!$result) {
            throw new \UserException("User instantiation error: profile not an admin");
        }

        $row = $result[0];
        $this->info = [
            $profileTbl => [
                'id'          => $row['profile_id'],
                'status'      => $row['profile_status'],
                'profile_no'  => $row['profile_no'],
                'logger'   => $row['profile_logger_id'],
                'profile_type'=> $row['profile_type'],
                'name'        => $row['profile_name'],
                'picture'     => $row['profile_picture'],
                'emails'      => $row['profile_emails'],
                'phones'      => $row['profile_phones'],
                'gender'      => $row['profile_gender'],
                'birthday'    => $row['profile_birthday'],
                'address'     => $row['profile_address'],
                'website'     => $row['profile_website'],
                'note'        => $row['profile_note'],
                'created_at'  => $row['profile_created_at'],
                'updated_at'  => $row['profile_updated_at'],
            ],
            $adminTbl => [
                'id'            => $row['admin_id'],
                'profile'    => $row['admin_profile_id'],
                'type'          => $row['admin_type'],
                'created_date'  => $row['admin_created_date'],
                'updated_date'  => $row['admin_updated_date'],
            ],
            $loggerTbl => [
                'id'               => $row['logger_id'],
                'email'            => $row['logger_email'],
                'phone'            => $row['logger_phone'],
                'username'         => $row['logger_username'],
                'status'           => $row['logger_status'],
                'password'         => $row['logger_password'],
                'reset_token'      => $row['logger_reset_token'],
                'reset_time'       => $row['logger_reset_time'],
                'activation_token' => $row['logger_activation_token'],
                'activation_time'  => $row['logger_activation_time'],
                'created_at'       => $row['logger_created_at'],
                'updated_at'       => $row['logger_updated_at'],
            ]
        ];

        $profileTypeMgt = new \ProfileTypeMgt();
        $profileTypeName = $profileTypeMgt->getInfo($this->info[$profileTbl]['profile_type'])['name'];
        $this->info['type'] = $profileTypeName;
    }

    /**
     * for creating a new user
     *
     * @param string name name of the user
     * @param array subProfileType the sub profile type
     * @param string $password unhashed password (min 8 characters, at least one alphabet and one digit)
     * @param string $identity unique identifier value either the email, phone or username
     * @param string $identityType unique identifier type either email, phone or username
     * @return array info of the newly created user ['logger'=>$l, 'profile'=>$p, 'profileSub'=>$ps, 'type'=>$t]
     */
    public static function create(
        string $name,
        array $subProfileType,
        string $password = "",
        string $identity = "",
        string $identityType = \Authentication::ALL_IDENTITY_TYPE['email']
    ):array {
        $userInfo =  parent::create($name, ['type' => 'admin', 'subType' => $subProfileType[0]], $password, $identity, $identityType);
        $profileNo = "ADM".str_pad($userInfo['profile']['id'], 4, "0", STR_PAD_LEFT);
        (new \Table(\ProfileMgr::TABLE))->updateOne($userInfo['profile']['id'], ['profile_no' => [$profileNo, 'isValue']]);
        parent::sendLoggerCreationMail($userInfo);
        return $userInfo;
    }

    /**
     * Sends an email to the newly created logger user
     * @param array $userInfo - The user information
     * @param string $otp - The otp for account activation
     */
    // public static function sendLoggerCreationMail(array $userInfo):void
    // {
    //     $settings = (new \Settings(SETTING_FILE, true))->getDetails();
    //     $body = "
    //         <div style='font-family:Arial, Helvetica, sans-serif; background:#f4f6fb; padding:30px;'>
    //             <div style='max-width:600px; margin:auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,0.08);'>
    //                 <div style='background:#3300c9; padding:25px; text-align:center; color:#ffffff;'>
    //                     <h2 style='margin:0;'>Welcome to {$settings->sitename} 🎉</h2>
    //                 </div>
    //                 <div style='padding:35px;'>
    //                     <p style='font-size:15px;'>Hello <strong>{$userInfo['profile']['name']}</strong>,</p>
    //                     <p style='font-size:15px; line-height:1.6; color:#444;'>
    //                         Congratulations! Your account has been successfully created on 
    //                         <strong>{$settings->sitename}</strong>. You're just one step away from joining the quiz arena.
    //                     </p>
    //                     <p style='font-size:15px; line-height:1.6; color:#444;'>
    //                         Your account is currently <strong>inactive</strong>. Use the One-Time Password (OTP) below to activate your account after your first login.
    //                     </p>
    //                     <div style='text-align:center; margin:35px 0;'>
    //                         <div style='display:inline-block;
    //                             font-size:32px;
    //                             letter-spacing:8px;
    //                             padding:18px 28px;
    //                             background:#ff3b30;
    //                             color:#ffffff;
    //                             border-radius:10px;
    //                             font-weight:bold;'>
    //                             {$userInfo['logger']['activation_token']}
    //                         </div>
    //                         <p style='margin-top:12px; font-size:13px; color:#666;'>
    //                             Your Activation Code
    //                         </p>
    //                     </div>
    //                     <p style='font-size:14px; color:#666; line-height:1.6;'>
    //                         If you did not create this account, please ignore this email.
    //                     </p>
    //                 </div>
    //                 <div style='background:#f4f6fb; padding:18px; text-align:center; font-size:12px; color:#777;'>
    //                     © " . date('Y') . " {$settings->sitename}
    //                 </div>
    //             </div>
    //         </div>";
    //     $notification = new \Notification();
    //     $to = [$userInfo['profile']['name']=>$userInfo['logger']['email']];
    //     $from = [$settings->sitename=>"{$settings->emails[0]}@{$settings->domain}"];
    //     $notification->sendMail(['to'=>$to, 'from'=>$from], 'Account Creation', $body);
    // }

    /**
     * for retrieving an admin information
     *
     * @param bool removePassword if true password will be removed from the user information
     * @return array info of the user ['logger'=>$l, 'profile'=>$p, 'admin'=>$a]
     */
    public function getInfo(bool $removePassword = true):array
    {
        if ($removePassword) {
            unset($this->info[\LoggerMgr::TABLE]['password']);
        }

        return $this->info;
    }

    /**
     * for retrieving several admin information
     *
     * @param integer $count the number of admins info to retrieve
     * @return array
     */
    public static function getAllInfo(int $count = \Functions::INFINITE):array
    {
        $profileTbl = \ProfileMgr::TABLE;
        $adminTbl   = Admin::TABLE;
        $loggerTbl  = \LoggerMgr::TABLE;
        $sql = "
            SELECT 
                -- Profile fields
                p.id AS {$profileTbl}_id,
                p.status AS {$profileTbl}_status,
                p.profile_no,
                p.logger AS profile_logger_id,
                p.profile_type,
                p.name AS {$profileTbl}_name,
                p.picture AS {$profileTbl}_picture,
                p.emails AS {$profileTbl}_emails,
                p.phones AS {$profileTbl}_phones,
                p.gender AS {$profileTbl}_gender,
                p.birthday AS {$profileTbl}_birthday,
                p.address AS {$profileTbl}_address,
                p.website AS {$profileTbl}_website,
                p.note AS profile_note,
                p.created_at AS {$profileTbl}_created_at,
                p.updated_at AS {$profileTbl}_updated_at,

                -- Admin fields
                a.id AS {$adminTbl}_id,
                a.profile AS {$adminTbl}_profile_id,
                a.type AS {$adminTbl}_type,
                a.created_date AS {$adminTbl}_created_date,
                a.updated_date AS {$adminTbl}_updated_date,

                -- Logger fields
                l.id AS {$loggerTbl}_id,
                l.email AS {$loggerTbl}_email,
                l.phone AS {$loggerTbl}_phone,
                l.username AS {$loggerTbl}_username,
                l.status AS {$loggerTbl}_status,
                l.password AS {$loggerTbl}_password,
                l.reset_token AS {$loggerTbl}_reset_token,
                l.reset_time AS {$loggerTbl}_reset_time,
                l.activation_token AS {$loggerTbl}_activation_token,
                l.activation_time AS {$loggerTbl}_activation_time,
                l.created_at AS {$loggerTbl}_created_at,
                l.updated_at AS {$loggerTbl}_updated_at

            FROM {$profileTbl} p
            RIGHT JOIN {$adminTbl} a ON p.id = a.profile
            JOIN {$loggerTbl} l ON p.logger = l.id
            LIMIT $count
        ";

        $query = new \Query();
        $result = $query->executeSql($sql)['rows'];
        if ($result) {
            foreach ($result as $row) {
                $info[$row['profile_id']] = [
                    $profileTbl => [
                        'id'          => $row['profile_id'],
                        'status'      => $row['profile_status'],
                        'profile_no'  => $row['profile_no'],
                        'logger'   => $row['profile_logger_id'],
                        'profile_type'=> $row['profile_type'],
                        'name'        => $row['profile_name'],
                        'picture'     => $row['profile_picture'],
                        'emails'      => $row['profile_emails'],
                        'phones'      => $row['profile_phones'],
                        'gender'      => $row['profile_gender'],
                        'birthday'    => $row['profile_birthday'],
                        'address'     => $row['profile_address'],
                        'website'     => $row['profile_website'],
                        'note'        => $row['profile_note'],
                        'created_at'  => $row['profile_created_at'],
                        'updated_at'  => $row['profile_updated_at'],
                    ],
                    $adminTbl => [
                        'id'            => $row['admin_id'],
                        'profile'    => $row['admin_profile_id'],
                        'type'          => $row['admin_type'],
                        'created_date'  => $row['admin_created_date'],
                        'updated_date'  => $row['admin_updated_date'],
                    ],
                    $loggerTbl => [
                        'id'               => $row['logger_id'],
                        'email'            => $row['logger_email'],
                        'phone'            => $row['logger_phone'],
                        'username'         => $row['logger_username'],
                        'status'           => $row['logger_status'],
                        'password'         => $row['logger_password'],
                        'reset_token'      => $row['logger_reset_token'],
                        'reset_time'       => $row['logger_reset_time'],
                        'activation_token' => $row['logger_activation_token'],
                        'activation_time'  => $row['logger_activation_time'],
                        'created_at'       => $row['logger_created_at'],
                        'updated_at'       => $row['logger_updated_at'],
                    ]
                ];        
            }
        }

        return $info;
    }

}
