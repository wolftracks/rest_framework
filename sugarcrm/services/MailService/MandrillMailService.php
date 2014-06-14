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

class MandrillMailService extends MailService
{
    const UNSUBSCRIBE_LINK_VARIABLE = 'unsubscribe_link';
    const DEFAULT_UNSUBSCRIBE_CONFIRMATION_PAGE = 'w3/unsubscribe.php';

    const MANDRILL_UNSUBSCRIBE_KEYWORD = 'UNSUB:';

    /**
     * Mandrill's standard Merge Field Delimiters
     */
    public static $mandrill_merge_field_delimiters = array(
        "begin" => "*|",
        "end" => "|*",
    );

    /* Credentials for Mandrill Account */
    protected $service_account_user;
    protected $service_account_pass;

    /* Default Unsubscribe Confirmation Link */
    protected $defaultConfirmationLink;

    /* Mandrill instance */
    protected $mandrill;

    /* SubAccount in effect for this customer */
    protected $subAccount;

    public function __construct()
    {
        parent::__construct();
        $this->defaultConfirmationLink = $this->coreUrl . "/" . self::DEFAULT_UNSUBSCRIBE_CONFIRMATION_PAGE;
    }

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
     * Send Email
     * @param string $customer_id
     * @param MailServiceSendParameters $sendParams
     * @return ServiceResult
     */
    public function send($customer_id, MailServiceSendParameters $sendParams)
    {
        $serviceResult = new ServiceResult();

        $this->fixupMergeFieldData($sendParams);

        $recipient_merge_vars = $sendParams->recipient_merge_vars;
        $recipients = $sendParams->recipients;

        $num_recipient_merge_vars = count($recipient_merge_vars);
        $toList = array();
        $mergeFields = array();
        foreach ($recipients AS $recipient) {
            if (!empty($recipient['email'])) {
                $toList[] = array(
                    'email' => $recipient['email'],
                    'name' => empty($recipient['name']) ? '' : $recipient['name'],
                    // 'type' => 'to',
                );
                if ($num_recipient_merge_vars > 0) {
                    $mergeFieldData = array();
                    $mergeFieldData['rcpt'] = $recipient['email'];
                    $mdata = empty($recipient['merge-data']) ? array() : $recipient['merge-data']; // Supplied in recipient
                    $j = 0;
                    $mcount = count($mdata);
                    foreach ($recipient_merge_vars AS $var) {
                        $value = ($mcount > $j) ? $mdata[$j] : '';
                        $mfield = array(
                            'name' => $var,
                            'content' => $value
                        );
                        $mergeFieldData['vars'][] = $mfield;
                        $j++;
                    }
                    $mergeFields[] = $mergeFieldData;
                }
            }
        }

        $this->mandrill = new Mandrill($this->service_account_user);

        $this->subAccount = $this->prepareCustomerSubaccount($customer_id);

        $tags = array();
        if (!empty($sendParams->tags)) {
            $tags = $sendParams->tags;
        }

        $metadata = array();
        if (!empty($sendParams->metadata)) {
            $metadata = $sendParams->metadata;
        }

        if (!empty($customer_id)) {
            $metadata['customer'] = $customer_id;
            $sendParams->headers['X-CUSTOMER-ID'] = $customer_id;
        }
        if (!empty($sendParams->communication_id)) {
            $metadata['communication'] = $sendParams->communication_id;
            $sendParams->headers['X-COMMUNICATION-ID'] = $sendParams->communication_id;
        }

        $message = array(
            /*---- Mandrill Settings not provided through Web Service API ------*/
            'important' => false,
            'track_opens' => true,
            'track_clicks' => true,
            'auto_text' => null,
            'auto_html' => null,
            'inline_css' => null,
            'url_strip_qs' => false,
            'view_content_link' => null,
            'tracking_domain' => null,
            'signing_domain' => null,
            'return_path_domain' => null,
            'merge' => true,
            'preserve_recipients' => false, /* important - keeps recipient names off of the to List (not displayed) */

            /*---- Mandrill Settings provided directly or indirectly through Web Service API ------*/
            // 'bcc_address' => $sendParams->from_email,

            'html' => $sendParams->html_body,
            'text' => $sendParams->text_body,
            'subject' => $sendParams->subject,
            'from_email' => $sendParams->from_email,
            'from_name' => $sendParams->from_name,
            'to' => $toList,
            'headers' => $sendParams->headers,
            'global_merge_vars' => $sendParams->global_merge_data,
            'merge_vars' => $mergeFields,
            'images' => $sendParams->images,
            'attachments' => $sendParams->attachments,

            'subaccount' => $this->subAccount,

            'tags' => $tags,
            'metadata' => $metadata,
        );

        try {
            $result = $this->mandrill->messages->send($message);
            $serviceResult->provider_response = $result;
            $serviceResult->recipients_attempted = count($toList);
            $serviceResult->recipients_accepted  = count($result);
            if (empty($result) || (count($result) !== count($toList))) {
                $serviceResult->success = false;
                $serviceResult->retry = false;
                $msg  = "MandrillMailService: Customer: {$customer_id}  Communication: {$sendParams->communication_id} ";
                if (empty($result)) {
                    $msg .= " Unexpected Response: Result is Empty";
                } else {
                    $msg .= " Unexpected Response: To List Count=" . count($toList) . "  Reply Status Count=" . count($result);
                }
                Log::error($msg);
            } else {
               $serviceResult->success = true;
            }
        } catch (Exception $e) {
            $serviceResult->success = false;
            $serviceResult->retry = true;
            $message = $e->getMessage();
            $msg  = "MandrillMailService: Customer: {$customer_id}  Communication: {$sendParams->communication_id} ";
            $msg .= " Exception Thrown By Mandrill: {$message}";
            Log::error($msg);
        }

        return ($serviceResult);
    }

