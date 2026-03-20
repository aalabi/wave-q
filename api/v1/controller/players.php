<?php
require_once dirname(__FILE__, 4) . DIRECTORY_SEPARATOR . "config.php";

// check if id is in the url e.g. /players/1
if (array_key_exists("id",$_GET)) {
    $id = trim($_GET['id']);

    $accessToken = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : "";
    $accessToken = Session::trimBearer($accessToken);
    if(!Session::isAnAccessTokenValid($accessToken)) {
        Response::sendBadResponse(Response::UNAUTHORIZED, ["Invalid access token provided"]);
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){
        try{
            $profileId = Session::profileIdFromAccessToken($accessToken);
            $player = new Player($id);
            $playerInfo = $player->getInfo();
    
            Response::sendGoodResponse(["Player info"], $playerInfo, true, Response::OK);
        } catch (SessionExpection | UserException $e) {
            Response::sendBadResponse(Response::BAD_REQUEST, [$e->getMessage()]);
        }
    }
    // if request is a PATCH, e.g. update player info
    elseif($_SERVER['REQUEST_METHOD'] === 'PATCH') {    
        // check request's content type header is JSON
        if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
            Response::sendBadResponse(Response::BAD_REQUEST, ["Content Type header not set to JSON"]);
        }

        // get PATCH request body as the PATCHed data will be JSON format
        $rawPatchdata = file_get_contents('php://input');

        if(!$jsonData = json_decode($rawPatchdata)) {    
            Response::sendBadResponse(Response::BAD_REQUEST, ["Request body is not valid JSON"]);
        }

        // check if patch request contains refresh token
        if(!isset($jsonData->refresh_token) || empty(trim($jsonData->refresh_token)))  {
            $msg = [];
            !isset($jsonData->refresh_token) ? $msg[] = "Refresh Token not supplied" : false;
            empty(trim($jsonData->refresh_token)) ? $msg[] = "Refresh Token cannot be blank" : false;
            Response::sendBadResponse(Response::BAD_REQUEST, ["Request body is not valid JSON"]);
        }
              
        $refreshToken = $jsonData->refresh_token;
        if(!$session->isValidRefreshToken($accessToken, $refreshToken)) {
            Response::sendBadResponse(Response::UNAUTHORIZED, ["Invalid access token or refresh token provided"]);
        }
    
        if($sessionInfo['logger']['status'] !== LoggerMgr::STATUS_VALUES['active']) {
            Response::sendBadResponse(Response::UNAUTHORIZED, ["inactive account"]); 
        }

        if(strtotime($sessionInfo['session']['refresh_token_expiry']) < time()) {
            Response::sendBadResponse(Response::UNAUTHORIZED, ["Refresh token has expired - please log in again"]);
        }

        try {                
            $session->renewTokens();
        }
        catch(SessionExpection $ex) {
            Response::sendBadResponse(Response::INTERNAL_SERVER_ERROR, [$ex->getMessage()]);
        }

        $data = [];
        if(isset($sessionInfo['session'])) {
            $data['session_id'] = $sessionInfo['session']['id'];
            $data['access_token'] = $sessionInfo['session']['access_token'];
            $data['access_token_expiry'] = $sessionInfo['session']['access_token_expiry'];
            $data['refresh_token'] = $sessionInfo['session']['refresh_token'];
            $data['refresh_token_expiry'] = $sessionInfo['session']['refresh_token_expiry'];            
        }
        else{
            $data['session_id'] = $returned_id;
            $data['access_token'] = $accessToken;
            $data['access_token_expiry'] = $access_token_expiry_seconds;
            $data['refresh_token'] = $refreshToken;
            $data['refresh_token_expiry'] = $refresh_token_expiry_seconds;
        }
        Response::sendGoodResponse(["Access token refreshed successfully"], $data, code: Response::OK);                        
    }
    // error when not DELETE or PATCH
    else {
        Response::sendBadResponse(Response::METHOD_NOT_ALLOWED, ["Request method not allowed 1"]);
    } 
}
// handle creating new session, e.g. log in
elseif(empty($_GET)) {
    // handle creating new session, e.g. logging in
    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::sendBadResponse(Response::METHOD_NOT_ALLOWED, ["Request method not allowed 2"]);
    }
  
    // delay login by 1 second to slow down any potential brute force attacks
    sleep(1);
  
    if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
        Response::sendBadResponse(Response::BAD_REQUEST, ["Content Type header not set to JSON"]);
    }
  
    $rawPostData = file_get_contents('php://input');
    if(!$jsonData = json_decode($rawPostData)) {
        Response::sendBadResponse(Response::BAD_REQUEST, ["Request body is not valid JSON"]);
    }

    if(!isset($jsonData->identifierType) || !isset($jsonData->identifierValue) || !isset($jsonData->password)) {
        $messages = [];
        (!isset($jsonData->identifierType) ? $messages[] = "Identifier Type not supplied" : false);
        (!isset($jsonData->identifierValue) ? $messages[] = "Identifier Value not supplied" : false);
        (!isset($jsonData->password) ? $messages[] = "Password not supplied" : false);
        Response::sendBadResponse(Response::BAD_REQUEST, $messages);
    }

    try {    
        $identifierType = $jsonData->identifierType;
        $identifierValue = $jsonData->identifierValue;
        $password = $jsonData->password;

        $session = Session::create($password, $identifierValue, $identifierType);
        $sessionInfo = $session->getInfo();
        Response::sendGoodResponse(["Session created successfully"], $sessionInfo, code: Response::CREATED);
    }
    catch(SessionExpection $ex) {
        Response::sendBadResponse(Response::UNAUTHORIZED, [$ex->getMessage()]);
    }
}
// return 404 error if endpoint not available
else {
    Response::sendBadResponse(Response::NOT_FOUND, ["Endpoint not found"]);
}