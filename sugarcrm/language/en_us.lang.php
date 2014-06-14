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

$app_strings = array (
    // Default SugarApiException error messages
    'EXCEPTION_UNKNOWN_EXCEPTION'       => 'Your request failed due to an unknown exception.',
    'EXCEPTION_FATAL_ERROR'             => 'Your request failed to complete.  A fatal error occurred.  Check logs for more details.',
    'EXCEPTION_NEED_LOGIN'              => 'You need to be logged in to perform this action.',
    'EXCEPTION_INVALID_TOKEN'           => 'Your authentication token is invalid.',
    'EXCEPTION_NOT_AUTHORIZED'          => 'You are not authorized to perform this action. Contact your administrator if you need access.',
    'EXCEPTION_INACTIVE_PORTAL_USER'    => 'You cannot access Portal because your portal account is inactive. Please contact customer support if you need access.',
    'EXCEPTION_PORTAL_NOT_CONFIGURED'   => 'Portal is not configured properly.  Contact your Portal Administrator for assistance.',
    'EXCEPTION_NO_METHOD'               => 'Your request was not supported. Could not find the HTTP method of your request for this path.',
    'EXCEPTION_NOT_FOUND'               => 'Your requested resource was not found.  Could not find a handler for the path specified in the request.',
    'EXCEPTION_MISSING_PARAMTER'        => 'A required parameter in your request was missing.',
    'EXCEPTION_INVALID_PARAMETER'       => 'A parameter in your request was invalid.',
    'EXCEPTION_REQUEST_FAILURE'         => 'Your request failed to complete.',
    'EXCEPTION_METADATA_OUT_OF_DATE'    => 'Your metadata or user hash did not match the server. Please resync your metadata.',
    'EXCEPTION_REQUEST_TOO_LARGE'       => 'Your request is too large to process.',
    'EXCEPTION_EDIT_CONFLICT'           => 'Edit conflict, please reload the record data.',
    'EXCEPTION_METADATA_CONFLICT'       => 'Metadata conflict, please reload the metadata.',
    'EXCEPTION_CLIENT_OUTDATED'         => 'Your software is out of date, please update your client before attempting to connect again.',
    'EXCEPTION_MAINTENANCE'             => 'SugarCRM is in maintenance mode. Only admins can login. Please contact your administrator for details.',

     // JobQueueException Error messages
    'JOBQUEUE_WRITE_EXCEPTION_ARGUMENT_MISSING' => 'JobQueue: WriteQueue Failed - Argument Missing',
    'JOBQUEUE_WRITE_EXCEPTION_ARGUMENT_INVALID' => 'JobQueue: WriteQueue Failed - Argument Invalid',
    'JOBQUEUE_STATUS_UPDATE_EXCEPTION_UNEXPECTED_OUTCOME' => 'JobQueue: Unexpected Outcome when attempting to Update Job Status',

    // DatabaseException Error messages
    'DATABASE_EXCEPTION_PREPARE_FAILED'         => 'Database: Prepare Failed',
    'DATABASE_EXCEPTION_BIND_PARAMETERS_FAILED' => 'Database: BindParameters Failed',
    'DATABASE_EXCEPTION_EXECUTE_FAILED'         => 'Database: Execute Failed',
    'DATABASE_EXCEPTION_FETCH_RESULT_FAILED'    => 'Database: FetchResult Failed',
    'DATABASE_EXCEPTION_CONNECTION_FAILED'      => 'Database: Connection Failed',
    'DATABASE_EXCEPTION_STATEMENT_INVALID'      => 'Database: Statement Invalid',
    'DATABASE_EXCEPTION_UNEXPECTED_OUTCOME'     => 'Database: Unexpected Outcome From Insert or Update',

    // Other Miscellaneous Transalatable Messages
    'LBL_INTERNAL_ERROR'                        => 'An Internal System Error Occurred',
);
