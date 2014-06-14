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
class HttpClient
{
    const DELETE = 'DELETE';
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';

    /**
     * This method uses Curl to invoke the Cloud service
     * @param $method
     * @param $url   - Fully Qualified
     * @param mixed $data - will be json_encoded on post/put if not null
     * @return array
     */
    public function callResource($method, $url, $data = null)
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
        switch ($method) {
            case static::POST:
                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case static::PUT:
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case static::DELETE:
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($data)) {
                    curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
        }

        $result = curl_exec($handle);
        list($responseHeaders, $raw) = explode("\r\n\r\n", $result, 2);
        $response = json_decode($raw, true);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        return array(
            'code' => $code,
            'data' => $response,
            'raw' => $raw,
            'response_headers' => $responseHeaders,
        );
    }
}
