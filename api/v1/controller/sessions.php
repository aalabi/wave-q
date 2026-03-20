<?php
require_once dirname(__FILE__, 4) . DIRECTORY_SEPARATOR . "config.php";

// check if id is in the url e.g. /sessions/1
if (array_key_exists("id",$_GET)) {
    $id = trim($_GET['id']);

    try{
        $session = new Session($id, true);
        $sessionInfo = $session->getInfo();
    } catch (SessionExpection $e) {
        Response::sendBadResponse(Response::BAD_REQUEST, [$e->getMessage()]);
    }

    $accessToken = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : "";    
    $accessToken = Session::trimBearer($accessToken);
    if(!$session->isItMyAccessToken($accessToken)) {
        Response::sendBadResponse(Response::UNAUTHORIZED, ["Invalid access token"]);
    }

    // if request is a DELETE, e.g. delete session
    if($_SERVER['REQUEST_METHOD'] === 'DELETE') {        
        try {
            $session->delete($accessToken);
            Response::sendGoodResponse(["session logged out"], ['session_id' => intval($id)], code:Response::OK);      
        }
        catch(SessionExpection $ex) {
            Response::sendBadResponse(Response::INTERNAL_SERVER_ERROR, [$ex->getMessage()]);
        }
    }

    // if request is a PATCH, e.g. renew access token 
    elseif($_SERVER['REQUEST_METHOD'] === 'PATCH') {        
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
            Response::sendBadResponse(Response::UNAUTHORIZED, ["Invalid access token or refresh token"]);
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
        $data['access_token'] = $session->getAccessToken();
        $data['refresh_token'] = $session->getRefreshToken();
        if(isset($sessionInfo['session'])) {
            $data['session_id'] = $sessionInfo['session']['id'];
            $data['access_token_expiry'] = $sessionInfo['session']['access_token_expiry'];
            $data['refresh_token_expiry'] = $sessionInfo['session']['refresh_token_expiry'];
        }
        else{
            $data['session_id'] = $sessionInfo['id'];
            $data['access_token_expiry'] = $sessionInfo['access_token_expiry'];
            $data['refresh_token_expiry'] = $sessionInfo['refresh_token_expiry'];
        }
        Response::sendGoodResponse(["Access token refreshed successfully"], $data, code: Response::OK);                        
    }
    // error when not DELETE or PATCH
    else {
        Response::sendBadResponse(Response::METHOD_NOT_ALLOWED, ["Request method not allowed"]);
    } 
}
// handle creating new session, e.g. log in
elseif(empty($_GET)) {
    // handle creating new session, e.g. logging in
    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::sendBadResponse(Response::METHOD_NOT_ALLOWED, ["Request method not allowed"]);
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
        if(isset($sessionInfo['session'])) {
            $sessionInfo['session']['access_token'] = $session->getAccessToken();
            $sessionInfo['session']['refresh_token'] = $session->getRefreshToken();
        }
        else{
            $sessionInfo['access_token'] = $session->getAccessToken();
            $sessionInfo['refresh_token'] = $session->getRefreshToken();
        }

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