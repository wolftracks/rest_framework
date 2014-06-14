<?php
include_once("../../lib/env/entrypoint.php");
define('ENTRY_POINT_TYPE', 'daemon');

require_once("sugarcrm/clients/SugarClient.php");

$dbm = DBManagerFactory::getDatabaseManager();
if (empty($dbm)) {
	echo 'Unable to connect to Database - terminating ...';
    Log::fatal('Unable to connect to Database - terminating ...');
    exit;
}
  	
$data = array(
    'api_user'     => 'sugartraining2',
    'api_instance' => '1234567890',
    'api_site_url' => 'http://localhost:8080/mango',
    'ip' => '127.0.0.1'
);	

$clientInfo = ClientInfo::fromArray($data); 
// print_r($clientInfo->toArray());   

$sugarClient = new SugarClient();
                         
try {
	$result = $sugarClient->trackClientRequest($clientInfo); 
	print_r($result); 
	print_r($clientInfo); 
} catch (Exception $e) {
	echo("ERROR: " . $e->getMessage());
}	