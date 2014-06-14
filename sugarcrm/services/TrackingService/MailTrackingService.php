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

require_once("sugarcrm/helpers/TrackingEvent.php");

abstract class MailTrackingService
{
    protected $dbm;
    const DEFAULT_ROWS_PER_REQUEST = 100;

    protected static $field_definitions = array(
        'id' => 'int',
        'customer_id' => 'char',
        'communication_id' => 'char',
        'datetime' => 'char',
        'event_type' => 'char',
        'event_id' => 'char',
        'ip' => 'char',
        'email' => 'char',
        'url' => 'char',
        'user_agent' => 'char',
        'tags' => 'char',
        'description' => 'char',
        'location' => 'char',
    );

    protected static $api_fields = array (
        'id',
        'customer_id',
        'communication_id',
        'datetime',
        'event_type',
        'ip',
        'email',
        'url',
        'user_agent',
        'tags',
        'description',
        'location'
    );

    protected $database_columns;

    protected $send_event_notice = false;

    /**
     *
     */
    function __construct()
    {
        $this->dbm = DBManagerFactory::getDatabaseManager();
        if (empty($this->dbm)) {
            Log::fatal('MailStatus: Unable to connect to Database ...');
            throw new DatabaseException(DATABASE_EXCEPTION_CONNECTION_FAILED, DatabaseException::ConnectionFailed);
        }

        $event_notice_setting = SugarConfig::get('misc.send_event_notice');
        if ($event_notice_setting == 'y' || $event_notice_setting == 'Y') {
            $this->send_event_notice = true;
        }

        $this->database_columns = array_keys(self::$field_definitions);
    }

    /**
     * Each event received will be processed by the appropriate EventHandler
     * @param $event
     * @return mixed
     */
    abstract public function process_event(array $event);

    /**
     * @abstract
     * @access public
     * @param string $api_user required
     * @param string $api_pass required
     */
    abstract public function setServiceAccountInfo($api_user, $api_pass);

    /**
     * @access public
     * @param string $customer_id required
     * @param array $options required
     */
    public function getTrackingRecords($customer_id, $options)
    {
        $rows = array();
        $last_id  = empty($options['last_id']) ? '0' : $options['last_id'];
        $max_rows = empty($options['max_num']) ? self::DEFAULT_ROWS_PER_REQUEST : (int) $options['max_num'];

        $stmt = false;
        try {
            $api_select_fields = $this->dbm->getSelectSet(self::$api_fields);
            $sql  = "SELECT {$api_select_fields} FROM tracking";
            $sql .= " WHERE customer_id = ?";
            $sql .= " AND id > ?";
            $sql .= " ORDER by id LIMIT ?";
            $stmt = $this->dbm->prepare($sql);

            $bindTemplate = 'ssi';
            $bindParams = array($customer_id, $last_id, $max_rows);
            $this->dbm->bindParameters($stmt, $bindTemplate, $bindParams);
            $this->dbm->execute($stmt);
            while($row = $this->dbm->fetchResult($stmt, self::$api_fields)) {
                if (!empty($row['location'])) {
                    $row['location'] = json_decode(urldecode($row['location']), true);
                }
                $rows[] = $row;
            }
            $count = count($rows);
            if ($count > 0) {
                $last_id_response = $rows[$count-1]['id'];
                $this->updateTrackingStatus($customer_id, $last_id, $last_id_response);
            }
            $this->dbm->closeStatement($stmt);
        } catch (DatabaseException $e) {
            if ($stmt !== false) {
                $this->dbm->closeStatement($stmt);
            }
            Log::error($e->getLogMessage());
        } catch (Exception $e) {
            if ($stmt !== false) {
                $this->dbm->closeStatement($stmt);
            }
            Log::error($e->getMessage());
        }
        return $rows;
    }

