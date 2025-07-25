<?php
//session_start();
require('../conn.php');
$regionId = isset($_POST['region']) ? intval($_POST['region']) : 0;
$data = [];

if ($regionId > 0) {
    $query = "SELECT id, district FROM districts WHERE region_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $regionId);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }
    $stmt->close();
}
echo json_encode($data);
?>
