<?php
include_once("../../lib/env/entrypoint.php");
define('ENTRY_POINT_TYPE', 'daemon');

require_once("sugarcrm/helpers/MailServiceSendParameters.php");

$dbm = DBManagerFactory::getDatabaseManager();
if (empty($dbm)) {
    Log::fatal('Unable to connect to Database - JobHandler terminating ...');
    exit;
}

$jobQueue = SugarClassLoader::getInstance('JobQueue');

$result = $jobQueue->writeQueue('123', '456', '777');
printf("Write Result = %s\n", $result?"TRUE":"FALSE");

for ($i=0; $i<2 ; $i++) {
    $job = $jobQueue->readQueue();
    if (empty($job)) {
        break;
    }
    printf("\n----------------------\n");
    $jobArray = $job->toArray();
	if (!empty($jobArray['payload'])) {
		$jobArray['payload'] = array("*PAYLOAD*");
	}
 	print_r($jobArray);
    if ($i%2==0) {
        $result = $jobQueue->setFinalJobStatus($job->job_id, JobQueue::STATUS_COMPLETE);
        printf("setFinalJobStatus COMPLETE Result = %s\n", $result?"TRUE":"FALSE");
    } else {
        $result = $jobQueue->retryJob($job->job_id);
        printf("retryJob RETRY = %s\n", $result?"TRUE":"FALSE");
    }
}

