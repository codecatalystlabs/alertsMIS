<?php
require('../conn.php');
$districtId = isset($_POST['district']) ? intval($_POST['district']) : 0;
$data = [];

if ($districtId > 0) {
    $query = "SELECT id, subcounty FROM subcounties WHERE district_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $districtId);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }
    $stmt->close();
}
echo json_encode($data);
?>
