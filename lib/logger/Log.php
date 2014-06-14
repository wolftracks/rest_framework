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

// Insert the path where you unpacked log4php
require_once "vendor/autoload.php";

// Tell log4php to use our configuration file.
//Logger::configure('../vendor/log4php/config.xml');
Logger::configure("lib/logger/config.php");


/**
 * This is a classic usage pattern: one logger object per class.
 */
class Log 
{
    /** Holds the Logger. */
    private static $log;

	private static function getLogger()
    {
        if (!self::$log) {
            self::$log = Logger::getLogger(__CLASS__);
        }
		return self::$log;
    }

    /** Logger can be used from any member method. */
    public static function trace($message)
    {
        self::getLogger()->trace($message);
    }

    public static function debug($message)
    {
        self::getLogger()->debug($message);
    }

    public static function info($message)
    {
        self::getLogger()->info($message);
    }

    public static function warn($message)
    {
        self::getLogger()->warn($message);
    }

    public static function error($message)
    {
        self::getLogger()->error($message);
    }

    public static function fatal($message)
    {
        self::getLogger()->fatal($message);
    }
}

