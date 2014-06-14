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

class ServiceResult
{
    public $success = false;
    public $retry = false;
    public $recipients_attempted = 0;
    public $recipients_accepted = 0;
    public $provider_response = array();

    public function setProviderResponse($provider_response)
    {
        $this->provider_response = $provider_response;
    }

    public function getProviderResponse()
    {
        return $this->provider_response;
    }

    public function setRecipientsAccepted($recipients_accepted)
    {
        $this->recipients_accepted = $recipients_accepted;
    }

    public function getRecipientsAccepted()
    {
        return $this->recipients_accepted;
    }

    public function setRecipientsAttempted($recipients_attempted)
    {
        $this->recipients_attempted = $recipients_attempted;
    }

    public function getRecipientsAttempted()
    {
        return $this->recipients_attempted;
    }

    public function setRetry($retry)
    {
        $this->retry = $retry;
    }

    public function getRetry()
    {
        return $this->retry;
    }

    public function setSuccess($success)
    {
        $this->success = $success;
    }

    public function getSuccess()
    {
        return $this->success;
    }
}
