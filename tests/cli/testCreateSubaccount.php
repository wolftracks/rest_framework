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

$subaccounts = $mandrill->subaccounts->getList();
// print_r($subaccounts); echo "\n\n";

$newAccountId = create_guid();

$exists = subaccountExists($newAccountId);
printf("%-40s %s\n", $newAccountId, $exists ? "TRUE" : "FALSE");

foreach ($subaccounts AS $subaccount) {
    $exists = subaccountExists($subaccount["id"]);
    printf("%-40s %s\n", $subaccount["id"], $exists ? "TRUE" : "FALSE");
}

$exists = subaccountExists($newAccountId);
printf("%-40s %s\n", $newAccountId, $exists ? "TRUE" : "FALSE");

/*--------------- CREATE SUBACCOUNTS ON THE FLY -----------------------------------------*/

if (!subaccountExists($newAccountId)) {
    $subaccount = addSubAccount($newAccountId);
    if (subaccountExists($newAccountId)) {
        printf("SubAccount Created\n");
        $subaccount = getSubAccount($newAccountId);
        print_r($subaccount);
    } else {
        printf("*** ERROR ***  SubAccount Create Failed\n");
    }
}

/*---------------------------------------------------------------------------------------*/


function subaccountExists($id)
{
    return !is_null(getSubAccount($id));
}

function getSubAccount($id)
{
    global $mandrill;
    try {
        $subaccount = $mandrill->subaccounts->info($id);
        if (!empty($subaccount) && ($subaccount['id'] === $id)) {
            return $subaccount;
        }
    } catch (Exception $e) {
    }
    return null;
}

function addSubAccount($id, $name = null, $notes = null)
{
    global $mandrill;
    try {
        $subaccount = $mandrill->subaccounts->add($id, $name, $notes);
        if (!empty($subaccount) && ($subaccount['id'] === $id)) {
            return $subaccount;
        }
    } catch (Exception $e) {
    }
    return null;
}    




 

