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

chdir(dirname(__FILE__).'/../../');

require_once("vendor/autoload.php");
require_once("lib/env/SugarConfig.php");
require_once("lib/logger/Log.php");
require_once("lib/utils/utils.php");
require_once("lib/utils/SugarClassLoader.php");
require_once("lib/exception/SugarException.php");
require_once("lib/database/DBManagerFactory.php");

