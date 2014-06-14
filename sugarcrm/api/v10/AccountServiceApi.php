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


class AccountServiceApi extends SugarApi
{
    public function registerApiRest()
    {
        $api = array(
            'getAccountStatus' => array(
                'reqType' => 'GET',
                'path' => array('account','status'),
                'pathVars' => array(),
                'method' => 'getAccountStatus',
            ),
            'listAccounts' => array(
                'reqType' => 'GET',
                'path' => array('account'),
                'pathVars' => array(),
                'method' => 'listAccounts',
            ),
        );

        return $api;
    }

    /**
     * get Account Activation Status
     *
     * @param ServiceBase $api
     * @return array
     */
    public function getAccountStatus(ServiceBase $api, $params)
    {
       $result = array (
           "account_id" => $this->customer_id,
           "credentials" => $this->credentials,
           "status" => "active"
       );
       return $result;
    }

    /**
     * get Account Activation Status
     *
     * @param ServiceBase $api
     * @return array
     */
    public function listAccounts(ServiceBase $api, $params)
    {
        $dbm = DBManagerFactory::getDatabaseManager();
        $dbm->connect();

        $limit = 500;
        $orderBy = "api_user";
        $dir = "ASC";

        if (!empty($params['limit'])) {
            $limit = $params['limit'];
        }
        if (!empty($params['order'])) {
            $orderBy = $params['order'];
        }
        if (!empty($params['dir'])) {
            $dir = $params['dir'];
        }

        $rows = array();

        try {
            $sql  = "SELECT * FROM client";
            $sql .= " ORDER by $orderBy $dir LIMIT $limit";
            $result = $dbm->query($sql);
            if ($result) {
                while ($row = $dbm->fetchAssoc($result)) {
                    $rows[] = $row;
                }
                $dbm->freeQueryResult($result);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage()); // Do Not Return - Must Release Locks
        }

        return $rows;
    }
}
