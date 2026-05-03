<?php
require_once 'config.php';

$deleted = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_person'])) {
    $del_sql = "DELETE FROM PERSON_TABLE";
    $del_stmt = oci_parse($conn, $del_sql);
    if (oci_execute($del_stmt)) {
        oci_commit($conn);
        $deleted = true;
    }
    oci_free_statement($del_stmt);
}

echo "<h2>Checking EMP_HMS2_TABLE...</h2>";
$sql1 = "SELECT COUNT(*) as cnt FROM HMS2.EMP_HMS2_TABLE";
$stmt1 = oci_parse($conn, $sql1);
if (oci_execute($stmt1)) {
    $row1 = oci_fetch_assoc($stmt1);
    echo "<p>EMP_HMS2_TABLE: " . $row1['CNT'] . " records</p>";
} else {
    $e = oci_error($stmt1);
    echo "<p>Error: " . $e['message'] . "</p>";
}

echo "<h2>Checking PERSON_TABLE...</h2>";
$sql2 = "SELECT COUNT(*) as cnt FROM PERSON_TABLE";
$stmt2 = oci_parse($conn, $sql2);
if (oci_execute($stmt2)) {
    $row2 = oci_fetch_assoc($stmt2);
    echo "<p>PERSON_TABLE: " . $row2['CNT'] . " records</p>";
} else {
    $e = oci_error($stmt2);
    echo "<p>Error: " . $e['message'] . "</p>";
}

if ($deleted) {
    echo "<p style='color: green; font-weight: bold;'>✓ All records deleted from PERSON_TABLE</p>";
}

oci_close($conn);
?>
<form method="POST" style="margin-top: 20px;">
    <button type="submit" name="delete_person" style="padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 5px; cursor: pointer;">Delete All Records from PERSON_TABLE</button>
</form>
