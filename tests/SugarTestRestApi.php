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

class SugarTestRestApi extends Sugar_PHPUnit_Framework_TestCase
{
    public  static $json_encoding = false;

    protected $http_status = false;

    protected $server;
    protected $request;
    protected $get_args;
    protected $post_args;

    /**
     * Make GET Rest Call
     * @param array  extraheaders
     * @return mixed $result Array or Json String  depending on $json_encoding setting
     */
    protected function restapi_get($uri, $extraheaders=array()) {
        ob_start();
        $this->restapi_init('GET', $uri, $extraheaders);
        $service = new RestService($this->server, $this->request, $this->get_args, array());
        $service->execute();
        $response = ob_get_clean();
        $serviceResponse = $service->getResponse();
        $this->http_status = $serviceResponse->getStatus();
        if (self::$json_encoding === 'auto' && (!empty($response)) && is_string($response)) {
            $result = json_decode($response, true);
        } else {
            $result = $response;
        }
        return($result);
    }


    /**
     * Make POST Rest Call
     * @param string $uri  e.g. '/email/send'
     * @param mixed  $input  Array or Json String  depending on $json_encoding setting
     * @param array  extraheaders
     * @return mixed $result Array or Json String  depending on $json_encoding setting
     */
    protected function restapi_post($uri='', $input, $extraheaders=array()) {
        ob_start();
        if (self::$json_encoding === 'auto') {
            $json = json_encode($input);
        } else {
            $json = $input;
        }
        $this->restapi_init('POST', $uri, $extraheaders);
        $service = new RestService($this->server, $this->request, $this->get_args, array(), $json);
        $service->execute();
        $response = ob_get_clean();
        $serviceResponse = $service->getResponse();
        $this->http_status = $serviceResponse->getStatus();
        if (self::$json_encoding === 'auto' && (!empty($response)) && is_string($response)) {
            $result = json_decode($response, true);
        } else {
            $result = $response;
        }
        return($result);
    }

    /**
     * Make PUT Rest Call
     * @param string $uri  e.g. '/email/send'
     * @param mixed  $input  Array or Json String  depending on $json_encoding setting
     * @param array  extraheaders
     * @return mixed $result Array or Json String  depending on $json_encoding setting
     */
    protected function restapi_put($uri='', $input, $extraheaders=array()) {
        ob_start();
        if (self::$json_encoding === 'auto') {
            $json = json_encode($input);
        } else {
            $json = $input;
        }
        $this->restapi_init('PUT', $uri, $extraheaders);
        $service = new RestService($this->server, $this->request, $this->get_args, array(), $json);
        $service->execute();
        $response = ob_get_clean();
        $serviceResponse = $service->getResponse();
        $this->http_status = $serviceResponse->getStatus();
        if (self::$json_encoding === 'auto' && (!empty($response)) && is_string($response)) {
            $result = json_decode($response, true);
        } else {
            $result = $response;
        }
        return($result);
    }


    /**
     * Make DELETE Rest Call
     * @param array  extraheaders
     * @return mixed $result Array or Json String  depending on $json_encoding setting
     */
    protected function restapi_delete($uri, $extraheaders=array()) {
        ob_start();
        $this->restapi_init('DELETE', $uri, $extraheaders);
        $service = new RestService($this->server, $this->request, $this->get_args, array());
        $service->execute();
        $response = ob_get_clean();
        $serviceResponse = $service->getResponse();
        $this->http_status = $serviceResponse->getStatus();
        if (self::$json_encoding === 'auto' && (!empty($response)) && is_string($response)) {
            $result = json_decode($response, true);
        } else {
            $result = $response;
        }
        return($result);
    }

    /**
     * Get Status Code of Last Rest Call Made
     * @return string  HTTP Status Code
     */
    protected function getStatusCode() {
        $code = empty($this->http_status['code']) ? '0' : $this->http_status['code'];
        return $code;
    }

    /**
     * Get HTTP Status of Last Rest Call Made
     * @return string  HTTP Status Code
     */
    public function getStatus() {
        $code = $this->getStatusCode();
        if ($code=='200') {
            $status = $code . " OK";
        } else {
            $status = empty($this->http_status['status']) ? '' : $this->http_status['status'];
        }
        return $status;
    }

    /*-- Private Methods -------------------*/

    /**
     * @param string $method
     * @param string $uri
     * @param array $extraheaders
     */
    protected function restapi_init($method, $uri, $extraheaders=array()) {
        $this->http_status=false;
        $tempArray = explode("?", $uri);
        $uri = $tempArray[0];
        $qs = '';
        $pathBits = explode('/', trim($uri, '/'));
        array_shift($pathBits);
        $this->get_args = array(
            '__sugar_url' => implode('/',$pathBits)
        );
        if (count($tempArray) >= 2) {
            $qs = $tempArray[1];
            if (!empty($qs)) {
                $qs_array=array();
                $qs_args = explode("&", $qs);
                foreach($qs_args as $arg) {
                    $kv = explode("=", $arg);
                    $k=$kv[0];
                    $v='';
                    if (count($kv) >= 2) {
                        $v=$kv[1];
                    }
                    $qs_array[$k] = $v;
                }
                $this->get_args = array_merge($this->get_args, $qs_array);
            }
        }
        $this->server = array(
            'HTTP_HOST'     => 'localhost:8888',
            'REQUEST_METHOD' => $method,
            'REQUEST_URI'   => $uri,
            'PATH_INFO'     => $uri,
            'SCRIPT_NAME'   => '/lib/rest/rest.php',
            'REMOTE_ADDR'   => '127.0.0.1',
            'HTTP_API_USER' => 'Invalid',
            'HTTP_API_INSTANCE' => md5("PHPUNIT Tests"),
            'HTTP_API_SITE_URL' => 'http://localhost:8888/phpunit/tests'
        );
        if (!empty($extraheaders)) {
            $this->server = array_merge($this->server,$extraheaders);
        }
        if (!empty($qs)) {
            $this->server['QUERY_STRING'] = $qs;
        }

        $this->post_args=array();

        $this->request = $this->get_args;
    }
}
