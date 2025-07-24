<?php
include '../conn.php';  // Include the database connection

function getOptions($column) {
    global $conn;
    $options = [];
    
    $query = "SELECT DISTINCT $column FROM admin_units WHERE $column IS NOT NULL ORDER BY $column ASC";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $options[] = $row[$column];
        }
    }

    return $options;
}

// Fetch options for regions, districts, and facilities
$regions = getOptions('region');
$districts = getOptions('district');
$facilities = getOptions('facility');

// Return options as a JSON object
echo json_encode([
    'regions' => $regions,
    'districts' => $districts,
    'facilities' => $facilities
]);
?>
