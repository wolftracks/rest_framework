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

class SugarClassLoader
{
    /**
     * Useful for injecting Test Mock Class Instances into Test Environment
     * @param array $mockInstances - Class to Instance Map
     */
    private static $mockInstances = array();

    /**
     * @param array - Well Known Include Directories
     */
    private static $dirMap = array(
        "lib/",
    );

    /**
     * @return array $classMap - Class to filename map
     */
    private static function getClassMap()
    {
        return array(
            "JobQueue"  =>  SugarConfig::get('jobqueue.path'),
        );
    }

    public static function autoload($className)
    {
        $classMap = self::getClassMap();
        if (!empty($classMap[$className])) {
            $file = $classMap[$className];
            if (file_exists($file)) {
                // printf("Autload ClassMap - Class: %s  File: %s\n", $className, $file);
                include_once("$file");
                return true;
            }
        }
        foreach(self::$dirMap as $dir) {
            if (file_exists("{$dir}$className.php")) {
                include_once("{$dir}$className.php");
                return true;
            }
        }
        return false;
    }


    public static function getInstance($className)
    {
        if (!empty(self::$mockInstances[$className])) {
            return self::$mockInstances[$className];
        }
        return new $className();
    }

    public static function addMockInstance($className, $obj)
    {
        self::$mockInstances[$className] = $obj;
    }

    public static function removeMockInstance($className)
    {
        unset(self::$mockInstances[$className]);
    }

    public static function clearMockInstances()
    {
        self::$mockInstances = array();
    }

}

spl_autoload_register(array('SugarClassLoader', 'autoload'));

