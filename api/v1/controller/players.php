<?php
require_once dirname(__FILE__, 4) . DIRECTORY_SEPARATOR . "config.php";

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id !== null) {
    try {
        $player = new Player($id);
    } catch (UserException $e) {
        Response::sendBadResponse(Response::BAD_REQUEST, [$e->getMessage()]);
    }

    // GET /players/{id} get player details
    if ($method === 'GET' && !$action) {

        $token = Functions::requireAuth();
        if ($id != Session::profileIdFromAccessToken($token)) {
            Response::sendBadResponse(Response::UNAUTHORIZED, ["player/access token mismatch"]);
        }

        Response::sendGoodResponse(["Player info"], $player->getInfo(), true, Response::OK);
    }
    // PATCH /players/{id} update player details
    elseif ($method === 'PATCH' && !$action) {
        $token = Functions::requireAuth();
        $jsonData = Functions::getJsonInput();

        $newData = [];
        isset($jsonData->name) && $newData['name'] = $jsonData->name;
        isset($jsonData->picture) && $newData['picture'] = $jsonData->picture;
        isset($jsonData->mode) && $newData['mode'] = $jsonData->mode;
        isset($jsonData->twofactor) && $newData['twofactor'] = $jsonData->twofactor;

        try {
            $player->update($newData);
        } catch (UserException $e) {
            Response::sendBadResponse(Response::BAD_REQUEST, [$e->getMessage()]);
        }

        Response::sendGoodResponse(["Player updated"], $player->getInfo(), true, Response::OK);
    }
    // POST /players/{id}/activate activate player
    elseif ($method === 'POST' && $action === 'activate') {
        $jsonData = Functions::getJsonInput();

        if (!isset($jsonData->activation_token)) {
            Response::sendBadResponse(Response::BAD_REQUEST, ["Activation token required"]);
        }

        try {
            $player->activate($jsonData->activation_token);
        } catch (UserException $e) {
            Response::sendBadResponse(Response::BAD_REQUEST, [$e->getMessage()]);
        }

        Response::sendGoodResponse(["Player activated"], $player->getInfo(), code:Response::OK);
    }
    // POST /players/{id}/resend-activation-token resend player activation token
    elseif ($method === 'POST' && $action === 'resend-activation-token') {   
        try {
            $player->resendActivationToken();
        } catch (UserException $e) {
            Response::sendBadResponse(Response::BAD_REQUEST, [$e->getMessage()]);
        }    
            
        Response::sendGoodResponse(["Activation token resent"], code: Response::OK);
    }
    // POST /players/{id}/change-password used for changing password
    elseif ($method === 'POST' && $action === 'change-password') {  
        $token = Functions::requireAuth();
        if ($id != Session::profileIdFromAccessToken($token)) {
            Response::sendBadResponse(Response::UNAUTHORIZED, ["player/access token mismatch"]);
        }        
        $jsonData = Functions::getJsonInput();

        if (!isset($jsonData->oldPassword, $jsonData->newPassword)) {
            Response::sendBadResponse(Response::BAD_REQUEST, ["Missing required fields"]);
        }

        try {
            $player->changePlayerPassword($jsonData->oldPassword, $jsonData->newPassword);
        } catch (UserException $e) {
            Response::sendBadResponse(Response::BAD_REQUEST, [$e->getMessage()]);
        }
            
        Response::sendGoodResponse(["Password changed successfully"], code: Response::OK);
    }
    // POST /players/{id}/change-username
    elseif ($method === 'POST' && $action === 'change-username') {

        $token = Functions::requireAuth();
        if ($id != Session::profileIdFromAccessToken($token)) {
            Response::sendBadResponse(Response::UNAUTHORIZED, ["player/access token mismatch"]);
        }

        $jsonData = Functions::getJsonInput();
        if (!isset($jsonData->username) || empty($jsonData->username)) {
            Response::sendBadResponse(Response::BAD_REQUEST, ["Username is required"]);
        }

        try {
            $player->changeUsername($jsonData->username);
        } catch (UserException $e) {
            Response::sendBadResponse(Response::BAD_REQUEST, [$e->getMessage()]);
        }

        Response::sendGoodResponse(["Username updated successfully"], code: Response::OK);
    }
    else {
        Response::sendBadResponse(Response::METHOD_NOT_ALLOWED, ["Invalid request"]);
    }
}
else {
    // POST /players
    if ($method === 'POST' && !$action) {
        $jsonData = Functions::getJsonInput();

        if (!isset($jsonData->name, $jsonData->email, $jsonData->phone, $jsonData->password, $jsonData->username)) {
            Response::sendBadResponse(Response::BAD_REQUEST, ["Missing required fields"]);
        }

        try {
            $player = Player::create($jsonData->name, ['player'], $jsonData->password, $jsonData->email, phone:$jsonData->phone, username:$jsonData->username);

            Response::sendGoodResponse(["Player created"], $player, true, Response::CREATED);

        } catch (UserException $e) {
            Response::sendBadResponse(Response::BAD_REQUEST, [$e->getMessage()]);
        }
    }
    // POST /players/forgot-password used for forgot password
    elseif ($method === 'POST' && $action === 'forgot-password') {
        $jsonData = Functions::getJsonInput();

        if (!isset($jsonData->email)) {
            Response::sendBadResponse(Response::BAD_REQUEST, ["Email required"]);
        }

        try {
            Player::requestPasswordReset($jsonData->email);
        } catch (UserException $e) {
            Response::sendBadResponse(Response::BAD_REQUEST, [$e->getMessage()]);
        }

        Response::sendGoodResponse(["If account exists, reset instructions sent"], code: Response::OK);
    }
    // POST /players/reset-password used for changing password during forgot password process
    elseif ($method === 'POST' && $action === 'reset-password') {

        $jsonData = Functions::getJsonInput();
        if (!isset($jsonData->email, $jsonData->token, $jsonData->newPassword)) {
            Response::sendBadResponse(Response::BAD_REQUEST, ["Missing required fields"]);
        }
        
        try {
            Player::apiResetPassword($jsonData->email, $jsonData->token, $jsonData->newPassword);
        } catch (UserException $e) {
            Response::sendBadResponse(Response::BAD_REQUEST, [$e->getMessage()]);
        }

        Response::sendGoodResponse(["Password reset successful"], code: Response::OK);
    }
    // GET /players (optional list)
    elseif ($method === 'GET') {
        Response::sendBadResponse(Response::NOT_FOUND, ["List players not implemented"]);
    }
    else {
        Response::sendBadResponse(Response::METHOD_NOT_ALLOWED, ["Invalid request"]);
    }
}