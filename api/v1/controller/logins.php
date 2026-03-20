<?php
require_once dirname(__FILE__, 4) . DIRECTORY_SEPARATOR . "config.php";

exit;
$limitPerPage = 20;

// check if profileId is in the url e.g. /profileId/1
if (array_key_exists("profileId", $_GET)) {
    //validate profile id
    if (!($profileId = filter_var($_GET["profileId"], FILTER_VALIDATE_INT))) {
        Functions::badResponse(Response::BAD_REQUEST, ["invalid profile id"]);
        exit;
    } else {
        $Db = new Database(__FILE__, $PDO, Profile::TABLE);
        if (!$Db->isDataInColumn(__LINE__, $profileId, Profile::ID)) {
            Functions::badResponse(Response::BAD_REQUEST, ["invalid user profile id"]);
            exit;
        }
    }

    // request is a GET e.g get login
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        Functions::endPointLock($PDO);

        $Login = new Login($PDO);
        if ($loginInfo = $Login->getInfo($profileId)) {
            $returnData = [];
            $returnData['rows_returned'] = 1;
            $returnData['users'] = [$loginInfo];

            Functions::goodResponse(Response::OK, [], $returnData, true);
            exit;
        } else {
            Functions::badResponse(Response::NOT_FOUND, ["user not found"]);
            exit;
        }
    }

    // request is a DELETE e.g delete profile
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $PROFILE_ID = Functions::endPointLock($PDO);
        Functions::accessControl($PDO, $PROFILE_ID, [Profile::PROFILE_TYPE[0]]);

        $Profile = new Profile($PDO);
        $loginId = $Profile->getInfo($profileId)[Profile::LOGIN_ID];
        $Db = new Database(__FILE__, $PDO, Login::TABLE);
        $where = [['column' => Login::ID, 'comparsion' => '=', 'bindAbleValue' => $loginId]];
        if ($Db->delete(__LINE__, $where)) {
            Functions::goodResponse(Response::OK, ["profile deleted"], [], true);
            exit;
        } else {
            Functions::badResponse(Response::NOT_FOUND, ["profile not found"]);
            exit;
        }
    }

    // request is a PATCH e.g update profile
    elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        // update profile
        try {
            if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'application/json') {
                Functions::badResponse(Response::BAD_REQUEST, ["content type header not set to JSON"]);
                exit;
            }

            $rawPatchData = file_get_contents('php://input');
            if (!$jsonData = json_decode($rawPatchData)) {
                Functions::badResponse(Response::BAD_REQUEST, ["request body is not valid JSON"]);
                exit;
            }

            $errors = [];
            if (!isset($jsonData->newPassword)) {
                $errors[] = "missing new password error";
            } else {
                if (strlen($jsonData->newPassword) < 8 ||  !preg_match('/^[a-zA-Z0-9]+$/', $jsonData->newPassword)) {
                    $errors[] = "invalid new password error";
                }
            }

            if (!isset($jsonData->oldPassword)) {
                $errors[] = "missing old password error";
            } else {
                $oldPassword = $jsonData->oldPassword;
                $Db = new Database(__FILE__, $PDO, Login::TABLE);
                $sql = "SELECT " . Login::PASSWORD . ", " . Login::TABLE . "." . Login::ID . " FROM " . Login::TABLE;
                $sql .= " INNER JOIN " . Profile::TABLE . " ON " . Login::TABLE . "." . Login::ID . " = " . Profile::TABLE;
                $sql .= "." . Profile::LOGIN_ID . " WHERE " . Profile::TABLE . "." . Profile::ID . " = :profileId";
                $bind = ["profileId" => $profileId];
                $result = $Db->queryStatment(__LINE__, $sql, $bind);
                if ($result['data']) {
                    if (!password_verify($oldPassword, $result['data'][0][Login::PASSWORD])) {
                        $errors[] = "invalid old password error";
                    }
                }
            }

            if ($errors) {
                Functions::badResponse(Response::BAD_REQUEST, $errors);
                exit;
            }

            $loginId = $result['data'][0][Login::ID];
            $Login = new Login($PDO);
            $column[LOGIN::PASSWORD] = [
                'colValue' => $Login->getPassword($Login->setPassword($jsonData->newPassword)),
                'isFunction' => false, 'isBindAble' => true
            ];

            $Db = new Database(__FILE__, $PDO, Login::TABLE);
            $where = [['column' => Login::ID, 'comparsion' => '=', 'bindAbleValue' => $loginId]];

            if ($Db->update(__LINE__, $column, $where)) {
                $loginInfo = $Login->getInfo($loginId, true);
                $returnData = [];
                $returnData['rows_returned'] = 1;
                $returnData['login'] = $loginInfo;
                Functions::goodResponse(Response::OK, ["password successfully updated"], $returnData);
                exit;
            } else {
                Functions::badResponse(Response::NOT_FOUND, ["no password update"]);
                exit;
            }
        } catch (LoginException $ex) {
            Functions::badResponse(Response::BAD_REQUEST, [$ex->getMessage()]);
            exit;
        }
    }

    // request is not GET, PATCH or DELETE
    else {
        Functions::badResponse(Response::METHOD_NOT_ALLOWED, ["request method not allowed"]);
        exit;
    }
}
// getting reset or activation token
elseif (array_key_exists("username", $_GET) && !array_key_exists("resetCode", $_GET)) {
    // create user
    if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        //validate username which will be either an email or phone
        if (!($username = htmlspecialchars($_GET["username"]))) {
            Functions::badResponse(Response::BAD_REQUEST, ["invalid username error"]);
            exit;
        } else {
            $errors = [];
            $Db = new Database(__FILE__, $PDO, Login::TABLE);
            if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                if (!$Db->isDataInColumn(__LINE__, $username, Login::EMAIL)) {
                    $errors[] = "invalid email error";
                }
                $isEmail = true;
            } else {
                if (!$Db->isDataInColumn(__LINE__, $username, Login::PHONE)) {
                    $errors[] = "invalid phone error";
                }
                $isEmail = false;
            }
            if ($errors) {
                Functions::badResponse(Response::BAD_REQUEST, $errors);
                exit;
            }
        }

        try {
            if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'application/json') {
                Functions::badResponse(Response::BAD_REQUEST, ["content type header not set to JSON"]);
                exit;
            }

            $rawPostData = file_get_contents('php://input');
            if (!$jsonData = json_decode($rawPostData)) {
                Functions::badResponse(Response::BAD_REQUEST, ["request body is not valid JSON"]);
                exit;
            }

            if (!isset($jsonData->resetCode) && !isset($jsonData->activationCode)) {
                Functions::badResponse(Response::BAD_REQUEST, ["Missing reset code and activation code error"]);
                exit;
            }

            $Db = new Database(__FILE__, $PDO, Login::TABLE);
            $Login = new Login($PDO);
            if (isset($jsonData->resetCode)) {
                $resetCode = $Login->getResetToken($Login->setResetToken());
                $resetTime = $Login->getResetTime($Login->setResetTime())->format('Y-m-d H:i:s');
                $column[Login::RESET_TOKEN] = ['colValue' => $resetCode, 'isFunction' => false, 'isBindAble' => true];
                $column[Login::RESET_TIME] = ['colValue' => $resetTime, 'isFunction' => false, 'isBindAble' => true];

                $subject = "Password Reset Token";
                $content = "
                    <p style='margin-bottom:20px;'>Good Day Sir/Madam </p>
                    <p style='margin-bottom:8px;'>
                        You recently requested to reset the password for your account. Resetting your password is easy.
                        Just use your password reset code!.<br/>
                        <strong>Code:</strong> $resetCode<br/>
                        <strong>Expiration Time:</strong> $resetTime<br/>
                    </p>
                    <p style='margin-bottom:8px;'>
                        If you did not make this request then please ignore this email.
                    </p>
                ";
            }
            if (isset($jsonData->activationCode)) {
                $activationCode = $Login->getResetToken($Login->setResetToken());
                $activationTime = $Login->getActivationTime($Login->setActivationTime())->format('Y-m-d H:i:s');
                $column[Login::ACTIVATION_TOKEN] = ['colValue' => $activationCode, 'isFunction' => false, 'isBindAble' => true];
                $column[Login::ACTIVATION_TIME] = ['colValue' => $activationTime, 'isFunction' => false, 'isBindAble' => true];
                $subject = "Account Activation Token";
                $content = "
                    <p style='margin-bottom:20px;'>Good Day Sir/Madam </p>
                    <p style='margin-bottom:8px;'>
                        Activation mail content will be here soon
                    </p>
                    <p style='margin-bottom:8px;'>
                        If you did not make this request then please ignore this email.
                    </p>
                ";
            }
            if ($isEmail) {
                $where[] = ['column' => Login::EMAIL, 'comparsion' => '=', 'bindAbleValue' => $username];
            } else {
                $where[] = ['column' => Login::PHONE, 'comparsion' => '=', 'bindAbleValue' => $username];
            }
            $Db->update(__LINE__, $column, $where);

            //get the user login table record
            $loginInfo = $Db->select(__LINE__, [], $where);
            unset($loginInfo[0][Login::PASSWORD]);
            unset($loginInfo[0][Login::RESET_TOKEN]);

            //send mail to user
            $Notification = new Notification();
            $Notification->sendMail(['to' => [$loginInfo[0][Login::EMAIL]], 'from' => ['info@' . URLEMAIL]], $subject, $content);

            $returnData = [];
            $returnData['rows_returned'] = 1;
            $returnData['Login'] =  $loginInfo[0];

            Functions::goodResponse(Response::OK, [], $returnData);
            exit;
        } catch (LoginException $ex) {
            Functions::badResponse(Response::BAD_REQUEST, [$ex->getMessage()]);
            exit;
        }
    }
    //request method apart from PATCH 405
    else {
        Functions::badResponse(Response::METHOD_NOT_ALLOWED, ["request method not allowed"]);
        exit;
    }
}
// change password from reset code and username
elseif (array_key_exists("username", $_GET) && array_key_exists("resetCode", $_GET)) {
    // create user
    if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        $errors = [];

        //validate username which will be either an email or phone
        if (!($username = htmlspecialchars($_GET["username"]))) {
            $errors[] = "blank username error";
        } else {
            $Db = new Database(__FILE__, $PDO, Login::TABLE);
            if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                if (!$Db->isDataInColumn(__LINE__, $username, Login::EMAIL)) {
                    $errors[] = "invalid email error";
                }
                $isEmail = true;
            } else {
                if (!$Db->isDataInColumn(__LINE__, $username, Login::PHONE)) {
                    $errors[] = "invalid phone error";
                }
                $isEmail = false;
            }
        }

        //validate reset code
        if (!($resetCode = htmlspecialchars($_GET["resetCode"]))) {
            $errors[] = "blank reset code error";
        } else {
            $errors = [];
            $Db = new Database(__FILE__, $PDO, Login::TABLE);
            $where = [['column' => Login::RESET_TOKEN, 'comparsion' => '=', 'bindAbleValue' => $resetCode, 'logic' => 'AND']];
            if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                $where[] = ['column' => Login::EMAIL, 'comparsion' => '=', 'bindAbleValue' => $username];
                if (!($result = $Db->select(__LINE__, [], $where))) {
                    $errors[] = "invalid reset code error";
                }
                $isEmail = true;
            } else {
                $where[] = ['column' => Login::PHONE, 'comparsion' => '=', 'bindAbleValue' => $username];
                if (!($result = $Db->select(__LINE__, [], $where))) {
                    $errors[] = "invalid reset code error";
                }
                $isEmail = false;
            }
            if ($result) {
                $result = $result[0];
                if (new DateTime() > new DateTime($result[Login::RESET_TIME])) {
                    $errors[] = "expired reset code error";
                }
            }
        }

        if ($errors) {
            Functions::badResponse(Response::BAD_REQUEST, $errors);
            exit;
        }

        try {
            if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'application/json') {
                Functions::badResponse(Response::BAD_REQUEST, ["content type header not set to JSON"]);
                exit;
            }

            $rawPostData = file_get_contents('php://input');
            if (!$jsonData = json_decode($rawPostData)) {
                Functions::badResponse(Response::BAD_REQUEST, ["request body is not valid JSON"]);
                exit;
            }

            $errors = [];
            if (!isset($jsonData->password)) {
                $errors[] = "missing password error";
            } else {
                if (strlen($jsonData->password) < 8 ||  !preg_match('/^[a-zA-Z0-9]+$/', $jsonData->password)) {
                    $errors[] = "invalid password error";
                }
            }

            if ($errors) {
                Functions::badResponse(Response::BAD_REQUEST, $errors);
                exit;
            }

            $password = password_hash($jsonData->password, PASSWORD_DEFAULT);

            $Db = new Database(__FILE__, $PDO, Login::TABLE);
            $Login = new Login($PDO);
            $column[Login::PASSWORD] = ['colValue' => $password, 'isFunction' => false, 'isBindAble' => true];
            $where = [];
            if ($isEmail) {
                $where[] = ['column' => Login::EMAIL, 'comparsion' => '=', 'bindAbleValue' => $username];
            } else {
                $where[] = ['column' => Login::PHONE, 'comparsion' => '=', 'bindAbleValue' => $username];
            }
            $Db->update(__LINE__, $column, $where);

            $loginInfo = $Db->select(__LINE__, [], $where);
            unset($loginInfo[0][Login::PASSWORD]);
            $returnData = [];
            $returnData['rows_returned'] = 1;
            $returnData['Login'] =  $loginInfo[0];

            Functions::goodResponse(Response::OK, ['password changed'], $returnData);
            exit;
        } catch (LoginException $ex) {
            Functions::badResponse(Response::BAD_REQUEST, [$ex->getMessage()]);
            exit;
        }
    }
    //request method apart from PATCH 405
    else {
        Functions::badResponse(Response::METHOD_NOT_ALLOWED, ["request method not allowed"]);
        exit;
    }
}
// change password from profile id
elseif (array_key_exists("profileId", $_GET)) {
    // create user
    if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        $errors = [];
        exit;
        //validate profileId which will be either an email or phone
        if (!($username = htmlspecialchars($_GET["username"]))) {
            $errors[] = "blank username error";
        } else {
            $Db = new Database(__FILE__, $PDO, Login::TABLE);
            if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                if (!$Db->isDataInColumn(__LINE__, $username, Login::EMAIL)) {
                    $errors[] = "invalid email error";
                }
                $isEmail = true;
            } else {
                if (!$Db->isDataInColumn(__LINE__, $username, Login::PHONE)) {
                    $errors[] = "invalid phone error";
                }
                $isEmail = false;
            }
        }

        //validate reset code
        if (!($resetCode = htmlspecialchars($_GET["resetCode"]))) {
            $errors[] = "blank reset code error";
        } else {
            $errors = [];
            $Db = new Database(__FILE__, $PDO, Login::TABLE);
            $where = [['column' => Login::RESET_TOKEN, 'comparsion' => '=', 'bindAbleValue' => $resetCode, 'logic' => 'AND']];
            if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                $where[] = ['column' => Login::EMAIL, 'comparsion' => '=', 'bindAbleValue' => $username];
                if (!($result = $Db->select(__LINE__, [], $where))) {
                    $errors[] = "invalid reset code error";
                }
                $isEmail = true;
            } else {
                $where[] = ['column' => Login::PHONE, 'comparsion' => '=', 'bindAbleValue' => $username];
                if (!($result = $Db->select(__LINE__, [], $where))) {
                    $errors[] = "invalid reset code error";
                }
                $isEmail = false;
            }
            if ($result) {
                $result = $result[0];
                if (new DateTime() > new DateTime($result[Login::RESET_TIME])) {
                    $errors[] = "expired reset code error";
                }
            }
        }

        if ($errors) {
            Functions::badResponse(Response::BAD_REQUEST, $errors);
            exit;
        }

        try {
            if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'application/json') {
                Functions::badResponse(Response::BAD_REQUEST, ["content type header not set to JSON"]);
                exit;
            }

            $rawPostData = file_get_contents('php://input');
            if (!$jsonData = json_decode($rawPostData)) {
                Functions::badResponse(Response::BAD_REQUEST, ["request body is not valid JSON"]);
                exit;
            }

            $errors = [];
            if (!isset($jsonData->password)) {
                $errors[] = "missing password error";
            } else {
                if (strlen($jsonData->password) < 8 ||  !preg_match('/^[a-zA-Z0-9]+$/', $jsonData->password)) {
                    $errors[] = "invalid password error";
                }
            }

            if ($errors) {
                Functions::badResponse(Response::BAD_REQUEST, $errors);
                exit;
            }

            $password = password_hash($jsonData->password, PASSWORD_DEFAULT);

            $Db = new Database(__FILE__, $PDO, Login::TABLE);
            $Login = new Login($PDO);
            $column[Login::PASSWORD] = ['colValue' => $password, 'isFunction' => false, 'isBindAble' => true];
            $where = [];
            if ($isEmail) {
                $where[] = ['column' => Login::EMAIL, 'comparsion' => '=', 'bindAbleValue' => $username];
            } else {
                $where[] = ['column' => Login::PHONE, 'comparsion' => '=', 'bindAbleValue' => $username];
            }
            $Db->update(__LINE__, $column, $where);

            $loginInfo = $Db->select(__LINE__, [], $where);
            unset($loginInfo[0][Login::PASSWORD]);
            $returnData = [];
            $returnData['rows_returned'] = 1;
            $returnData['Login'] =  $loginInfo[0];

            Functions::goodResponse(Response::OK, ['password changed'], $returnData);
            exit;
        } catch (LoginException $ex) {
            Functions::badResponse(Response::BAD_REQUEST, [$ex->getMessage()]);
            exit;
        }
    }
    //request method apart from PATCH 405
    else {
        Functions::badResponse(Response::METHOD_NOT_ALLOWED, ["request method not allowed"]);
        exit;
    }
}
// getting all login at $limitPerPage per page at a time
elseif (array_key_exists("page", $_GET)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $PROFILE_ID = Functions::endPointLock($PDO);
        Functions::accessControl($PDO, $PROFILE_ID, [Profile::PROFILE_TYPE[0]]);

        //validate page
        if (!($page = filter_var($_GET["page"], FILTER_VALIDATE_INT))) {
            Functions::badResponse(Response::BAD_REQUEST, ["invalid number per page value"]);
            exit;
        }

        try {
            $Db = new Database(__FILE__, $PDO, Login::TABLE);
            $sql = "SELECT count(" . Login::ID . ") as totalCount FROM " . Login::TABLE;
            $loginCount = $Db->queryStatment(__LINE__, $sql)['data'][0]['totalCount'];

            $numOfPages = ceil($loginCount / $limitPerPage);
            if ($numOfPages == 0) {
                $numOfPages = 1;
            }

            if ($page > $numOfPages || $page == 0) {
                Functions::badResponse(Response::NOT_FOUND, ["page not found"]);
                exit;
            }

            $offset = ($page == 1 ?  0 : ($limitPerPage * ($page - 1)));
            $sql = "SELECT * FROM " . Login::TABLE . " LIMIT $limitPerPage OFFSET $offset";
            $result = $Db->queryStatment(__LINE__, $sql);
            $rowCount = $result['rowCount'];
            $Login = new Login($PDO);
            $loginArray = [];
            foreach ($result['data'] as $aResult) {
                $loginInfo = $Login->getInfo($aResult[Login::ID], true);
                $loginArray[] = $loginInfo;
            }

            $returnData = [];
            $returnData['rows_returned'] = $rowCount;
            $returnData['total_rows'] = $loginCount;
            $returnData['total_pages'] = $numOfPages;
            ($page < $numOfPages ? $returnData['has_next_page'] = true : $returnData['has_next_page'] = false);
            ($page > 1 ? $returnData['has_previous_page'] = true : $returnData['has_previous_page'] = false);
            $returnData['logins'] = $loginArray;

            Functions::goodResponse(Response::OK, [], $returnData, true);
            exit;
        } catch (LoginException $ex) {
            Functions::badResponse(Response::INTERNAL_SERVER_ERROR, [$ex->getMessage()]);
            exit;
        }
    } else {
        Functions::badResponse(Response::METHOD_NOT_ALLOWED, ["request method not allowed"]);
        exit;
    }
}
// getting all profiles or login
elseif (empty($_GET)) {
    // if request is a GET get all login
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $PROFILE_ID = Functions::endPointLock($PDO);
        Functions::accessControl($PDO, $PROFILE_ID, [Profile::PROFILE_TYPE[0]]);
        try {
            $Db = new Database(__FILE__, $PDO, Login::TABLE);
            $result = $Db->select(__LINE__);
            $rowCount = count($result);

            // $adminArray = [];
            // foreach ($result as $aResult) {
            //     $profileInfo = (new Admin($PDO))->getInfo($aResult[Profile::ID]);
            //     $adminArray[] = [$profileInfo];
            // }

            $returnData = [];
            $returnData['rows_returned'] = $rowCount;
            $returnData['logins'] = $result;

            Functions::goodResponse(Response::OK, [], $returnData, true);
            exit;
        } catch (LoginException $ex) {
            Functions::badResponse(Response::INTERNAL_SERVER_ERROR, [$ex->getMessage()]);
            exit;
        }
    }


    //request method apart from GET or POST then return 405
    else {
        Functions::badResponse(Response::METHOD_NOT_ALLOWED, ["request method not allowed"]);
        exit;
    }
}
// requested endpoint is not found
else {
    Functions::badResponse(Response::NOT_FOUND, ["endpoint not found"]);
    exit;
}