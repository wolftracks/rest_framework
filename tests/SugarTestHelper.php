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

if(!defined('sugarEntry')) define('sugarEntry', true);

include_once("../lib/env/entrypoint.php");
define('ENTRY_POINT_TYPE', 'test');

$GLOBALS['logger_file_name'] = "sugartest.log";

set_include_path(
    dirname(__FILE__) . PATH_SEPARATOR .
        dirname(__FILE__) . '/..' . PATH_SEPARATOR .
        get_include_path()
);


require_once("lib/exception/SugarException.php");
require_once("lib/exception/SugarApiException.php");
require_once("lib/rest/RestService.php");
require_once("lib/rest/SugarApi.php");


// initialize the various globals we use
global $dbm;
$dbm = DBManagerFactory::getDatabaseManager();
if (empty($dbm)) {
    throw new SugarException('Unable to connect to Database - SugarTestHelper terminating ...');
}


// constant to indicate that we are running tests
if (!defined('SUGAR_PHPUNIT_RUNNER'))
    define('SUGAR_PHPUNIT_RUNNER', true);

if ( !isset($_SERVER['HTTP_USER_AGENT']) )
    // we are probably running tests from the command line
    $_SERVER['HTTP_USER_AGENT'] = 'cli';

if ( !isset($_SERVER['SERVER_SOFTWARE']) )
    $_SERVER["SERVER_SOFTWARE"] = 'PHPUnit';

// helps silence the license checking when running unit tests.
$_SESSION['VALIDATION_EXPIRES_IN'] = 'valid';

$GLOBALS['startTime'] = microtime(true);



class Sugar_PHPUnit_Framework_TestCase extends PHPUnit_Framework_TestCase
{

    /**
     * Assert that two arrays are equal. This helper method will sort the two arrays before comparing them.
     * @param array $expected the expected array
     * @param array $actual the actual array
     * @param string assert Message
     */
    protected function assertArraysEqual(array $expected, array $actual, $message) {
        $result1 = $this->array_diff_assoc_recursive($expected, $actual);
        $result2 = $this->array_diff_assoc_recursive($actual, $expected);
        $match = empty($result1) && empty($result2);
        if ($match) {
            $this->assertTrue($match, $message);
        } else {
            try {
                $this->assertEquals($expected, $actual, $message);
            } catch (PHPUnit_Framework_ExpectationFailedException $e) {
                $messageSeparator  = "@@@@@@";
                $msg  = $e->getMessage();
                if (!empty($result1)) {
                    $msg  .= "\n" . $messageSeparator . " In Array (Expected) - Not in Array (Actual)" . "\n";
                    // $msg  .= var_export($result1,true);
                    $msg  .= print_r($result1,true);
                }
                if (!empty($result2)) {
                    $msg  .= "\n" . $messageSeparator . " In Array (Actual)   - Not in Array (Expected)" . "\n";
                    // $msg  .= var_export($result2,true);
                    $msg  .= print_r($result2,true);
                }
                $comparison = new PHPUnit_Framework_ComparisonFailure('','','','');
                throw new PHPUnit_Framework_ExpectationFailedException($msg,$comparison);
            }
        }
    }

    private function array_diff_assoc_recursive($array1, $array2)
    {
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key])) {
                    $difference[$key] = $value;
                } elseif (!is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = $this->array_diff_assoc_recursive($value, $array2[$key]);
                    if ($new_diff != false) {
                        $difference[$key] = $new_diff;
                    }
                }
            } else {
                if( !array_key_exists($key,$array2) || $array2[$key] != $value) {
                    $difference[$key] = $value;
                }
            }
        }
        return !isset($difference) ? array() : $difference;
    }

}


// -------- include the other test tools
require_once 'tests/SugarTestRestApi.php';


