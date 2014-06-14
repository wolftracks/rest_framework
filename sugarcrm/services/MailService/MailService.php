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

/**
 * This class is the Abstract Base Class for the Sugar Mail Services that will
 * interact with the various Third Party Mail Service Providers
 *
 * @interface
 */
abstract class MailService
{
    protected $dbm;
    protected $coreUrl;

    function __construct() {
        $this->coreUrl = SugarConfig::get('site.core_url');

        $this->dbm = DBManagerFactory::getDatabaseManager();
        $this->dbm->connect();
    }

    /**
     * @abstract
     * @access public
     * @param string $api_user required
     * @param string $api_pass required
     */
    abstract public function setServiceAccountInfo($api_user, $api_pass);

    /**
     * @abstract
     * @access public
     * @param string $customer_id required
     * @param MailServiceSendParameters $sendParams required
     */
    abstract public function send($customer_id, MailServiceSendParameters $sendParams);

}
