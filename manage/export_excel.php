<?php
session_start();
require('../conn.php');

// Fetch filter parameters from the request
$district = isset($_GET['district']) ? $_GET['district'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Base query
$sql = "SELECT * FROM alerts WHERE 1=1";
$params = [];
$types = "";

// Apply filters
if (!empty($district)) {
    $sql .= " AND alert_case_district LIKE ?";
    $params[] = "{$district}%";  // Add wildcards for partial match
    $types .= "s";
}

if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND date BETWEEN ? AND ?";
    $params[] = $from_date;
    $params[] = $to_date;
    $types .= "ss";
}

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Set headers for file download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=alerts_export.csv');

// Output column headers
$output = fopen('php://output', 'w');
fputcsv($output, [
    'Person Calling', 'Source of Signal', 'Contact Number','Date',
    'Alert Reported Before', 'Village', 'Parish', 'Subcounty', 'District',
    'Case Name', 'Age', 'Sex', 'Contact', 'Next of Kin', 'Next of Kin Contact', 'Time',
    'Health Facility Visit','History','Traditional Healer Visit','Symptoms',
    'Actions','Case Verification Desk','Field Verification Desk','Field Verification Decision',
    'Feedback','Comments','Verification Date','Verification Time'
]);

// Fetch and output data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['person_reporting'], $row['alert_from'], $row['contact_number'], $row['date'],
        $row['alert_reported_before'], $row['village'], $row['alert_case_parish'],
        $row['sub_county'], $row['alert_case_district'], $row['alert_case_name'],
        $row['alert_case_age'], $row['alert_case_sex'], $row['point_of_contact_name'],
        $row['point_of_contact_relationship'], $row['point_of_contact_phone'], $row['time'],
        $row['health_facility_visit'], $row['history'], $row['traditional_healer_visit'],
        $row['symptoms'], $row['actions'], $row['case_verification_desk'],$row['field_verification'],
        $row['field_verification_decision'], $row['feedback'],$row['comments'], $row['verification_date'],
        $row['verification_time']
    ]);
}

// Close file output
fclose($output);
exit();
?>
