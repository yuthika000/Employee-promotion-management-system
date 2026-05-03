<!DOCTYPE html>
<html>
<head><title>Truncate PERSON_TABLE</title></head>
<body>
<h1>Truncate PERSON_TABLE</h1>
<?php
require_once 'config.php';

// Check count before
$before_sql = "SELECT COUNT(*) as cnt FROM PERSON_TABLE";
$before_stmt = oci_parse($conn, $before_sql);
oci_execute($before_stmt);
$before_row = oci_fetch_assoc($before_stmt);
echo "<p>Before: PERSON_TABLE has " . $before_row['CNT'] . " records</p>";

// Try DELETE (works even with table locks)
$sql = "DELETE FROM PERSON_TABLE";
$stmt = oci_parse($conn, $sql);

if (oci_execute($stmt)) {
    echo "<p style='color: green;'>✓ DELETE command executed</p>";
    // Commit the delete
    oci_commit($conn);
    echo "<p style='color: green;'>✓ Transaction committed</p>";
} else {
    $e = oci_error($stmt);
    echo "<p style='color: red;'>✗ DELETE failed: " . $e['message'] . "</p>";
}

// Check count after
$after_sql = "SELECT COUNT(*) as cnt FROM PERSON_TABLE";
$after_stmt = oci_parse($conn, $after_sql);
oci_execute($after_stmt);
$after_row = oci_fetch_assoc($after_stmt);
echo "<p>After: PERSON_TABLE has " . $after_row['CNT'] . " records</p>";

oci_close($conn);

echo '<p><a href="check_counts.php">Check counts again</a></p>';
?>
</body>
</html>
