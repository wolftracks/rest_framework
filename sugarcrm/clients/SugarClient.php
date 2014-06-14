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

require_once("sugarcrm/clients/ClientInfo.php");

class SugarClient
{
    const MAX_RETRIES = 5;  // Maximum number of times to try creating unique id on create

    protected $dbm;

    public $id;
    public $api_user;
    public $api_instance;
    public $api_site_url;
    public $ip;
    public $request_count;
    public $last_request;
    public $date_entered;
    public $date_modified;

    protected static $field_definitions = array(
        'id' => 'char',
        'api_user' => 'char',
        'api_instance' => 'char',
        'api_site_url' => 'char',
        'ip' => 'char',
        'request_count' => 'int',
        'last_request' => 'int',
        'date_entered' => 'char',
        'date_modified' => 'char',
    );

    protected $database_columns;

    /**
     *
     */
    function __construct()
    {
        $this->dbm = DBManagerFactory::getDatabaseManager();
        if (empty($this->dbm)) {
            Log::fatal('SugarClient: Unable to connect to Database ...');
            throw new DatabaseException(DATABASE_EXCEPTION_CONNECTION_FAILED, DatabaseException::ConnectionFailed);
        }
        $this->database_columns = array_keys(self::$field_definitions);
    }

    /**
     * @param ClientInfo $clientInfo
     * @return bool  true if success
     * @throws Exception
     */
    public function trackClientRequest(ClientInfo &$clientInfo) {
        if (!empty($clientInfo->id)) {
            $info = $this->getClientInfoFromId($clientInfo->id);
        } else {
            $info = $this->getClientInfoFromApiData($clientInfo->api_user,
                $clientInfo->api_instance, $clientInfo->api_site_url);
        }

        $stmt = false;
        try {
            $updated=false;
            $errno = DBManager::MYSQL_CODE_RECORD_NOT_FOUND;
            if (!empty($info)) {
                $clientInfo = $info;

                $timestamp = time();
                $date_now = gmdate("Y-m-d H:i:s");

                $clientInfo->request_count++;
                $clientInfo->last_request = $timestamp;
                $clientInfo->date_modified = $date_now;

                $sql = "UPDATE client SET ";
                $sql .= " request_count = request_count+1,";
                $sql .= " last_request = ?,";
                $sql .= " date_modified = ?";
                $sql .= " WHERE id = ?";
                $bindParams = array(
                    $clientInfo->last_request,
                    $clientInfo->date_modified,
                    $clientInfo->id,
                );
                $bindTemplate = "sss";
                $stmt = $this->dbm->prepare($sql);
                $this->dbm->bindParameters($stmt, $bindTemplate, $bindParams);
                $this->dbm->execute($stmt);
                $errno = $this->dbm->getErrno();

                if ($errno == 0 && $this->dbm->affectedRows($stmt) > 0) {
                    $updated=true;
                    $this->dbm->closeStatement($stmt);
                }
            }

            if (!$updated && $errno == DBManager::MYSQL_CODE_RECORD_NOT_FOUND ) {
                $column_pos = array();
                $bindParams = array();
                $bindTemplate = '';
                $j=0;
                foreach($this->database_columns as $field) {
                    $column_pos[$field] = $j++;
                    if (self::$field_definitions[$field] === 'int') {
                        $bindParams[] = 0;
                        $bindTemplate .= 'i';
                    } else {
                        $bindParams[] = '';
                        $bindTemplate .= 's';
                    }
                }

                $this->dbm->closeStatement($stmt);
                $placeholders = array_fill(0, count($this->database_columns), '?');
                $sql = 'INSERT into client ' .
                    '(' . implode(', ', $this->database_columns) . ') VALUES ' .
                    '(' . implode(', ', $placeholders) . ');';

                $stmt = $this->dbm->prepare($sql);
                $timestamp = time();
                $date_now = gmdate("Y-m-d H:i:s");

                $clientInfo->request_count = 1;
                $clientInfo->last_request = $timestamp;
                $clientInfo->date_entered = $date_now;
                $clientInfo->date_modified = $date_now;

                for ($i = 0; $i < self::MAX_RETRIES && ($errno != 0); $i++) {
                    $clientInfo->id = create_guid();
                    $bindParams = array(
                        $clientInfo->id,
                        $clientInfo->api_user,
                        $clientInfo->api_instance,
                        $clientInfo->api_site_url,
                        $clientInfo->ip,
                        $clientInfo->request_count,
                        $clientInfo->last_request,
                        $clientInfo->date_entered,
                        $clientInfo->date_modified,
                    );
                    $stmt = $this->dbm->prepare($sql);
                    $this->dbm->bindParameters($stmt, $bindTemplate, $bindParams);
                    $this->dbm->execute($stmt);
                    $errno = $this->dbm->getErrno();
                    if ($errno != DBManager::MYSQL_CODE_DUPLICATE_KEY && $this->dbm->affectedRows($stmt) != 1) {
                        throw new Exception('SugarClient: trackClientRequest FAILED - ERRNO=' . $errno);
                    }
                    $this->dbm->closeStatement($stmt);
                }
            }

            if ($errno) {
                $logMessage = 'SugarClient: Failed (ERRNO: ' . $errno . ') trying to Insert new Client:'
                    . ' API_USER:' . $clientInfo->api_user
                    . ' API_INSTANCE:' . $clientInfo->api_instance
                    . ' API_SITE_URL:' . $clientInfo->api_site_url;
                throw new Exception($logMessage);
            }

            return true;
        } catch (DatabaseException $e) {
            if ($stmt !== false) {
                $this->dbm->closeStatement($stmt);
            }
            Log::error($e->getLogMessage());
            throw $e;
        } catch (Exception $e) {
            if ($stmt !== false) {
                $this->dbm->closeStatement($stmt);
            }
            Log::error($e->getMessage());
            throw $e;
        }
    }

