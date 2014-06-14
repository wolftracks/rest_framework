<?php
include_once("../../lib/env/entrypoint.php");
define('ENTRY_POINT_TYPE', 'daemon');

require_once("lib/database/MysqliDatabaseManager.php");

$field_definitions = array(
    'id' => 'int',
    'customer_id' => 'char',
    'communication_id' => 'char',
    'status' => 'char',
    'data' => 'char',
    'result' => 'char',
    'not_before' => 'int',
    'retries' => 'int',
    'deleted' => 'int',
    'date_entered' => 'datetime',
);
$columns = array_keys($field_definitions);

$dbConfig = SugarConfig::getDatabaseConfiguration();

$dbm = new MysqliDatabaseManager($dbConfig);
$dbm->connect();

$ai = $dbm->getAutoIncrement("jobqueue"); // NEXT Insert will Have this Increment Value

try {
    $sql = "SELECT " . $dbm->getSelectSet($columns) . " FROM `jobqueue`";
    $sql .= " WHERE deleted = ?";
    $sql .= " OR deleted = ?";
    $sql .= " OR deleted = ?";

    $stmt = $dbm->prepare($sql);
    $bindResult = $dbm->bindParameters($stmt, "iii", array(1, 0, 3));
    $result = $dbm->execute($stmt);
    while ($row = $dbm->fetchResult($stmt, $columns)) {
        $row['data'] = "**DATA**";
        print_r($row);
    }
    $dbm->closeStatement($stmt);
    $dbm->close();
} catch (DatabaseException $e) {
    $lastError = $dbm->getLastError();
    printf("--- Database Exception --- %s\n", $e->getCode());
    printf("Last Error: %s\n", $lastError);
    printf("Message: %s\n", $e->getMessage());
    printf("Log Message: %s\n", $e->getLogMessage());
}

exit;
