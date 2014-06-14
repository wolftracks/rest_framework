<?php
/*
 * By installing or using this file, you are confirming on behalf of the entity
 * subscribed to the SugarCRM Inc. product ("Company") that Company is bound by
 * the SugarCRM Inc. Master Subscription Agreement ("MSA"), which is viewable at:
 * http://www.sugarcrm.com/master-subscription-agreement
 *
 * If Company is not bound by the MSA, then by installing or using this file
 * you are agreeing unconditionally that Company will be bound by the MSA and
 * certifying that you have authority to bind Company accordingly.
 *
 * Copyright  2004-2013 SugarCRM Inc.  All rights reserved.
 */

require_once(dirname(__FILE__)."/../../lib/env/entrypoint.php");
define('ENTRY_POINT_TYPE', 'daemon');

$dbm = DBManagerFactory::getDatabaseManager();
if (empty($dbm)) {
    Log::fatal('Unable to connect to Database - JobHandler terminating ...');
    exit;
}

require_once("sugarcrm/services/StorageService/ImageStoreService.php");

$customer_id = 'sugartraining1';

$storageService = new ImageStoreService($customer_id);

echo "\n\n--------------- PUT FILE --------------------\n";
$filePath = '/Users/twolfe/superman.png';
$remoteName = 'superman.png';
$contentType = 'image/png';

$metadata = array(
    'hello' => 'world',
);

$url = $storageService->putFile($filePath, $remoteName, $contentType, $metadata);
printf("Remote Name:  %s\n",$remoteName);
printf("URL:  %s\n",$url);

echo "\n\n--------------- GET OBJECT --------------------\n";
$result = $storageService->getObject($remoteName);
print_r($result);

echo "\n\n--------------- LIST OBJECTS --------------------\n";
$files = $storageService->listObjects();
print_r($files);

exit;

echo "\n\n--------------- LIST OBJECTS --------------------\n";
$files = $storageService->listObjects();
print_r($files);

echo "\n\n--------------- DELETE OBJECT --------------------\n";
$remoteName = 'superman.png';
printf("Remote Name:  %s\n",$remoteName);
$storageService->deleteObject($remoteName);

echo "\n\n--------------- LIST OBJECTS --------------------\n";
$files = $storageService->listObjects();
print_r($files);

echo "\n\n--------------- PUT FILE --------------------\n";
$filePath = '/Users/twolfe/superman.png';
$remoteName = 'superman.png';
$contentType = 'image/png';
$url = $storageService->putFile($filePath, $remoteName, $contentType);
printf("Remote Name:  %s\n",$remoteName);
printf("URL:  %s\n",$url);

echo "\n\n--------------- LIST OBJECTS --------------------\n";
$files = $storageService->listObjects();
print_r($files);

echo "\n\n--------------- DELETE OBJECT --------------------\n";
$remoteName = 'superman.png';
printf("Remote Name:  %s\n",$remoteName);
$storageService->deleteObject($remoteName);

echo "\n\n--------------- LIST OBJECTS --------------------\n";
$files = $storageService->listObjects();
print_r($files);

echo "\n\n--------------- PUT OBJECT --------------------\n";
$filePath = '/Users/twolfe/superman.jpg';
$remoteName = 'qwerty.png';
$contentType = 'image/jpeg';
$fh=fopen($filePath, "r");
$contents = fread($fh, filesize($filePath));
fclose($fh);
$url = $storageService->putObject($contents, $remoteName, $contentType);
printf("Remote Name:  %s\n",$remoteName);
printf("URL:  %s\n",$url);

echo "\n\n--------------- LIST OBJECTS --------------------\n";
$files = $storageService->listObjects();
print_r($files);

echo "\n\n--------------- DELETE OBJECT --------------------\n";
$remoteName = 'qwerty.png';
printf("Remote Name:  %s\n",$remoteName);
$storageService->deleteObject($remoteName);

echo "\n\n--------------- LIST OBJECTS --------------------\n";
$files = $storageService->listObjects();
print_r($files);


