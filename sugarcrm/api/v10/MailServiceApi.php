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

require_once("sugarcrm/helpers/MailServiceSendParameters.php");

class MailServiceApi extends SugarApi
{
    /**
     * These are the defaults delimiters (also Mandrill's standard delimiters)
     */
    public static $default_merge_field_delimiters = array(
        "begin" => "*|",
        "end" => "|*",
    );

    public function registerApiRest()
    {
        $api = array(
            'queueMail' => array(
                'reqType' => 'POST',
                'path' => array('email', 'send'),
                'pathVars' => array('', ''),
                'method' => 'queueMail',
            ),
            'sendMailImmediate' => array(
                'reqType' => 'POST',
                'path' => array('email', 'sendimmediate'),
                'pathVars' => array('', ''),
                'method' => 'sendMailImmediate',
            ),
            'getTrackingDetail' => array(
                'reqType' => 'GET',
                'path' => array('email','tracking'),
                'pathVars' => array('', ''),
                'method' => 'getTrackingDetail',
            ),
        );

        return $api;
    }

    /**
     *  Construct mail request and push it to the Job Queue
     */
    public function queueMail(ServiceBase $api, $params)
    {
        $queue = SugarClassLoader::getInstance('JobQueue');
        $sendParams = $this->getSendParameters($params);

        $mailProvider = SugarConfig::getEmailServiceProvider();
        $recipients = $sendParams->recipients;
        $totalRecipients = count($recipients);
        $recipientsAttempted = 0;
        $recipientsAccepted  = 0;
        for ($num_packets=0, $index=0; $index < $totalRecipients; $num_packets++, $index += $packet_size) {
            $packet = array_slice($recipients, $index, $mailProvider['max_send_size'], true);
            $packet_size = count($packet);
            // print_r($packet);

            $sendParams->recipients = $packet;

            $job = new Job();
            $job->setJobType(JobQueue::JOBTYPE_SENDMAIL);
            $job->setCustomerId($sendParams->customer_id);
            $payload = $sendParams->toArray();
            $job->setPayload($payload);
            $result = $queue->writeQueue($job);
            $recipientsAttempted += $packet_size;
            if ($result) {
                Log::info("SendMail Job Queued: customer={$sendParams->customer_id}  TotalRecipients={$totalRecipients}  PacketSize={$packet_size}  Attempted={$packet_size}  Accepted={$packet_size}");
                $recipientsAccepted  += $packet_size;
            }
        }

        if ($recipientsAttempted > 0) {
            return array(
                "status" => "accepted",
                "recipients_attempted" => $recipientsAttempted,
                "recipients_accepted"  => $recipientsAccepted
            );
        }

        //-- No Jobs Posted
        Log::fatal("SendMail Job Not Created - No Recipients: customer={$sendParams->customer_id}");

        throw new SugarApiExceptionError('Unable to process request');
    }

    /**
     *  Send Mail Immediately using the configured Mail Service Provider
     */
    public function sendMailImmediate(ServiceBase $api, $params)
    {
        $mailService = $this->getMailService();
        $sendParams = $this->getSendParameters($params);

        $serviceResponse=array();

        $mailProvider = SugarConfig::getEmailServiceProvider();
        $recipients = $sendParams->recipients;
        $totalRecipients = count($recipients);
        $recipientsAttempted = 0;
        $recipientsAccepted  = 0;
        for ($num_packets=0, $index=0; $index < $totalRecipients; $num_packets++, $index += $packet_size) {
            $packet = array_slice($recipients, $index, $mailProvider['max_send_size'], true);
            $packet_size = count($packet);
            // print_r($packet);

            $sendParams->recipients = $packet;
            $response = $mailService->send($this->customer_id, $sendParams);
            $serviceResponse[] = $response->provider_response;
            if ($response->success) {
                Log::info("SendMail Immediate: customer={$sendParams->customer_id}  TotalRecipients={$totalRecipients}  PacketSize={$packet_size}  Attempted={$response->recipients_attempted}  Accepted={$response->recipients_accepted}");
                $recipientsAttempted += $response->recipients_attempted;
                $recipientsAccepted  += $response->recipients_accepted;
            }
        }

        if ($recipientsAttempted > 0) {
            return array(
                "status" => "accepted",
                "recipients_attempted" => $recipientsAttempted,
                "recipients_accepted"  => $recipientsAccepted,
                "details" => $serviceResponse
            );
        }

        //-- No Jobs Posted
        Log::fatal("SendMail Immediate Failed - No Recipients: customer={$sendParams->customer_id}");

        //-- No Mail Sent
        throw new SugarApiExceptionError('Unable to process request');
    }

