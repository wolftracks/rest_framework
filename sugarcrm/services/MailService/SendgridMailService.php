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

require_once 'sugarcrm/services/MailService/MailService.php';
require_once 'sugarcrm/helpers/ServiceResult.php';
SendGrid::register_autoloader();

class SendgridMailService extends MailService
{
    protected $service_account_user;
    protected $service_account_pass;

    /**
     * @param string $service_account_user
     * @param string $service_account_pass
     */
    public function setServiceAccountInfo($service_account_user, $service_account_pass)
    {
        $this->service_account_user = $service_account_user;
        $this->service_account_pass = $service_account_pass;
    }

    /**
     * @param string $customer_id
     * @param MailServiceSendParameters $sendParams
     * @return ServiceResult
     */
    public function send($customer_id, MailServiceSendParameters $sendParams) {
        $attachments = array();

        $serviceResult = new ServiceResult();

        $sendgrid = new SendGrid($this->service_account_user, $this->service_account_pass);

        $mail = new SendGrid\Email();

        /**
        $global_merge_vars = array(
            array(
                'name' => 'company_name',
                'content' => 'BakersField Electronics, Inc.',
            ),
            array(
                'name' => 'service_provider',
                'content' => 'Sendgrid',
            ),
        );

        $recipient_merge_vars = array(
            "first_name",
            "last_name",
            "city",
            "state",
            "appointment_date",
            "appointment_time",
            "representative_name",
            "representative_first_name",
        );

        $recipients = array(
            array("abc@yahoo.com", "Captain Kangaroo",   "merge-data" => array("Captain",   "Kangaroo",     "Chicago", 		"Illinois",		"10/24/2014",	"9:15 AM",	"Robert Blake",		"Robert")),
            array("abc@yahoo.com", "Doctor Do Little",   "merge-data" => array("Doctor",    "Do Little",    "Milwaukee", 	"Wisconsin",	 "8/12/2014",	"8:10 AM",	"Peter Jennings",	"Peter")),
            array("abc@yahoo.com", "Casper the Ghost",   "merge-data" => array("Casper",    "Ghost",        "Indianapolis", "Indiana",		 "7/24/2014",	"10:30 AM", "Roger Rabbit", 	"Roger")),
            array("abc@yahoo.com", "Curly Howard",       "merge-data" => array("Curly",     "Howard",       "Minneapolis", 	"Minnesota",	 "9/3/2014",	"10:25 AM", "Clark Kent",		"Clark")),
            array("abc@yahoo.com", "Moe Howard",         "merge-data" => array("Moe",       "Howard",       "St. Paul", 	"Minnesota",	"11/16/2014",	"2:25 PM",	"Bruce Willis", 	"Bruce")),
            array("abc@yahoo.com", "Larry Fine",         "merge-data" => array("Larry",     "Fine",         "Rochester", 	"Minnesota",	"12/25/2014",	"5:15 PM",	"David Banner", 	"David")),
        );
        **/

        /*-- Merge field delimiters present in the provided HTML --*/
        $global_merge_data       = $sendParams->global_merge_data;
        $merge_field_delimiters  = $sendParams->merge_field_delimiters;
        $recipient_merge_vars    = $sendParams->recipient_merge_vars;
        $recipients              = $sendParams->recipients;

        $num_global_merge_vars    = count($global_merge_data);
        $num_recipient_merge_vars = count($recipient_merge_vars);

        // addSubstitution("%name%", array("John", "Harry", "Bob"));

        $begin_delimiter = empty($merge_field_delimiters['begin']) ? '' : $merge_field_delimiters['begin'];
        $end_delimiter   = empty($merge_field_delimiters['end']) ? '' : $merge_field_delimiters['end'];

        if ($num_global_merge_vars > 0) {
            $global_merge_vars = array();
            $global_merge_value = array();
            $global_merge_var_data = array();
            $i=0;
            foreach($global_merge_data as $gdata) {
                $global_merge_vars[$i]  = $begin_delimiter . $gdata['name'] . $end_delimiter;
                $global_merge_value[$i] = $gdata['content'];
                $global_merge_var_data[$i] = array();
                $i++;
            }
        }

        if ($num_recipient_merge_vars > 0) {
            $merge_vars = array();
            $merge_var_data = array();
            $i=0;
            foreach($recipient_merge_vars as $var) {
                $merge_vars[$i] = $begin_delimiter . $var . $end_delimiter;
                $merge_var_data[$i] = array();
                $i++;
            }
        }

        $toList = array();
        foreach($recipients AS $recipient) {
            if (!empty($recipient['email'])) {
                $email = $recipient['email'];
                $name  = empty($recipient['name']) ? '' : $recipient['name'];
                $toList[] = $email;
                if ($num_global_merge_vars > 0) {
                    $i=0;
                    foreach($global_merge_data as $gdata) {
                        $global_merge_var_data[$i][] = $global_merge_value[$i];
                        $i++;
                    }
                }
                if ($num_recipient_merge_vars > 0) {
                    $i=0;
                    $mdata = empty($recipient['merge-data']) ? array() : $recipient['merge-data']; // Supplied in recipient
                    $mcount = count($mdata);
                    foreach($recipient_merge_vars AS $var) {
                        $value = ($mcount > $i) ? $mdata[$i] : '';
                        $merge_var_data[$i][] = $value;
                        $i++;
                    }
                }
            }
        }

        try {
            //print_r($toList);

            $mail->setTos($toList);
            if ($num_global_merge_vars > 0) {
                $i=0;
                foreach($global_merge_vars as $var) {
                    $mail->addSubstitution($global_merge_vars[$i], $global_merge_var_data[$i]);
                     //printf("Substitution-Var: %s\n",$global_merge_vars[$i]);
                     //print_r($global_merge_var_data[$i]);
                    $i++;
                }
            }
            if ($num_recipient_merge_vars > 0) {
                $i=0;
                foreach($recipient_merge_vars as $var) {
                    $mail->addSubstitution($merge_vars[$i], $merge_var_data[$i]);
                     //printf("Substitution-Var: %s\n",$merge_vars[$i]);
                     //print_r($merge_var_data[$i]);
                    $i++;
                }
            }

            $mail->setFrom($sendParams->from_email);

            $mail->setSubject($sendParams->subject);

            $mail->setText($sendParams->text_body);
            $mail->setHtml($sendParams->html_body);

            $mail->setMessageHeaders($sendParams->headers);

            if (!empty($sendParams->attachments)) {
                $base_dir = "temp";
                if (!file_exists($base_dir)) {
                    mkdir($base_dir, 0777);
                }

                foreach($sendParams->attachments as $paramAttachment) {
                    $tempfile = $base_dir . "/" . create_guid();
                    $fp = fopen($tempfile, 'w');
                    fwrite($fp, base64_decode($paramAttachment['content']));
                    fclose($fp);

                    $attachments[$paramAttachment['name']] = $tempfile;
                }

               if (!empty($attachments)) {
                  $mail->setAttachments($attachments);
               }
            }

            $result = $sendgrid->web->send($mail);
            if (empty($result) || empty($result->message) || $result->message !== 'success') {
                $serviceResult->success = false;
                $serviceResult->retry = false;
                $msg  = "SendgridMailService: Customer: {$customer_id}  Communication: {$sendParams->communication_id} ";
                if (empty($result) || empty($result->message)) {
                    $msg .= " Unexpected Response: Result Message is Empty";
                } else {
                    $msg .= " Error Response: Sendgrid Message=" . $result->message . "  Errors: " . $result->errors[0];
                }
                Log::error($msg);
            } else {
                $serviceResult->success = true;
            }

        } catch(Exception $e) {
            $serviceResult->success = false;
            $serviceResult->retry = true;
            $message = $e->getMessage();
            $msg  = "SendgridMailService: Customer: {$customer_id}  Communication: {$sendParams->communication_id} ";
            $msg .= " Exception Thrown By Sendgrid: {$message}";
            Log::error($msg);
        }

        if (!empty($attachments)) {
            foreach($attachments as $filename => $tempfile) {
                @unlink($tempfile);
            }
        }
        return($serviceResult);
    }

}
