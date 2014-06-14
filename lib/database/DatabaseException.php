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

class DatabaseException extends Exception
{
    const PrepareFailed         = 1;
    const BindParametersFailed  = 2;
    const ExecuteFailed         = 3;
    const FetchResultFailed     = 4;
    const ConnectionFailed      = 5;
    const StatementInvalid      = 6;
    const QueryFailed           = 7;
    const UnexpectedOutcome     = 8;

    /**
     * @var array
     */
    static protected $errorMessageMappings = array(
        self::PrepareFailed => 'DATABASE_EXCEPTION_PREPARE_FAILED',
        self::BindParametersFailed => 'DATABASE_EXCEPTION_BIND_PARAMETERS_FAILED',
        self::ExecuteFailed => 'DATABASE_EXCEPTION_EXECUTE_FAILED',
        self::FetchResultFailed => 'DATABASE_EXCEPTION_FETCH_RESULT_FAILED',
        self::ConnectionFailed => 'DATABASE_EXCEPTION_CONNECTION_FAILED',
        self::StatementInvalid => 'DATABASE_EXCEPTION_STATEMENT_INVALID',
        self::QueryFailed => 'DATABASE_EXCEPTION_QUERY_FAILED',
        self::UnexpectedOutcome => 'DATABASE_EXCEPTION_UNEXPECTED_OUTCOME',
    );

    /**
     * @return string
     */
    public function getLogMessage()
    {
        return "DatabaseException - @(" . basename($this->getFile()) . ":" . $this->getLine() . " [" . $this->getCode(
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