    /**
     * Get Tracking Detail
     */
    public function getTrackingDetail(ServiceBase $api, $params)
    {
        $trackingService = $this->getTrackingService();
        $response = $trackingService->getTrackingRecords($this->customer_id, $params);

        return $response;
    }

    /**
     *  Construct mail request and push it to the Job Queue
     */
    public function uploadImage(ServiceBase $api, $params)
    {
        $queue = SugarClassLoader::getInstance('JobQueue');
        $sendParams = $this->getSendParameters($params);

        $mailProvider = SugarConfig::getEmailServiceProvider();
        $recipients = $sendParams->recipients;
        $totalRecipients = count($recipients);
        $recipientsAttempted = 0;
        $recipientsAccepted  = 0;
        for ($num_packets=0, $index=0; $index < $totalRecipients; $num_packets++, $index += $packet_size) {
            $packet = array_slice($recipients, $index, $mailProvider['max_send_size'], true);
            $packet_size = count($packet);
            // print_r($packet);

            $sendParams->recipients = $packet;

            $job = new Job();
            $job->setJobType(JobQueue::JOBTYPE_SENDMAIL);
            $job->setCustomerId($sendParams->customer_id);
            $payload = $sendParams->toArray();
            $job->setPayload($payload);
            $result = $queue->writeQueue($job);
            $recipientsAttempted += $packet_size;
            if ($result) {
                Log::info("SendMail Job Queued: customer={$sendParams->customer_id}  TotalRecipients={$totalRecipients}  PacketSize={$packet_size}  Attempted={$packet_size}  Accepted={$packet_size}");
                $recipientsAccepted  += $packet_size;
            }
        }

        if ($recipientsAttempted > 0) {
            return array(
                "status" => "accepted",
                "recipients_attempted" => $recipientsAttempted,
                "recipients_accepted"  => $recipientsAccepted
            );
        }

        //-- No Jobs Posted
        Log::fatal("SendMail Job Not Created - No Recipients: customer={$sendParams->customer_id}");

        throw new SugarApiExceptionError('Unable to process request');
    }


    /**
     *  Get an instance of the MailService for
     *  the configured Mail Service Provider
     */
    protected function getMailService()
    {
        $mailProvider = SugarConfig::getEmailServiceProvider();
        $mailServiceClass = $mailProvider['provider_name'] . 'MailService';
        $mailServiceFile = 'sugarcrm/services/MailService/' . $mailServiceClass . '.php';
        if (file_exists($mailServiceFile)) {
            include_once($mailServiceFile);
        } else {
            $msg = "Mail Service handler not found: {$mailServiceFile}";
            Log:error($msg);
            throw new SugarApiExceptionNotFound($msg);
        }
        $mailService = SugarClassLoader::getInstance($mailServiceClass);
        $mailService->setServiceAccountInfo($mailProvider['account_id'], $mailProvider['account_password']);
        return $mailService;
    }

    /**
     *  Get an instance of the Mail TrackingService for
     *  the configured Mail Service Provider
     */
    protected function getTrackingService()
    {
        $mailProvider = SugarConfig::getEmailServiceProvider();
        $trackingServiceClass = $mailProvider['provider_name'] . 'MailTrackingService';
        $trackingServiceFile = 'sugarcrm/services/TrackingService/' . $trackingServiceClass . '.php';
        if (file_exists($trackingServiceFile)) {
            include_once($trackingServiceFile);
        } else {
            $msg = "Mail Tracking Service handler not found: {$trackingServiceFile}";
            Log:error($msg);
            throw new SugarApiExceptionNotFound($msg);
        }
        $trackingService = SugarClassLoader::getInstance($trackingServiceClass);
        $trackingService->setServiceAccountInfo($mailProvider['account_id'], $mailProvider['account_password']);
        return $trackingService;
    }

