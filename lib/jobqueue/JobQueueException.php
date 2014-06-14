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

class JobQueueException extends Exception
{
    const MissingArgument     = 1;
    const InvalidArgument     = 2;
    const UnexpectedOutcome   = 3;

    /**
     * @var array
     */
    static protected $errorMessageMappings = array(
        self::MissingArgument => 'JOBQUEUE_WRITE_EXCEPTION_ARGUMENT_MISSING',
        self::InvalidArgument => 'JOBQUEUE_WRITE_EXCEPTION_ARGUMENT_INVALID',
        self::UnexpectedOutcome => 'JOBQUEUE_STATUS_UPDATE_EXCEPTION_UNEXPECTED_OUTCOME',
    );

    /**
     * @return string
     */
    public function getLogMessage()
    {
        return "JobQueueException - @(" . basename($this->getFile()) . ":" . $this->getLine() . " [" . $this->getCode(
        ) . "]" . ") - " . $this->getMessage();
    }

    /**
     * @return array|string
     */
    public function getUserFriendlyMessage()
    {
        if (isset(self::$errorMessageMappings[$this->getCode()])) {
            $exception_code = self::$errorMessageMappings[$this->getCode()];
        }
        if (empty($exception_code)) {
            $exception_code = 'LBL_INTERNAL_ERROR'; //use generic message if a user-friendly version is not available
        }
        return translate($exception_code);
    }
}
