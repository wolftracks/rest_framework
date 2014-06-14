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

class RestEndpoints
{

    /**
     * This is the Map of Endpoints to ClassName(s)
     * implementing those Endpoints
     *
     * Each Table Entry represents an Endpoint Map
     * for each supported REST API version
     *
     * The Key for each Endpoint map consists
     * of a "REQUEST_METHOD : endpoint"
     *
     * The entries in the Map are scanned in the order
     * specified for a match. A "*" can be used to mean
     * "Any Request Method"
     *
     * Valid Keys include:
     *    'GET:myservice'
     *    'POST:myservice'
     *    '*:myservice'
     */

    protected static $map = array(
        "v10" => array(
            "*:account" => "AccountServiceApi",
            "*:email" => "MailServiceApi",
            "*:imagestore" => "ImageStoreServiceApi",
            "*:imagecapture" => "ImageCaptureServiceApi",
        ),
    );

    /**
     * Returns the value of a config variable
     * @param string $version e.g. 'v10'
     */
    public static function getMap($version)
    {
        if (isset(self::$map[$version])) {
            return self::$map[$version];
        }
        return null; // Version Invalid
    }
}
