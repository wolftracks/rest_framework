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

$debug = false;  // Debug More to Log File
$trace = false;  // Print Status To Console
$once  = false;  // Run Once then Quit

$GLOBALS['logger_file_name'] = "sugarcron.log";

require_once(dirname(__FILE__)."/../../lib/env/entrypoint.php");
define('ENTRY_POINT_TYPE', 'daemon');

require_once("sugarcrm/helpers/MailServiceSendParameters.php");
require_once 'sugarcrm/helpers/ServiceResult.php';

$dbm = DBManagerFactory::getDatabaseManager();
if (empty($dbm)) {
    Log::fatal('Unable to connect to Database - JobHandler terminating ...');
    exit;
}

/*------------- Timer Values -----------*/
$maxIntervalMinutes = SugarConfig::get('daemon.cron_interval_minutes');
$maxIntervalSeconds = $maxIntervalMinutes * 60;
$maxIntervalSeconds -= 10;   // We will use all but 10 seconds of the interval

// Process Sleeps this long when the readQueue Shows no Jobs Available
$sleepTimeSeconds = SugarConfig::get('jobhandler.sleep_seconds');
/*--------------------------------------*/

$mailProvider = SugarConfig::getEmailServiceProvider();
$mailServiceClass = $mailProvider['provider_name'] . 'MailService';
$mailServiceFile = 'sugarcrm/services/MailService/' . $mailServiceClass . '.php';
if (file_exists($mailServiceFile)) {
    include_once($mailServiceFile);
} else {
    Log::fatal('Mail Service File does Not Exist: ' . $mailServiceFile);
    exit;
}

$start_time = time();
$end_time = $start_time + $maxIntervalSeconds;

$queue = SugarClassLoader::getInstance('JobQueue');

if ($debug) {
    $msg = sprintf("-- Start JobHandler - Time: %s  Datetime: %s", $start_time, date("Y-m-d H:i:s"));
    Log::debug($msg);
}

$jobsProcessed = 0;
while (true) {
    if (time() >= $end_time) {
        break;
    }

    $job = $queue->readQueue(JobQueue::JOBTYPE_SENDMAIL);
    if (!empty($job)) {
        $jobsProcessed++;
        $mailService = new $mailServiceClass();
        $mailService->setServiceAccountInfo($mailProvider['account_id'], $mailProvider['account_password']);
        $result = processMailSendJob($queue, $job, $mailService);

        if ($once) {
            break;
        }
    } else {
        if ($once) {
            break;
        }

        if ($trace) {
            printf("Sleeping $sleepTimeSeconds seconds ...\n");
        }

        sleep($sleepTimeSeconds);
    }
}

$end = time();
$elapsed = $end - $start_time;

if ($debug) {
    $msg = sprintf(
        "-- Stop JobHandler  - Time: %s  Datetime: %s   ... Elapsed (%s seconds)",
        $end,
        date("Y-m-d H:i:s"),
        $elapsed
    );
    Log::debug($msg);
}

if ($once) {
    printf("Done!   Jobs Processed: {$jobsProcessed}\n");
}


function processMailSendJob(JobQueue $queue, Job $job, MailService $mailService) {
    global $trace;

    if ($trace) {
        $traceInfo = array(
            "Job Id:" => $job->getJobId(),
            "Customer Id:" => $job->getCustomerId()
        );
        printf("---------- Processing Job --------\n%s\n",print_r($traceInfo,true));
    }

    try {
        $sendParams = MailServiceSendParameters::fromArray($job->getPayload());
        $serviceResponse = $mailService->send($job->getCustomerId(), $sendParams);
        if ($serviceResponse->success) {
           // Job Completed Successfully - Mark Job Complete
           $queue->setFinalJobStatus($job->getJobId(), JobQueue::STATUS_COMPLETE);
           return true;
        }

        if ($serviceResponse->retry) {
            // Retry Requested
            $result = $queue->retryJob($job->getJobId());
            if (!$result) {
                // Unable to Retry - Retry Maximum Likely Reached - Mark Job Failed
                $queue->setFinalJobStatus($job->getJobId(), JobQueue::STATUS_FAILED);
            }
        } else {
            // No Retry ... Mark as Failed
            $queue->setFinalJobStatus($job->getJobId(), JobQueue::STATUS_FAILED);
        }
        return false;
    } catch (Exception $e) {
        // Unexpected Failure - DO NOT Retry this Request ... Mark as Failed
        $queue->setFinalJobStatus($job->getJobId(), JobQueue::STATUS_FAILED);
        return false;
    }
}