    /**
     *  Audit request input and generate the common MailServiceSendParameters
     *  interface object that will be handed off to the configured
     *  Mail Service Provider
     */
    protected function getSendParameters($params)
    {
        $fields = array(
            'communication_id' => 'string',
            'merge_field_delimiters' => 'array',
            'global_merge_data' => 'array',
            'recipient_merge_vars' => 'array',
            'recipients' => 'array',
            'headers' => 'array',
            'from' => 'array',
            'reply_to' => 'array',
            'subject' => 'string',
            'html_body' => 'string',
            'text_body' => 'string',
            'inline_images' => 'array',
            'attachments' => 'array',
        );

        $required = array(
            'communication_id',
            'recipients',
            'from',
            'subject',
        );

        foreach ($required as $var) {
            if (empty($params[$var])) {
                throw new SugarApiExceptionMissingParameter("Required Field Missing : " . $var);
            }
        }

        $args = array();
        foreach ($params as $fieldName => $value) {
            if (!isset($fields[$fieldName])) {
                throw new SugarApiExceptionInvalidParameter("Parameter Not Understood : " . $fieldName);
            }
            if ($fields[$fieldName] == "array") {
                $args[$fieldName] = empty($params[$fieldName]) ? array() : $params[$fieldName];
                if (!is_array($args[$fieldName])) {
                    $msg = "Parameter Format Invalid - Array Expected : {$fieldName}";
                    throw new SugarApiExceptionInvalidParameter($msg);
                }
            } elseif ($fields[$fieldName] == "string") {
                $args[$fieldName] = empty($params[$fieldName]) ? '' : $params[$fieldName];
                if (!is_string($args[$fieldName])) {
                    $msg = "Parameter Format Invalid - String Expected : {$fieldName}";
                    throw new SugarApiExceptionInvalidParameter($msg);
                }
            }
        }

        $sendParams = new MailServiceSendParameters();

        $sendParams->customer_id = $this->customer_id;
        $sendParams->communication_id = $args['communication_id'];
        $sendParams->subject = $args['subject'];
        $sendParams->html_body = $args['html_body'];
        $sendParams->text_body = $args['text_body'];
        $sendParams->from_email = empty($args['from']['email']) ? '' : $args['from']['email'];
        $sendParams->from_name = empty($args['from']['name']) ? '' : $args['from']['name'];

        $sendParams->headers = array_merge(array(), $args['headers']);
        $sendParams->headers['X-CUSTOMER-ID'] = $sendParams->customer_id;
        $sendParams->headers['X-COMMUNICATION-ID'] = $sendParams->communication_id;

        if (empty($args['reply_to'])) {
            $reply_to_name = $sendParams->from_name;
            $reply_to_email = $sendParams->from_email;
        } else {
            $reply_to_name = empty($args['reply_to']['name']) ? '' : $args['reply_to']['name'];
            $reply_to_email = empty($args['reply_to']['email']) ? '' : $args['reply_to']['email'];
        }
        if (empty($sendParams->headers['reply-to'])) {
            $sendParams->headers['reply-to'] = $reply_to_email;
        }
        if (empty($args['merge_field_delimiters'])) {
            $sendParams->merge_field_delimiters = self::$default_merge_field_delimiters;
        } else {
            $sendParams->merge_field_delimiters = $args['merge_field_delimiters'];
        }

        $sendParams->global_merge_data = empty($args['global_merge_data']) ? array() : $args['global_merge_data'];
        $sendParams->recipient_merge_vars = empty($args['recipient_merge_vars']) ? array() : $args['recipient_merge_vars'];
        $sendParams->recipients = empty($args['recipients']) ? array() : $args['recipients'];
        $sendParams->images = empty($args['inline_images']) ? array() : $args['inline_images'];
        $sendParams->attachments = empty($args['attachments']) ? array() : $args['attachments'];

        $sendParams->tags = array('campaign'); // campaign, notice,  (broad classification)
        $sendParams->metadata = array();

        return ($sendParams);
    }

}
