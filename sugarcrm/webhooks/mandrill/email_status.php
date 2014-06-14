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

include_once("../../../lib/env/entrypoint.php");
define('ENTRY_POINT_TYPE', 'event');

require_once("sugarcrm/services/TrackingService/MandrillMailTrackingService.php");

if (!empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD']=='POST' && !empty($_REQUEST['mandrill_events'])) {
    $mandrillMailTracking = new MandrillMailTrackingService();

    $postdata = $_REQUEST['mandrill_events'];
    $json = urldecode($postdata);
    $data = json_decode($json,true);

    if (is_array($data) && count($data) > 0) {
        foreach($data AS $event) {
            $mandrillMailTracking->process_event($event);
        }
    }

    $results = array(
        'results' => true
    );

    echo json_encode($results);
}
exit;