    /**
     * @param TrackingEvent $trackingEvent
     * @return bool
     * @throws Exception
     */
    protected function addTrackingEvent(TrackingEvent $trackingEvent) {

        if (empty($trackingEvent->customer_id) ||
            empty($trackingEvent->communication_id) ||
            empty($trackingEvent->event_id)   ||
            empty($trackingEvent->event_type) ||
            empty($trackingEvent->datetime)   ||
            $trackingEvent->event_type == 'click' && empty($trackingEvent->url)) {

            return false;  // Minimal Elements Required to Log this Event Were Not Met
        }

        $stmt = false;
        try {
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

            $placeholders = array_fill(0, count($this->database_columns), '?');
            $sql = 'INSERT into tracking ' .
                '(' . implode(', ', $this->database_columns) . ') VALUES ' .
                '(' . implode(', ', $placeholders) . ');';

            $stmt = $this->dbm->prepare($sql);

            $bindParams = array(
                null,
                $trackingEvent->customer_id,
                $trackingEvent->communication_id,
                $trackingEvent->datetime,
                $trackingEvent->event_type,
                $trackingEvent->event_id,
                $trackingEvent->ip,
                $trackingEvent->email,
                $trackingEvent->url,            // URL Clicked On when Event type is click
                $trackingEvent->user_agent,
                $trackingEvent->tags,
                $trackingEvent->description,
                $trackingEvent->location
            );

            $this->dbm->bindParameters($stmt, $bindTemplate, $bindParams);
            $this->dbm->execute($stmt);
            if ($this->dbm->affectedRows($stmt) != 1) {
                throw new Exception('MandrillMailStatus: addTrackingEvent - Insert did not result in Expected Row Change');
            }
            $this->dbm->closeStatement($stmt);
            return true;
        } catch (DatabaseException $e) {
            if ($stmt !== false) {
                $this->dbm->closeStatement($stmt);
            }
            Log::error($e->getLogMessage());
            return false;
        } catch (Exception $e) {
            if ($stmt !== false) {
                $this->dbm->closeStatement($stmt);
            }
            Log::error($e->getMessage());
            return false;
        }
    }

    private function updateTrackingStatus($customer_id, $last_id_request, $last_id_response)
    {
        $datetime = gmdate('Y-m-d H:i:s', time());
        $updated=false;
        $stmt = false;
        try {
            $sql = 'INSERT into tracker_status ' .
                '(customer_id, last_id_request, last_id_response, datetime ) VALUES (?, ?, ?, ?);';
            $bindParams = array($customer_id, $last_id_request, $last_id_response, $datetime);
            $bindTemplate = "ssss";
            $stmt = $this->dbm->prepare($sql);
            $this->dbm->bindParameters($stmt, $bindTemplate, $bindParams);
            $this->dbm->execute($stmt);
            $errno = $this->dbm->getErrno();

            if ($errno == DBManager::MYSQL_CODE_DUPLICATE_KEY) {
                $this->dbm->closeStatement($stmt);

                $sql = "UPDATE tracker_status SET ";
                $sql .= " last_id_request = ?,";
                $sql .= " last_id_response = ?,";
                $sql .= " datetime = ?";
                $sql .= " WHERE customer_id = ?";
                $sql .= " AND (last_id_request < ? OR last_id_response < ?)";
                $bindParams = array(
                    $last_id_request,
                    $last_id_response,
                    $datetime,
                    $customer_id,
                    $last_id_request,
                    $last_id_response,
                );
                $bindTemplate = "ssssss";
                $stmt = $this->dbm->prepare($sql);
                $this->dbm->bindParameters($stmt, $bindTemplate, $bindParams);
                $this->dbm->execute($stmt);
                $errno = $this->dbm->getErrno();
            }
            if ($errno == 0 && $this->dbm->affectedRows($stmt) > 0) {
                $updated=true;
            }
            $this->dbm->closeStatement($stmt);
            return $updated;
        } catch (DatabaseException $e) {
            if ($stmt !== false) {
                $this->dbm->closeStatement($stmt);
            }
            Log::error($e->getLogMessage());
            return false;
        } catch (Exception $e) {
            if ($stmt !== false) {
                $this->dbm->closeStatement($stmt);
            }
            Log::error($e->getMessage());
            return false;
        }
    }

    /**
     * @param $toName
     * @param $toEmail
     * @param $fromName
     * @param $fromEmail
     * @param $subject
     * @param $message
     * @return bool
     */
    protected function send_notice($toName, $toEmail, $fromName, $fromEmail, $subject, $message)
    {
        $recipient = "\"$toName\" <$toEmail>";
        $sender = "\"$fromName\" <$fromEmail>";
        $extraHeaders = "From: " . $sender . "\n";
        $extraHeaders .= "Reply-To: " . $sender . "\n";

        $message = str_replace("\\", "", $message);
        $subject = str_replace("\\", "", $subject);

        $result = mail($recipient, $subject, $message . "\n", $extraHeaders);

        return ($result);
    }
}
