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
        string $identityType = \Authentication::ALL_IDENTITY_TYPE['email']
    ):array {
        $userInfo =  parent::create($name, ['type' => 'player', 'subType' => $subProfileType[0]], $password, $identity, $identityType);
        $profileNo = "PLA".str_pad($userInfo['profile']['id'], 4, "0", STR_PAD_LEFT);
        (new \Table(\ProfileMgr::TABLE))->updateOne($userInfo['profile']['id'], ['profile_no' => [$profileNo, 'isValue']]);
        parent::sendLoggerCreationMail($userInfo);
        return $userInfo;
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
    

}
