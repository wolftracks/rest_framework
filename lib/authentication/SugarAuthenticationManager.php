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

class SugarAuthenticationManager
{
    private static $authentication_url = "https://authenticate.sugarcrm.com/rest/subscription/";

    /**
     * Handles Customer Account Authentication
     * @param string $customer_key
     */
    public function authenticate($customer_key) {
        $url = self::$authentication_url . $customer_key;
        $success = self::callAuthenticationService($url);
        return $success;
    }

    /**
     * This method invokes the Sugar Account License server to determine
     * whether there is a Valid and Active account for the customer
     * identified by the supplied  customer_key
     * @param string $customer_key
     * @return boolean $success
     */
    protected function callAuthenticationService($url)
    {
        $handle = curl_init();
        $headers = array();
        $headers[] = 'Expect:';
        $headers[] = 'Accept: application/json';

        curl_setopt($handle, CURLOPT_URL, $url);
        if (count($headers) > 0) {
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_HEADER, true);

        $result = curl_exec($handle);
        list($response_headers, $response) = explode("\r\n\r\n", $result, 2);

        $response = json_decode($response, true);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        $success = !empty($response['success']);
        return $success;
    }

}
