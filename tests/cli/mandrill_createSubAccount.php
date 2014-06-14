<?php
include_once("../../lib/env/entrypoint.php");
define('ENTRY_POINT_TYPE', 'daemon');

require_once("lib/http/HttpClient.php");
require_once("sugarcrm/clients/SugarClient.php"); 

$dbm = DBManagerFactory::getDatabaseManager();
if (empty($dbm)) {
	echo 'Unable to connect to Database - terminating ...';
    Log::fatal('Unable to connect to Database - terminating ...');
    exit;
}  

$mandrillConfig = SugarConfig::getEmailServiceProvider('Mandrill'); 
$mandrill_key = $mandrillConfig['account_id'];  

$restClient = new HttpClient();

$data = array(
	"key" => $mandrill_key
);
              
$id = "TEMP-" . time();
$data['id'] = $id;
            
$url = 'https://mandrillapp.com/api/1.0/subaccounts/add.json'; 
$result = $restClient->callResource('POST', $url, $data);
if ($result['code'] != '200') {
	echo "\n----------------------------\n";
	print_r($result);
	echo "\n";
	exit;
}  
print_r($result['data']); 



$data['id'] = $id;
            
$url = 'https://mandrillapp.com/api/1.0/subaccounts/info.json'; 
$result = $restClient->callResource('POST', $url, $data);
if ($result['code'] != '200') {
	echo "\n----------------------------\n";
	print_r($result);
	echo "\n";
	exit;
}  
print_r($result['data']);


