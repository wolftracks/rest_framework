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

function directoryEndsWith($findPath, $dir) {
    $rootDir = $dir;
    $pos = strpos($dir, $findPath);
    if ($pos !== false) { 
        $rootDir = substr($dir, 0, $pos+strlen($findPath));
    }
    return $rootDir; 
}

if (empty($GLOBALS['logger_file_name'])) {
    $loggerFileName = 'sugarcrm.log';
} else {
    $loggerFileName = $GLOBALS['logger_file_name'];
}

$rootDir = directoryEndsWith('/vendor/', __DIR__) . '..';

// var_dump($rootDir);
// $f =   "{$rootDir}/log/{$loggerFileName}";
// var_dump($f);
// exit;

return array(
    'rootLogger' => array(
	    'appenders' => array('default'),
    ),
    'appenders' => array(
        'default' => array(
            'class' => 'LoggerAppenderFile',
            'layout' => array(
                'class' => 'LoggerLayoutPattern',
                'params' => array(
                    'conversionPattern' => '%date [%level] %message%newline',
                ),
            ),
            'params' => array(
                'file' => "{$rootDir}/log/{$loggerFileName}",
                'append' => true,
            ),
        ),
    ),
);
