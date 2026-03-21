<?php

require_once('config.php');

$settings =  new \Settings(SETTING_FILE, true);
try {
    $dbConnect = \DbConnect::getInstance(SETTING_FILE);
} catch (\DbConnectExpection $e) {
    echo $e->getMessage();
    \Response::sendBadResponse(\Response::INTERNAL_SERVER_ERROR, [$e->getMessage()]);
}

/* $player = new Player(4);
var_dump($player->getInfo());
$player->update(['name'=>'Tunde Mayana', 'picture'=>'1', 'mode'=>'dark']);
var_dump($player->getInfo()); */
//$player->activate("787770");
//var_dump($player->getInfo());

//$query = new \Query();
//$appSettings = new \AppSettings('winner');

// var_dump($appSettings->getInfo());

//echo $settings->getDetails()->database->name;
//var_dump(\Admin::create('Alabi', ['admin'], "password10", "alabi10@yahoo.com", \Authentication::ALL_IDENTITY_TYPE['email']));

// $admin = new \Admin(1);
// var_dump($admin->getInfo());

//$player=\Player::create('Alabi', ['player'], "password10", "alabi12@yahoo.com", \Authentication::ALL_IDENTITY_TYPE['email']);
//var_dump($player);

