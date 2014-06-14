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

require_once 'lib/exception/SugarException.php';

class SugarApiException extends SugarException
{
    public $httpCode = 400;

    /**
     * @param string $messageLabel optional Label for error message.  Used to load the appropriate translated message.
     * @param array $msgArgs optional set of arguments to substitute into error message string
     * @param int $httpCode
     * @param string $errorLabel
     */
    public function __construct($messageLabel = null, $msgArgs = null, $httpCode = 0, $errorLabel = null)
    {

        if ($httpCode != 0) {
            $this->httpCode = $httpCode;
        }
        parent::__construct($messageLabel, $msgArgs,$errorLabel);
    }

    public function getHttpCode()
    {
        return $this->httpCode;
    }
}
/**
 * General error, no specific cause known.
 */
class SugarApiExceptionError extends SugarApiException
{
    public $httpCode = 500;
    public $errorLabel = 'fatal_error';
    public $messageLabel = 'EXCEPTION_FATAL_ERROR';
}

/**
 * Incorrect API version
 */
class SugarApiExceptionIncorrectVersion extends SugarApiException
{
    public $httpCode = 301;
    public $errorLabel = 'incorrect_version';
    public $messageLabel = 'EXCEPTION_INCORRECT_VERSION';
}

/**
 * Token not supplied or token supplied is invalid.
 * The client should display the username and password screen
 */
class SugarApiExceptionNeedLogin extends SugarApiException
{
    public $httpCode = 401;
    public $errorLabel = 'need_login';
    public $messageLabel = 'EXCEPTION_NEED_LOGIN';
}

/**
 * The user's session is invalid
 * The client should get a new token and retry.
 */
class SugarApiExceptionInvalidGrant extends SugarApiException
{
    public $httpCode = 401;
    public $errorLabel = 'invalid_grant';
    public $messageLabel = 'EXCEPTION_INVALID_TOKEN';
}

/**
 * This action is not allowed for this user.
 */
class SugarApiExceptionNotAuthorized extends SugarApiException
{
    public $httpCode = 403;
    public $errorLabel = 'not_authorized';
    public $messageLabel = 'EXCEPTION_NOT_AUTHORIZED';
}

/**
 * URL does not resolve into a valid REST API method.
 */
class SugarApiExceptionNoMethod extends SugarApiException
{
    public $httpCode = 404;
    public $errorLabel = 'no_method';
    public $messageLabel = 'EXCEPTION_NO_METHOD';
}
/**
 * Resource specified by the URL does not exist.
 */
class SugarApiExceptionNotFound extends SugarApiException
{
    public $httpCode = 404;
    public $errorLabel = 'not_found';
    public $messageLabel = 'EXCEPTION_NOT_FOUND';
}

class SugarApiExceptionRequestTooLarge extends SugarApiException
{
    public $httpCode = 413;
    public $errorLabel = 'request_too_large';
    public $messageLabel = 'EXCEPTION_REQUEST_TOO_LARGE';
}
/**
 * One of the required parameters for the request is missing.
 */
class SugarApiExceptionMissingParameter extends SugarApiException
{
    public $httpCode = 422;
    public $errorLabel = 'missing_parameter';
    public $messageLabel = 'EXCEPTION_MISSING_PARAMTER';
}
/**
 * One of the required parameters for the request is incorrect.
 */
class SugarApiExceptionInvalidParameter extends SugarApiException
{
    public $httpCode = 422;
    public $errorLabel = 'invalid_parameter';
    public $messageLabel = 'EXCEPTION_INVALID_PARAMETER';
}
/**
 * The API method is unable to process parameters due to some of them being wrong.
 */
class SugarApiExceptionRequestMethodFailure extends SugarApiException
{
    public $httpCode = 424;
    public $errorLabel = 'request_failure';
    public $messageLabel = 'EXCEPTION_REQUEST_FAILURE';
}

/**
 * The client is out of date for this version
 */
class SugarApiExceptionClientOutdated extends SugarApiException
{
    public $httpCode = 433;
    public $errorLabel = 'client_outdated';
    public $messageLabel = 'EXCEPTION_CLIENT_OUTDATED';
}

/**
 * We're in the maintenance mode
 */
class SugarApiExceptionMaintenance extends SugarApiException
{
    public $httpCode = 503;
    public $errorLabel = 'maintenance';
    public $messageLabel = 'EXCEPTION_MAINTENANCE';
}
