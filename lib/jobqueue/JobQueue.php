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
require_once("lib/jobqueue/Job.php");
require_once("lib/jobqueue/JobQueueException.php");
require_once("lib/database/MysqliDatabaseManager.php");

class JobQueue
{
    protected $dbm;
    protected $node_id='';

    const STATUS_PENDING = "pending";
    const STATUS_STARTED = "started";
    /* Final Statuses */
    const STATUS_COMPLETE = "complete";
    const STATUS_FAILED = "failed";

    protected static $maxRetries = 3;
    protected static $retryIntervalMinutes = 10;

    const JOBTYPE_ANY       = "*";
    const JOBTYPE_SENDMAIL  = "sendmail";
    protected static $jobtypes = array(
        JobQueue::JOBTYPE_ANY,
        JobQueue::JOBTYPE_SENDMAIL,
    );

    protected static $field_definitions = array(
        'id' => 'int',
        'job_type' => 'char',
        'customer_id' => 'char',
        'node_id' => 'char',
        'status' => 'char',
        'data' => 'char',
        'result' => 'char',
        'not_before' => 'int',
        'retries' => 'int',
        'deleted' => 'int',
        'date_entered' => 'datetime',
    );
    protected static $columns;
    protected static $select_set;

    function __construct()
    {
        $this->dbm = DBManagerFactory::getDatabaseManager();
        $this->dbm->connect();
        $this->node_id = SugarConfig::get('site.node_id');
        self::$maxRetries = SugarConfig::get('jobqueue.max_retries', self::$maxRetries);
        self::$retryIntervalMinutes = SugarConfig::get('jobqueue.retry_interval_minutes', self::$retryIntervalMinutes);
        self::$columns = array_keys(self::$field_definitions);
        self::$select_set = $this->dbm->getSelectSet(self::$columns);
    }

    /**
     * Create a new Job Queue Entry for specified Customer
     *
     * @param  Job    $job
     * @return boolean true=success
     * @throws JobQueueException  Allows JobQueueException to bubble up.
     */
    public function writeQueue(Job $job)
    {
        if (!$this->createJob($job)) {
            return false;
        }
        return true;
    }

    /**
     * Read and Dequeue the next Job on the Queue and return it
     *
     * @param  string  $job_type  (optional : default = JOBTYPE_ANY)
     * @return  Job $job or false if none available
     */
    public function readQueue($job_type=self::JOBTYPE_ANY)
    {
        try {
            $job = $this->dequeueNextJob($job_type);
            if (empty($job)) {
                return false;
            }
            return $job;
        } catch (JobQueueException $e) {
            return false;
        }
    }

    /**
     * Set Final Job Status: Complete or Failed
     *
     * @param  string  $job_id
     * @param  string  $finalStatus
     * @return boolean  true=success
     */
    public function setFinalJobStatus($job_id, $finalStatus)
    {
        $result = false;

        if ($finalStatus == self::STATUS_COMPLETE ||
            $finalStatus == self::STATUS_FAILED
        ) {
            $result = $this->setJobStatus($job_id, $finalStatus);
        }

        return $result;
    }

