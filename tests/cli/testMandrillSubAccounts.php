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

$mandrill = new Mandrill($mandrill_key);

$result = $mandrill->subaccounts->getList();
print_r($result); echo "\n\n";


$data=$result;
$newAccountId = create_guid();
$result = $mandrill->subaccounts->add($newAccountId, "Name is " . time(), json_encode($data));
print_r($result); echo "\n\n";

$result = $mandrill->subaccounts->info($newAccountId);
print_r($result); echo "\n\n";

if (!empty($result['notes'])) {
    printf("---------------- NOTES ---------------------\n");
    $notes = json_decode($result['notes'], true);
    print_r($notes);  echo "\n\n";
}


 

