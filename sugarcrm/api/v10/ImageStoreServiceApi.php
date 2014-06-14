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

require_once("sugarcrm/services/StorageService/ImageStoreService.php");

class ImagestoreServiceApi extends SugarApi
{
    public function registerApiRest()
    {
        $api = array(
            'createImage' => array(
                'reqType' => 'POST',
                'path' => array('imagestore'),
                'pathVars' => array('', ''),
                'method' => 'createImage',
            ),
            'deleteImage' => array(
                'reqType' => 'DELETE',
                'path' => array('imagestore','?'),
                'pathVars' => array('','resource_name'),
                'method' => 'deleteImage',
            ),
            'retrieveImage' => array(
                'reqType' => 'GET',
                'path' => array('imagestore','?'),
                'pathVars' => array('','resource_name'),
                'method' => 'retrieveImage',
            ),
            'listImages' => array(
                'reqType' => 'GET',
                'path' => array('imagestore'),
                'pathVars' => array(''),
                'method' => 'listImages',
            ),
        );

        return $api;
    }

    /**
     * Store New Image Resource in Image Store
     *
     * @param ServiceBase $api
     * @param $params
     * @return array
     * @throws SugarApiExceptionError
     */
    public function createImage(ServiceBase $api, $params)
    {
        $required_params = array('resource_name', 'mime_type', 'contents');
        $this->checkRequiredParams($required_params, $params);

        $resourceName = $params['resource_name'];
        if ($this->resourceExists($resourceName)) {
            throw new SugarApiExceptionError('Resource Already Exists');
        }

        try {
            $storageService = new ImageStoreService($this->customer_id);
            $mimeType = $params['mime_type'];
            $contents = base64_decode($params['contents']);

            $remoteName = empty($params['local_name']) ? '' : $params['local_name'];
            $metadata = array(
                "remote_name" => $remoteName
            );

            $resource_url = $storageService->putObject($contents, $resourceName, $mimeType, $metadata);

            $result = array(
                "resource_name" => $resourceName,
                "resource_url" => $resource_url,
            );

            return $result;
        } catch (Exception $e) {
            throw new SugarApiExceptionError('PutObject Storage Service Failure');
        }
    }

    /**
     * Delete Image Resource from Image Store
     *
     * @param ServiceBase $api
     * @param $params
     * @return array
     * @throws SugarApiExceptionNotFound
     * @throws SugarApiExceptionError
     */
    public function deleteImage(ServiceBase $api, $params)
    {
        $required_params = array('resource_name');
        $this->checkRequiredParams($required_params, $params);

        $resourceName = $params['resource_name'];
        if (!$this->resourceExists($resourceName)) {
            throw new SugarApiExceptionNotFound('Resource Not Found');
        }

        try {
            $storageService = new ImageStoreService($this->customer_id);
            $storageService->deleteObject($resourceName);
            return array(
                "resource_name" => $resourceName
            );
        } catch (Exception $e) {
            throw new SugarApiExceptionError('DeleteObject Storage Service Failure');
        }
    }

    /**
     * Get Image Data For Specified Image From Image Store
     *
     * @param ServiceBase $api
     * @param $params
     * @return array Image Data
     * @throws SugarApiExceptionNotFound
     * @throws SugarApiExceptionError
     */
    public function retrieveImage(ServiceBase $api, $params)
    {
        $required_params = array('resource_name');
        $this->checkRequiredParams($required_params, $params);

        $resourceName = $params['resource_name'];
        if (!$this->resourceExists($resourceName)) {
            throw new SugarApiExceptionNotFound('Resource Not Found');
        }

        try {
            $storageService = new ImageStoreService($this->customer_id);
            $imageInfo = $storageService->getObject($resourceName);
            return $imageInfo;
        } catch (Exception $e) {
            throw new SugarApiExceptionError('GetObject Storage Service Failure');
        }
    }

    /**
     * List Image Data From Image Store for this Customer Account
     *
     * @param ServiceBase $api
     * @param $params
     * @return array Image Objects
     * @throws SugarApiExceptionError
     */
    public function listImages(ServiceBase $api, $params)
    {
        try {
            $storageService = new ImageStoreService($this->customer_id);
            $imageObjects = $storageService->listObjects($params);
            return($imageObjects);
        } catch (Exception $e) {
            throw new SugarApiExceptionError('ListObjects Storage Service Failure');
        }
    }

    /**
     * Check to see if Resource Exists
     *
     * @return bool true=Exists
     * @throws SugarApiExceptionError
     */
    public function resourceExists($resourceName)
    {
        try {
            $storageService = new ImageStoreService($this->customer_id);
            $result = $storageService->objectExists($resourceName);
            return $result;
        } catch (Exception $e) {
            throw new SugarApiExceptionError('GetObject Storage Service Failure');
        }
    }

    /**
     * Verify all required parameters exists
     *
     * @param $required
     * @param $actual
     * @return bool
     * @throws SugarApiExceptionMissingParameter
     */
    protected function checkRequiredParams($required, $actual) {
        $missing = array();
        foreach ($required AS $required_param) {
            if (empty($actual[$required_param])) {
                $missing[] = $required_param;
            }
        }

        if (count($missing) > 0) {
            $fields = implode(',', $missing);
            throw new SugarApiExceptionMissingParameter('Missing Required Parameters: ' . $fields);
        }
        return true;
    }
}