    /**
     * Retry Job with Specified job_id
     * If a Job Fails to execute, it can be Retried.
     * Up To self::$maxRetries are allowed for any given Job.
     * A Retry is deferred self::$retryIntervalMinutes before it
     * becomes available
     * When All Retries have been used or if the attempt to Retry Fails,
     * the Job Status is set to STATUS_FAILED.
     *
     * @param  string  $job_id
     * @return boolean  true=success
     */
    public function retryJob($job_id)
    {
        $stmt = false;
        try {
            $not_before = time() + (self::$retryIntervalMinutes * 60);

            $sql = "UPDATE jobqueue SET ";
            $sql .= " status = '" . self::STATUS_PENDING . "',";
            $sql .= " retries = retries + 1,";
            $sql .= " not_before = '$not_before'";
            $sql .= " WHERE id   = ?";
            $sql .= " AND status = '"  . self::STATUS_STARTED . "'";
            $sql .= " AND retries < '" . self::$maxRetries . "'";
            $sql .= " AND deleted = 0";

            $stmt = $this->dbm->prepare($sql);
            $this->dbm->bindParameters($stmt, "s", array($job_id));
            $this->dbm->execute($stmt);
            if ($this->dbm->affectedRows($stmt) != 1) {
                throw new JobQueueException('retryJob: Update did not result in Expected Row Change', JobQueueException::UnexpectedOutcome);
            }
            $this->dbm->closeStatement($stmt);
            return true;
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

        $this->setFinalJobStatus($job_id, self::STATUS_FAILED);
        return false;
    }

    /**
     * Create a new Job Queue Entry for specified Customer
     * @param  Job   $job
     * @throws JobQueueException  Allows JobQueueException to bubble up.
     */
    protected function createJob(Job $job)
    {
        $stmt = false;
        $this->argRequired('CreateJob', 'JobType', $job->job_type, 'enum', self::$jobtypes);
        $this->argRequired('CreateJob', 'Customer', $job->customer_id, 'string');
        $this->argRequired('CreateJob', 'Payload', $job->payload);

        $data = base64_encode(serialize($job->payload));
        $status = self::STATUS_PENDING;
        $date_entered = gmdate("Y-m-d H:i:s");

        try {
            $placeholders = array_fill(0, count(self::$columns), '?');
            $sql = 'INSERT into jobqueue ' .
                '(' . implode(', ', self::$columns) . ') VALUES ' .
                '(' . implode(', ', $placeholders)  . '); ';

            $stmt = $this->dbm->prepare($sql);
            $bindParams = array(NULL, $job->job_type, $job->customer_id, $this->node_id, $status, $data, NULL, 0, 0, 0, $date_entered);
            $this->dbm->bindParameters($stmt, "sssssssiiis", $bindParams);
            $this->dbm->execute($stmt);
            if ($this->dbm->affectedRows($stmt) != 1) {
                throw new JobQueueException('createJob: Insert did not result in Expected Row Change', JobQueueException::UnexpectedOutcome);
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

    /**
     * Return Next Job Available if one is Available and Update
     * that Jobs Status to "Started". This effectively dequeues the job
     * since it is no longer available.
     *
     * @param  string  $job_type  (optional : default = JOBTYPE_ANY)
     * @return  Job $job or false if none available
     */
    protected function dequeueNextJob($job_type=self::JOBTYPE_ANY)
    {
        if (empty($job_type) || !in_array($job_type, self::$jobtypes)) {
            return false;
        }

        $this->dbm->lockTables(array('jobqueue', 'WRITE'));
        $job = false;

        try {
            $sql  = "SELECT * FROM jobqueue";
            $sql .= " WHERE status='" . self::STATUS_PENDING . "'";
            if ($job_type != self::JOBTYPE_ANY) {
                $sql .= " AND job_type = '" . $job_type . "'";
            }
            $sql .= " AND not_before <= '" . time() . "'";
            $sql .= " ORDER by id LIMIT 1";
            $result = $this->dbm->query($sql);
            if ($result && ($row = $this->dbm->fetchAssoc($result))) {
                if ($this->setJobStatus($row['id'], self::STATUS_STARTED)) {
                    $job = $this->toJob($row);
                }
                $this->dbm->freeQueryResult($result);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage()); // Do Not Return - Must Release Locks
        }

        $this->dbm->unlockTables();
        return $job;
    }

    /**
     * Set Job Status for existing Job
     *
     * @param  string  $job_id
     * @param  string  $status
     * @return boolean true=success
     */
    protected function setJobStatus($job_id, $status)
    {
        $stmt = false;
        try {
            $sql  = "UPDATE jobqueue SET status = ?";
            $sql .= " WHERE id = ?";
            $sql .= " AND deleted = 0";

            $stmt = $this->dbm->prepare($sql);
            $this->dbm->bindParameters($stmt, "ss", array($status, $job_id));
            $this->dbm->execute($stmt);
            if ($this->dbm->affectedRows($stmt) != 1) {
                throw new JobQueueException('setJobStatus: Update did not result in Expected Row Change', JobQueueException::UnexpectedOutcome);
            }
            $this->dbm->closeStatement($stmt);
            return true;
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
        return false;
    }

    /**
     * Check required Arguments to make sure that they are
     * not Empty and where possible, are the right type
     *
     * @param  string $operation   'Function description for Logging/Exception Reporting if failure'
     * @param  string $name        'Name of Required Argument'
     * @param  string $arg         'Argument Value'
     * @param  string $type        'Argument Type for type constraint checking'
     * @param  array  $enum_values 'Array of allowed falues if type = enum'
     * @throws JobQueueException
     */
    protected function argRequired($operation, $name, $arg, $type = null, $enum_values = array())
    {
        if (empty($arg)) {
            $msg = "JobQueue: $operation failed - Argument '$name' required, but empty";
            Log::error($msg);
            throw new JobQueueException($msg, JobQueueException::MissingArgument);
        }
        if (($type === 'array' && !is_array($arg)) || ($type === 'string' && !is_string($arg))) {
            $msg = "JobQueue: $operation failed - Argument '$name' - expected type: $type";
            Log::error($msg);
            throw new JobQueueException($msg, JobQueueException::InvalidArgument);
        }
        if ($type === 'enum' && (!is_string($arg) || !in_array($arg, $enum_values))) {
            $msg = "JobQueue: $operation failed - Argument '$name' - type: $type = unsupported value";
            Log::error($msg);
            throw new JobQueueException($msg, JobQueueException::InvalidArgument);
        }
    }

    /**
     * Create a Job object from the provided Database Row
     * @param  array  JobQueue DB Row
     * @return Job $job
     */
    protected function toJob(array $dbRow)
    {
        $job = new Job();
        $job->job_id = $dbRow['id'];
        $job->customer_id = $dbRow['customer_id'];
        $job->payload = unserialize(base64_decode($dbRow['data']));
        return $job;
    }
}
