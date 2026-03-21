<?php
require_once dirname(__FILE__, 4) . DIRECTORY_SEPARATOR . "config.php";

// check if id is in the url e.g. /players/1
if (array_key_exists("id",$_GET)) {
    $id = trim($_GET['id']);
    try{
        $player = new Player($id);
        $playerInfo = $player->getInfo();
    } catch (UserException $e) {
        Response::sendBadResponse(Response::BAD_REQUEST, [$e->getMessage()]);
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){
        $accessToken = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : "";
        $accessToken = Session::trimBearer($accessToken);
        if(!Session::isAnAccessTokenValid($accessToken)) {
            Response::sendBadResponse(Response::UNAUTHORIZED, ["Invalid access token provided"]);
        }
        
        try{
            if($id != Session::profileIdFromAccessToken($accessToken)){
                Response::sendBadResponse(Response::UNAUTHORIZED, ["player/access token mismatch"]);
            }
            Response::sendGoodResponse(["Player info"], $playerInfo, true, Response::OK);
            
        } catch (SessionExpection $e) {
            Response::sendBadResponse(Response::BAD_REQUEST, [$e->getMessage()]);
        }
    }
    // if request is a PATCH, e.g. update player info
    //TODO
    elseif($_SERVER['REQUEST_METHOD'] === 'PATCH') {    
        if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
            Response::sendBadResponse(Response::BAD_REQUEST, ["Content Type header not set to JSON"]);
        }

        $rawPatchdata = file_get_contents('php://input');

        if(!$jsonData = json_decode($rawPatchdata)) {    
            Response::sendBadResponse(Response::BAD_REQUEST, ["Request body is not valid JSON"]);
        }

        if(isset($jsonData->activation_token))  {
            $activationToken = $jsonData->activation_token;
            try{
                $player->activate($activationToken);
                Response::sendGoodResponse(["Player activate successfully"], $player->getInfo(), true, Response::OK);
            }
            catch (UserException $e) {
                Response::sendBadResponse(Response::BAD_REQUEST, [$e->getMessage()]);
            }            
        }
        else{
            $accessToken = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : "";
            $accessToken = Session::trimBearer($accessToken);
            if(!Session::isAnAccessTokenValid($accessToken)) {
                Response::sendBadResponse(Response::UNAUTHORIZED, ["Invalid access token provided"]);
            }

            $newData = [];
            isset($jsonData->name) && !empty($jsonData->name) ? $newData['name'] = $jsonData->name : null;
            isset($jsonData->picture) && !empty($jsonData->picture) ? $newData['picture'] =$jsonData->picture : null;
            isset($jsonData->mode) && !empty($jsonData->mode) ? $newData['mode'] = $jsonData->mode : null;
            isset($jsonData->twofactor) && !empty($jsonData->twofactor) ? $newData['twofactor']=  $jsonData->twofactor : null;
               
            try{                
                $player->update($newData);
            }
            catch(UserException $e){
                Response::sendBadResponse(Response::BAD_REQUEST, [$e->getMessage()]);
            }

            Response::sendGoodResponse(["Player info updated successfully"], $player->getInfo(), code: Response::OK);  
        }                      
    }
    // error when not DELETE or PATCH
    else {
        Response::sendBadResponse(Response::METHOD_NOT_ALLOWED, ["Request method not allowed 1"]);
    } 
}
// handle creating new player
elseif(empty($_GET)) {
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
        $name = isset($jsonData->name) ? $jsonData->name : "Firstname Surname";

        $player = Player::create($name, ['player'], $password, $identifierValue, $identifierType);
        $playerInfo = $player;
        Response::sendGoodResponse(["Player created successfully"], $playerInfo, code: Response::CREATED);
    }
    catch(UserException $ex) {
        Response::sendBadResponse(Response::UNAUTHORIZED, [$ex->getMessage()]);
    }
}
// return 404 error if endpoint not available
else {
    Response::sendBadResponse(Response::NOT_FOUND, ["Endpoint not found"]);
}