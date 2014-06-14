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

require_once('lib/database/DatabaseException.php');
require_once('lib/database/DBManager.php');

class DBManagerFactory
{
    /**
     * @var Database Manager
     */
    protected static $dbm;

    /**
     * Get Database Manager configured for this instance
     * Configured Database Manager is loaded if needed
     * @param  bool $reset
     * @return $dbm Database Manager
     */
    public static function getDatabaseManager($reset = false)
    {
        if ($reset) {
            self::reset();
        }
        if (empty(self::$dbm)) {
            $dbConfig = SugarConfig::getDatabaseConfiguration();
            $dbmClassName = $dbConfig['dbManagerClassName'];
            $dbmClassPath = $dbConfig['dbManagerClassPath'];
            $dbmFileName  = "{$dbmClassPath}/{$dbmClassName}.php";
            if (file_exists($dbmFileName)) {
                include_once($dbmFileName);
            }
            self::$dbm = SugarClassLoader::getInstance($dbmClassName);
            self::$dbm->dbConfig = $dbConfig;
            self::$dbm->connect();
        }
        return self::$dbm;
    }

    /**
     * Reset Database Manager and Connection Handle
     */
    protected static function reset()
    {
        self::$dbm = null;
    }
}
