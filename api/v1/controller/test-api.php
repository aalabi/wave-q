<?php
require_once dirname(__FILE__, 4) . DIRECTORY_SEPARATOR . "config.php";

$settings =  new \Settings(SETTING_FILE, true);
try {
    $dbConnect = \DbConnect::getInstance(SETTING_FILE);
} catch (\DbConnectExpection $e) {
    Response::sendBadResponse(Response::INTERNAL_SERVER_ERROR, [$e->getMessage()]);
}
$query = new \Query();