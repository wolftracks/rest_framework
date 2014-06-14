<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * By installing or using this file, you are confirming on behalf of the entity
 * subscribed to the SugarCRM Inc. product ("Company") that Company is bound by
 * the SugarCRM Inc. Master Subscription Agreement ("MSA"), which is viewable at:
 * http://www.sugarcrm.com/master-subscription-agreement
 *
 * If Company is not bound by the MSA, then by installing or using this file
 * you are agreeing unconditionally that Company will be bound by the MSA and
 * certifying that you have authority to bind Company accordingly.
 *
 * Copyright (C) 2004-2013 SugarCRM Inc.  All rights reserved.
 ********************************************************************************/

abstract class ServiceBase {
    public $user;
    public $platform = 'base';
    public $action = 'view';

    abstract public function execute();
    abstract protected function handleException(Exception $exception);

    protected function loadServiceDictionary($dictionaryName) {
    }

    protected function loadApiClass($route) {
        $apiClassName = $route['className'];
        $apiClass =  SugarClassLoader::getInstance($apiClassName);
        return $apiClass;
    }

    /**
     * This function loads various items needed to setup the user's environment (such as app_strings and app_list_strings)
     */
    protected function loadUserEnvironment()
    {
        //global $current_user, $current_language;
        //$current_language = $GLOBALS['sugar_config']['default_language'];
        //$GLOBALS['app_strings'] = return_application_language($current_language);
    }

    /**
     * This function loads various items when the user is not logged in
     */
    protected function loadGuestEnvironment()
    {
        //global $current_user, $current_language;
        //$current_language = $GLOBALS['sugar_config']['default_language'];
        //$GLOBALS['app_strings'] = return_application_language($current_language);
    }

    /**
     * Set a response header
     * @param string $header
     * @param string $info
     * @return bool
     */
    public function setHeader($header, $info)
    {
        // do nothing in base class
        return $this;
    }

    /**
     * Generate suitable ETag for content
     *
     * This function generates the necessary cache headers for using ETags with dynamic content. You
     * simply have to generate the ETag, pass it in, and the function handles the rest.
     *
     * @param string $etag ETag to use for this content.
     * @return bool Did we have a match?
     */
    public function generateETagHeader()
    {
        // do nothing in base class
        return false;
    }

    /**
     * Set response to be read from file
     */
    public function fileResponse($filename)
    {
        return false;
    }

    /**
     * Release session data
     * Keeps $_SESSION but it's no longer preserved after the end of the request
     */
    protected function releaseSession()
    {
    }

    /**
     * Handle the situation where the API needs login
     * @param Exception $e Exception that caused the login problem, if any
     * @throws SugarApiExceptionNeedLogin
     */
    public function needLogin(Exception $e = null)
    {
    }

    /**
     * Capture PHP error output and handle it
     *
     * @param string $errorType The error type to hand down through the exception (default: 'php_error')
     * @throw SugarApiExceptionError
     */
    public function handleErrorOutput($errorType = 'php_error')
    {
        if (ob_get_level() > 0 && ob_get_length() > 0) {
            // Looks like something errored out first
            $errorOutput = ob_get_clean();
            Log::error("A PHP error occurred:\n".$errorOutput);
            $e = new SugarApiExceptionError();
            $e->errorLabel = $errorType;
            if (SugarConfig::get('admin.developer_mode') == '1') {
                $e->setExtraData('error_output',$errorOutput);
            }
            throw $e;
        }
    }
}