    /**
     * Determine whether the request merge field delimiters are the same as those required
     * by Mandrill.  If not, then construct the merge field variables as required by Mandrill
     * and replace the existing merge field fields in the html_body, text_body and subject
     * with those required by Mandrill.
     *
     * @param array $sendParams
     */
    protected function fixupMergeFieldData($sendParams)
    {
        if (!empty($sendParams->merge_field_delimiters) &&
            !empty($sendParams->merge_field_delimiters['begin']) &&
            !empty($sendParams->merge_field_delimiters['end']) &&
            ($sendParams->merge_field_delimiters['begin'] != self::$mandrill_merge_field_delimiters['begin'] ||
                $sendParams->merge_field_delimiters['end'] != self::$mandrill_merge_field_delimiters['end'])
        ) {
            $this->processUnsubscribeLink($sendParams, $sendParams->html_body, $sendParams->merge_field_delimiters);

            $mvars = array();
            if (!empty($sendParams->global_merge_data)) {
                foreach ($sendParams->global_merge_data as $mdata) {
                    if (!empty($mdata['name'])) {
                        $mvars[] = $mdata['name'];
                    }
                }
            }
            if (!empty($sendParams->recipient_merge_vars)) {
                $mvars = array_merge($mvars, $sendParams->recipient_merge_vars);
            }

            if (count($mvars) > 0) {
                $sendParams->html_body = $this->updateMergeData(
                    $sendParams->html_body,
                    $mvars,
                    $sendParams->merge_field_delimiters,
                    self::$mandrill_merge_field_delimiters
                );
                $sendParams->text_body = $this->updateMergeData(
                    $sendParams->text_body,
                    $mvars,
                    $sendParams->merge_field_delimiters,
                    self::$mandrill_merge_field_delimiters
                );
                $sendParams->subject = $this->updateMergeData(
                    $sendParams->subject,
                    $mvars,
                    $sendParams->merge_field_delimiters,
                    self::$mandrill_merge_field_delimiters
                );
            }
        } else {
            $this->processUnsubscribeLink($sendParams, $sendParams->html_body, self::$mandrill_merge_field_delimiters);
        }
    }

    /**
     * Make sure the Unsubscribe Link exists and is set the URL to the Default Confirmation
     * page if one has not been supplied.
     *
     * @param array $sendParams
     * @param string $messageContent  Message Body that may Contain an Unsubscribe Link
     * @param array  $contentdelimiters  Merge Field Delimiters used in the Message Body
     */
    protected function processUnsubscribeLink($sendParams, &$messageContent, $contentDelimeters)
    {
        $globalMergeData = array();
        $unsubscribeLink = $this->defaultConfirmationLink;
        $unsubscribeLinkFound = false;
        if (!empty($sendParams->global_merge_data)) {
            foreach ($sendParams->global_merge_data as $mdata) {
                if (!empty($mdata['name'])) {
                    $name = $mdata['name'];
                    $value = empty($mdata['content']) ? '' : trim($mdata['content']);
                    if ($name == self::UNSUBSCRIBE_LINK_VARIABLE) {
                        if (!$unsubscribeLinkFound) {
                            // Should only at most one - but if more, Process 'First' and discard any others
                            $unsubscribeLinkFound = true;
                            if (!empty($value)) {
                                $unsubscribeLink = $value;
                            }
                        }
                    } else {
                        $globalMergeData[] = $mdata;
                    }
                }
            }
        }
        if ($unsubscribeLinkFound) {
            $sendParams->global_merge_data = $globalMergeData;
        }
        $this->replaceUnsubscribeLink($messageContent, $contentDelimeters, $unsubscribeLink);
    }

