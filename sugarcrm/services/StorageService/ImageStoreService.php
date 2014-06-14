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

use Aws\S3\S3Client;

/**
 *
 */
class ImageStoreService
{
    const DEFAULT_MAX_LIST_SIZE = 500;

    protected $imageStoreConfig;
    protected $customerId;
    protected $customerBucket;
    protected $customerFolder;
    protected $customerFolderLen; // including separator
    protected $baseUrl;

    /**
     * Load Configuration Data
     */
    public function __construct($customerId)
    {
        $this->customerId = $customerId;
        $this->customerFolder = $customerId;   // md5($customerId);
        $this->customerFolderLen = strlen($this->customerFolder) + 1; // includes separator
        $this->imageStoreConfig = SugarConfig::getImageStoreConfiguration();

        /*---
        To get started, we are assuming that there is a Single Bucket for All Customer Accounts.
        There will eventually need to be a Separate Bucket for each customer, with the ability
           to manage each individual Customer's usage.
        ----*/
        $this->customerBucket = $this->imageStoreConfig['bucket'];
        $this->baseUrl = "http://{$this->customerBucket}.s3.amazonaws.com/";
    }

    /**
     * Get Object From Customer's Image Store
     * @param string $resourceName  - Object Name
     * @return string  Publicly accessible URL
     */
    public function getObject($resourceName)
    {
        try {
            $imageStore = $this->getImageStoreClient();
            $result = $imageStore->getObject(
                array(
                    'Bucket' => $this->customerBucket,
                    'Key' => $this->getQualifiedResourceName($resourceName)
                )
            );
            $result = $result->toArray();
            $metaData = empty($result['Metadata']) ? array() : $result['Metadata'];
            $object = array(
                "resource_name" => $resourceName,
                "resource_url" => $this->baseUrl . $this->getQualifiedResourceName($resourceName),
                "object_size" => $result['ContentLength'],
                "mime_type" => $result['ContentType'],
                "metadata" => $metaData,
            );
            return $object;
        } catch (Exception $e) {
            throw new Exception("getObject Failure: " . $e->getMessage());
        }
    }

    /**
     * Put Object to Customer's Image Store
     * @param string $contents       ; Binary Data
     * @param string $resourceName   ; Object Name
     * @param string $contentType    ; Content Type e.g. 'text/plain', 'image/jpeg'
     * @param array  $metadata       ; optional associative array
     * @return string  Publicly accessible URL
     */
    public function putObject($contents, $resourceName, $contentType, $metadata = array())
    {
        try {
            $imageStore = $this->getImageStoreClient();
            $params = array(
                'Bucket' => $this->customerBucket,
                'Key' => $this->getQualifiedResourceName($resourceName),
                'ContentType' => $contentType,
                'Body' => $contents,
                'ACL' => 'public-read',
                'StorageClass' => 'REDUCED_REDUNDANCY',
                'Metadata' => $metadata
            );
            $result = $imageStore->putObject($params);
            if (empty($result['ObjectURL'])) {
                throw new Exception('Unable to obtain Resource URL');
            }
            return $result['ObjectURL'];
        } catch (Exception $e) {
            throw new Exception("putObject Failure: " . $e->getMessage());
        }
    }

    /**
     * Put an Image File to Customer's Image Store
     * @param string $filePath       ; Absolute File Path
     * @param string $resourceName   ; Object Name
     * @param string $contentType    ; Cotent Type e.g. 'text/plain', 'image/jpeg'
     * @param array  $metadata       ; optional associative array
     * @return string  Publicly accessible URL
     */
    public function putFile($filePath, $resourceName, $contentType, $metadata = array())
    {
        try {
            $imageStore = $this->getImageStoreClient();
            $result = $imageStore->putObject(
                array(
                    'Bucket' => $this->customerBucket,
                    'Key' => $this->getQualifiedResourceName($resourceName),
                    'SourceFile' => $filePath,
                    'ContentType' => $contentType,
                    'ACL' => 'public-read',
                    'StorageClass' => 'REDUCED_REDUNDANCY',
                    'Metadata' => $metadata
                )
            );
            if (empty($result['ObjectURL'])) {
                throw new Exception('Unable to obtain Resource URL');
            }
            return $result['ObjectURL'];
        } catch (Exception $e) {
            throw new Exception("putFile Failure: " . $e->getMessage());
        }
    }

    /**
     * Delete an Object in Customer's Image Store
     *
     * @param $resourceName
     * @return bool success
     */
    public function deleteObject($resourceName)
    {
        try {
            $imageStore = $this->getImageStoreClient();
            $imageStore->deleteObject(
                array(
                    'Bucket' => $this->customerBucket,
                    'Key' => $this->getQualifiedResourceName($resourceName)
                )
            );
            return true;
        } catch (Exception $e) {
            throw new Exception("deleteObject Failure: " . $e->getMessage());
        }
    }

