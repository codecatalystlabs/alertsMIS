<?php
require('../conn.php');

$subcountyId = isset($_POST['subcounty']) ? intval($_POST['subcounty']) : 0;
$facilityType = isset($_POST['facilityType']) ? $_POST['facilityType'] : ''; // Correct assignment of facilityType
$data = [];

if ($subcountyId > 0) { // Changed $districtId to $subcountyId
    $query = "SELECT id, facility FROM facilities WHERE subcounty_id = ? AND ownership = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $subcountyId, $facilityType); // Use correct parameter binding
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
}

echo json_encode($data);
?>
