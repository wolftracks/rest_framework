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

class SugarException extends Exception
{
    public $errorLabel = 'unknown_exception';
    public $messageLabel = 'EXCEPTION_UNKNOWN_EXCEPTION';
    public $msgArgs = null;

    /**
     * Extra data attached to the exception
     * @var array
     */
    public $extraData = array();

    /**
     * @param string $messageLabel optional Label for error message.  Used to load the appropriate translated message.
     * @param array $msgArgs optional set of arguments to substitute into error message string
     *  $messageLabel is in app strings.
     * @param string $errorLabel
     */
    public function __construct($messageLabel = null, $msgArgs = null, $errorLabel = null)
    {

        if (!empty($messageLabel)) {
            $this->messageLabel = $messageLabel;
        }

        if (!empty($errorLabel)) {
            $this->errorLabel = $errorLabel;
        }

        if (!empty($msgArgs)) {
            $this->msgArgs = $msgArgs;
        }

        $this->setMessage($this->messageLabel, $this->msgArgs);

        parent::__construct($this->message);
    }

    /**
     * Each Sugar API exception should have a unique label that clients can use to identify which
     * Sugar API exception was thrown.
     *
     * @return null|string Unique error label
     */
    public function getErrorLabel()
    {
        return $this->errorLabel;
    }

    /**
     * Sets the user locale appropriate message that is suitable for clients to display to end users.
     * Message is based upon the message label provided when this SugarApiException was constructed.
     *
     * If the message label isn't found in app_strings, we'll use the label itself as the message.
     *
     * @param string $messageLabel required Label for error message.  Used to load the appropriate translated message.
     * @param array $msgArgs optional set of arguments to substitute into error message string
     */
    public function setMessage($messageLabel, $msgArgs = null)
    {
        // If no message label, don't bother looking it up
        if (empty($messageLabel)) {
            $this->message = null;
            return;
        }
        $message = translate($messageLabel);

        // If no arguments provided, return message.
        // If there are arguments, insert into message then return formatted message
        if (empty($msgArgs)) {
            $this->message = $message;
        } else {
            $this->message = string_format($message, $msgArgs);
        }
    }

    /**
     * Set exception extra data
     * @param string $key
     * @param mixed $data
     * @return SugarApiException
     */
    public function setExtraData($key, $data)
    {
        $this->extraData[$key] = $data;
        return $this;
    }

}