    /**
     * @access public
     * @param string $id required
     * @return ClientInfo $clientInfo  - null if not found
     */
    public function getClientInfoFromId($id=null)
    {
        $stmt = false;
        $clientInfo = null;
        try {
            if (!empty($id)) {
                $select_fields = implode(', ', $this->database_columns);
                $sql  = "SELECT " . $select_fields . " FROM client";
                $sql .= " WHERE id = ?";
                $stmt = $this->dbm->prepare($sql);

                $bindTemplate = 's';
                $bindParams = array($id);
                $this->dbm->bindParameters($stmt, $bindTemplate, $bindParams);
                $this->dbm->execute($stmt);
                if ($this->dbm->getErrno() == 0 && $row = $this->dbm->fetchResult($stmt, self::$api_fields)) {
                    $clientInfo = ClientInfo::fromArray($row);
                }
                $this->dbm->closeStatement($stmt);
                return $clientInfo;
            }
        } catch (DatabaseException $e) {
            if ($stmt !== false) {
                $this->dbm->closeStatement($stmt);
            }
            Log::error($e->getLogMessage());
            return null;
        } catch (Exception $e) {
            if ($stmt !== false) {
                $this->dbm->closeStatement($stmt);
            }
            Log::error($e->getMessage());
            return null;
        }
        return null;
    }

    /**
     * @access public
     * @param string $id required
     * @return ClientInfo $clientInfo  - null if not found
     */
    public function getClientInfoFromApiData($apiUser, $apiInstance, $apiSiteUrl)
    {
        $stmt = false;
        $clientInfo = null;
        try {
            $select_fields = implode(', ', $this->database_columns);
            $sql  = "SELECT " . $select_fields . " FROM client";
            $sql .= " WHERE api_user = ? AND api_instance= ? AND api_site_url = ?";
            $stmt = $this->dbm->prepare($sql);

            $bindTemplate = 'sss';
            $bindParams = array($apiUser, $apiInstance, $apiSiteUrl);
            $this->dbm->bindParameters($stmt, $bindTemplate, $bindParams);
            $this->dbm->execute($stmt);
            if ($this->dbm->getErrno() == 0 && $row = $this->dbm->fetchResult($stmt, $this->database_columns)) {
                $clientInfo = ClientInfo::fromArray($row);
            }
            $this->dbm->closeStatement($stmt);
            return $clientInfo;
        } catch (DatabaseException $e) {
            if ($stmt !== false) {
                $this->dbm->closeStatement($stmt);
            }
            Log::error($e->getLogMessage());
            return null;
        } catch (Exception $e) {
            if ($stmt !== false) {
                $this->dbm->closeStatement($stmt);
            }
            Log::error($e->getMessage());
            return null;
        }
        return null;
    }
}
