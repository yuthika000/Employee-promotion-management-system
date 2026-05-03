<?php
// Oracle Database Configuration
$host = 'localhost';
$port = '1521';
$service_name = 'XEPDB1';
$username = 'HMS'; // Change to your Oracle username
$password = 'HMS'; // Change to your Oracle password

// Create connection string
$tns = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SERVICE_NAME=$service_name)))";

try {
    $conn = oci_connect($username, $password, $tns, 'AL32UTF8');
    if (!$conn) {
        $e = oci_error();
        throw new Exception($e['message']);
    }
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
