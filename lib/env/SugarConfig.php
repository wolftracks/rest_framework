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

class SugarConfig
{
    const CONFIG_INI_FILE = "lib/env/config.ini";

    protected static $configData = null;

    /* ---- DEFAULT CONFIG VALUES -------*/
    private static $defaultConfigData = array(
        /* These would be Configutation Variable Settings that are not required to appear
           in the external ini file, but will be overridden by the ini file if they do appear.
        */

        /*-- No Default Configuration Data --*/
    );

    /** --- FOR TESTING ONLY ---
     * Set value of a config variable
     * @param string $key
     * @param string $value
     */
    public static function _set($key, $value)
    {
        self::init();
        self::$configData[$key] = $value;
    }

    /** --- FOR TESTING ONLY ---
     * Set All Configuration Data
     * @param array Configuration Data
     */
    public static function _setConfigData($configData)
    {
        self::$configData = $configData;
    }

    /**
     * Returns the value of a config variable
     * @param string $key
     * @param string $defaultValue
     * @return string
     */
    public static function get($key, $defaultValue=null)
    {
        self::init();
        if (isset(self::$configData[$key])) {
            return self::$configData[$key];
        }
        return $defaultValue;
    }

    /**
     * Set All Configuration Data
     * @param array Configuration Data
     */
    public static function getConfigData()
    {
        self::init();
        return self::$configData;
    }

    /**
     * Returns the current ESP Account Info
     * @return array
     */
    public static function getEmailServiceProvider($providerName=null)
    {
        self::init();
        if (!empty($providerName)) {
            $provider = $providerName;
        } else {
            $provider = self::$configData['esp.provider_name'];
        }
        return array(
            "provider_name" => $provider,
            "account_id"    => self::$configData[$provider . '.account_id'],
            "account_password" => self::$configData[$provider . '.account_password'],
            "max_send_size" => self::$configData[$provider . '.max_send_size']
        );
    }

    /**
     * Returns the current Database Configuration
     * @return array
     */
    public static function getDatabaseConfiguration()
    {
        /* Extract Database Entities From Configuration */
        self::init();
        return array(
            "dbManagerClassPath" => self::$configData['database.dbManagerClassPath'],
            "dbManagerClassName" => self::$configData['database.dbManagerClassName'],
            "dbhost" => self::$configData['database.dbhost'],
            "dbname" => self::$configData['database.dbname'],
            "dbuser" => self::$configData['database.dbuser'],
            "dbpassword" => self::$configData['database.dbpassword']
        );
    }

    /**
     * Returns the current ImageStore Configuration
     * @return array
     */
    public static function getImageStoreConfiguration()
    {
        /* Extract Image Store Entities From Configuration */
        self::init();
        return array(
            "account" => self::$configData['s3imagestore.account'],
            "access_key" => self::$configData['s3imagestore.access_key'],
            "secret_key" => self::$configData['s3imagestore.secret_key'],
            "bucket" => self::$configData['s3imagestore.bucket'],
        );
    }

    /**
     * Initialize Config Data by starting with the Default Config
     * and merging the configuration from the config.ini file
     * Values in the ini file trump the default values where there are key matches.
     */
    protected static function init()
    {
        if (self::$configData === null) {
            self::$configData = self::$defaultConfigData;
            $iniConfig = self::loadIniFile();
            if (!empty($iniConfig)) {
                self::$configData = array_merge(self::$configData, $iniConfig);
            }
        }
    }

    /**
     * Gets config from ini file
     */
    protected static function loadIniFile()
    {
        $configData = array();

        $groups = parse_ini_file(self::CONFIG_INI_FILE, true);
        foreach ($groups as $groupName => $groupVars) {
            if (is_array($groupVars)) {
                $group = $groupVars;
                $groupKey = $groupName . '.';
            } else {
                $group = array($groupName => $groupVars);
                $groupKey = '';
            }

            foreach ($group as $sKey => $sValue) {
                $configData[$groupKey . $sKey] = $sValue;
            }
        }
        return $configData;
    }
}