    /**
     * List Objects in User's Bucket
     *
     * NOTE: Marker works like 'LAST_MARKER received' but is documented as 'NEXT MARKER'
     *
     * @return array StorageObject
     */
    public function listObjects($options = array())
    {
        $details = (!empty($options['metadata']) && $options['metadata'] === 'true');
        $userPrefix = empty($options['prefix']) ? '' : $options['prefix'];
        $prefix  = $this->customerFolder . '/' . $userPrefix;
        $maxNum  = empty($options['max_num'])  ? self::DEFAULT_MAX_LIST_SIZE : $options['max_num'];
        $listOptions = array(
            'Bucket' => $this->customerBucket,
            'Prefix' => $prefix,
            'MaxKeys' => $maxNum+1
        );
        $nextResourceName = empty($options['next_resource']) ? null : $options['next_resource'];
        if (!empty($nextResourceName)) {
            $listOptions['Marker'] = $this->getQualifiedResourceName($nextResourceName);
        }
        try {
            $imageStore = $this->getImageStoreClient();
            $storageContents = array();
            $iterator = $imageStore->getIterator(
                'ListObjects',
                $listOptions
            );
            $count=0;
            $last=true;
            $nextRecord=null;
            foreach ($iterator as $s3Data) {
                $qualifiedResourceName = $s3Data['Key'];
                $unQualifiedResourceName = $this->getUnQualifiedResourceName($s3Data['Key']);
                $objectSize = $s3Data['Size'];
                // S3 DateTime Format: 2014-02-15T02:55:46.000Z
                $lastModified = $s3Data['LastModified'];
                $lastModified = substr($lastModified, 0, 10) . ' ' . substr($lastModified, 11, 8);
                $record = array(
                    "resource_name" => $unQualifiedResourceName,
                    "resource_url" => "http://{$this->customerBucket}.s3.amazonaws.com/{$qualifiedResourceName}",
                    "last_modified" => $lastModified,
                    "object_size" => $objectSize
                );
                if ($details) {
                    $result = $imageStore->getObject(
                        array(
                            'Bucket' => $this->customerBucket,
                            'Key' => $this->getQualifiedResourceName($unQualifiedResourceName)
                        )
                    );
                    $result = $result->toArray();
                    $record["metadata"] = empty($result['Metadata']) ? array() : $result['Metadata'];
                    $record["mime_type"] = $result['ContentType'];
                }
                if ($count < $maxNum) {
                    $storageContents[] = $record;
                    $nextRecord = $record;
                    $count++;
                } else {
                    $last=false;
                    break;
                }
            }
            if ($last) {
                $next = '';
            } else {
                $next = $nextRecord["resource_name"];
            }
            $result = array(
                'max_num' => $maxNum,
                'actual'  => $count,
                'last'    => $last,
                'next_resource' => $next,
                'records' => $storageContents
            );
            return $result;
        } catch (Exception $e) {
            throw new Exception("listObjects Failure: " . $e->getMessage());
        }
    }

    /**
     * Get Object From Customer's Image Store
     *
     * @param string $resourceName     ; Object Name
     * @return boolean true if Exists
     */
    public function objectExists($resourceName)
    {
        try {
            $imageStore = $this->getImageStoreClient();
            $result = $imageStore->getObject(
                array(
                    'Bucket' => $this->customerBucket,
                    'Key' => $this->getQualifiedResourceName($resourceName)
                )
            );
            if (empty($result)) {
                return false;
            }
            return true;
        } catch (AWS\S3\Exception\NoSuchKeyException $e) {
            return false;
        }
        catch (Exception $e) {
            throw new Exception("getObject Failure: " . $e->getMessage());
        }
    }

    /**
     * Get an Instance of the S3 Client using the
     * configured S3 access keys
     */
    protected function getImageStoreClient()
    {
        try {
            $imageStore = S3Client::factory(
                array(
                    'key' => $this->imageStoreConfig['access_key'],
                    'secret' => $this->imageStoreConfig['secret_key']
                )
            );
            return $imageStore;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Resources known to caller do not have customer account folder information
     * We need to provide the qualified Resource name to S3 (includes Customer Account Folder)
     */
    protected function getQualifiedResourceName($resourceName)
    {
        return $this->customerFolder . '/' . $resourceName;
    }

    /**
     * Resources known to caller do not have customer account folder information
     * We need to provide the qualified Resource name to S3 (includes Customer Account Folder)
     */
    protected function getUnQualifiedResourceName($resourceName)
    {
        if (strlen($resourceName) > $this->customerFolderLen &&
            substr($resourceName, $this->customerFolderLen - 1, 1) === '/'
        ) {
            $resourceName = substr($resourceName, $this->customerFolderLen);
        }
        return $resourceName;
    }
}