    /**
     * Update the Merge Fields in the content provided, replacing
     * the existing Merge field delimiters with the delimeters
     * required by the Mandrill service
     * @param  string $content
     * @param  array  $merge_vars
     * @param  array  $from_delimiters
     * @param  array  $to_delimiters
     * @return string modified content
     */
    protected function updateMergeData($content, array $merge_vars, array $from_delimiters, array $to_delimiters)
    {
        $varsFrom = array();
        $varsTo = array();
        foreach ($merge_vars as $var) {
            $varsFrom[] = $from_delimiters['begin'] . $var . $from_delimiters['end'];
            $varsTo[]   = $to_delimiters['begin'] . $var . $to_delimiters['end'];
        }
        $count = 0;
        $result = str_replace($varsFrom, $varsTo, $content, $count);
        return $result;
    }

    /**
     * Update Message Content to Replace the Unsubscribe Link
     *
     * @param string $messageContent  Message Body that may Contain an Unsubscribe Link
     * @param array  $contentdelimiters  Merge Field Delimiters used in the Message Body
     * @param array  $unsubscribeLink  Unsubscribe Confirmation Link
     */
    protected function replaceUnsubscribeLink(&$messageContent, $contentDelimeters, $unsubscribeLink) {
        $from = $contentDelimeters['begin']
            . self::UNSUBSCRIBE_LINK_VARIABLE
            . $contentDelimeters['end'];

        $to = self::$mandrill_merge_field_delimiters['begin']
            . self::MANDRILL_UNSUBSCRIBE_KEYWORD
            . $unsubscribeLink
            . self::$mandrill_merge_field_delimiters['end'];

        $count = 0;
        $messageContent = str_replace($from, $to, $messageContent, $count);
    }

    /**
     * If Individual SubAccounts Are In Effect, then
     * this function will check to see if a subaccount exists for
     * the provided customer_id, and is so return that subaccount id.
     * If not, then attempt will be made to create one and if successful, will return it.
     *
     * If the Individual Subaccount Option is Not in effect, or if a subaccount
     * doesn't exist and cannot be created, the function will return either
     * a Shared SubAccount or Null (no SubAccount for this Customer)
     *
     * @param string $customer_id
     * @return string the SubAccount ID to be used for this Customer or NULL
     */
    protected function prepareCustomerSubaccount($customer_id) {

        $sharedSubAccount = "sugartraining1";

        /*---------------------*/

        if (!empty($customer_id)) {
            if ($this->subaccountExists($customer_id)) {
                return $customer_id;
            }

            if (!is_null($this->addSubAccount($customer_id))) {
                return $customer_id;
            }
        }

        /*-----------------------*/

        return $sharedSubAccount;
    }

    /**
     * check to see if supplied SubAccount Already Exists
     * @param string $id subaccount ID
     * @return bool true if exists
     */
    protected function subaccountExists($id)
    {
        return !is_null($this->getSubAccount($id));
    }

    /**
     * Get Subaccount Info
     * @param string $id subaccount ID
     * @return array  Subaccount Info - null if not exists
     */
    protected function getSubAccount($id)
    {
        try {
            $subaccount = $this->mandrill->subaccounts->info($id);
            if (!empty($subaccount) && ($subaccount['id'] === $id)) {
                return $subaccount;
            }
        } catch (Exception $e) {
        }
        return null;
    }

    /**
     * Add Subaccount
     * @param string $id subaccount ID
     * @param string $name Name associated with SubAccount
     * @param string $notes Arbitrary string to store with Subaccount - Can Be JSON String
     * @return array Subaccount Info - null if unable to create
     */
    protected function addSubAccount($id, $name = null, $notes = null)
    {
        try {
            $subaccount = $this->mandrill->subaccounts->add($id, $name, $notes);
            if (!empty($subaccount) && ($subaccount['id'] === $id)) {
                return $subaccount;
            }
        } catch (Exception $e) {
        }
        return null;
    }
}
