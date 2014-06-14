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

abstract class DBManager
{
    const MYSQL_CODE_DUPLICATE_KEY = 1062;
    const MYSQL_CODE_RECORD_NOT_FOUND = 1032;
    /**
     * Name of database
     * @var resource
     */
    public $dbConfig = null;

    /**
     * Connects to the database backend
     *
     * Will open a persistent or non-persistent connection.
     */
    abstract public function connect();

    /**
     * Parses and runs queries
     *
     * @param  string   $sql        SQL Statement to execute
     * @return resource|bool result set or success/failure bool
     */
    abstract public function query($sql);
}
