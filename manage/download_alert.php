<?php
require('../conn.php');
require('fpdf/fpdf.php'); // Ensure FPDF is installed

// Get alert ID from URL
$alert_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$alert_id) {
    die("Invalid request. Missing or invalid alert ID.");
}

// Fetch alert details
$stmt = $conn->prepare("SELECT * FROM alerts WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error); // Debugging the prepare statement failure
}
$stmt->bind_param("i", $alert_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Alert not found in the database.");
}

$alert = $result->fetch_assoc();
$stmt->close();

// Check if data is fetched
if (!$alert) {
    die("Failed to fetch alert details.");
}

// Generate PDF
$pdf = new FPDF();
$pdf->AddPage();

// Set font and add a title
$pdf->SetFont('Arial', 'B', 20);
$pdf->Cell(0, 20, "Alert Desk", 0, 1, 'C');
$pdf->SetFont('Arial', 'I', 12);
//$pdf->Cell(0, 10, "Alert Details - #{$alert_id}", 0, 1, 'C');
$pdf->Ln(10);

// Add a border for certificate-like styling
$pdf->SetLineWidth(0.5);
$pdf->Rect(10, 30, 190, 260);  // Add a border around the certificate

// Title with a large font
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, "Dear team, after verification, Alert #{$alert_id} needs your attention", 0, 1, 'C');
$pdf->Ln(10);

// Set font for the content
$pdf->SetFont('Arial', '', 12);

// Table content (field names and their corresponding values)
$columns = [
    'Alert ID' => $alert['id'],
    'Presenting With' => $alert['symptoms'],
    'Name of Person Calling' => $alert['person_reporting'],
    'Source of Signal' => $alert['alert_from'],
    'Person Calling Phone' => $alert['contact_number'],
    'Date' => $alert['date'],
    'Alert Reported Before?' => $alert['alert_reported_before'],
    'Village' => $alert['village'],
    'Parish' => $alert['alert_case_parish'],
    'Subcounty' => $alert['sub_county'],
    'District' => $alert['alert_case_district'],
    'Name of Alert Case' => $alert['alert_case_name'],
    'Age' => $alert['alert_case_age'],
    'Sex' => $alert['alert_case_sex'],
    'Contact' => $alert['point_of_contact_name'],
    'Next of Kin' => $alert['point_of_contact_relationship'],
    'Contact of Next of Kin' => $alert['point_of_contact_phone'],
    'Time' => $alert['time']
];

// Add a line for spacing
$pdf->Ln(5);

// Loop through the alert details and add them in a certificate-friendly format
foreach ($columns as $label => $value) {
    // Add each field and value to the certificate
    $pdf->Cell(50, 10, ucfirst(str_replace("_", " ", $label)) . ":", 0, 0, 'L');
    $pdf->Cell(0, 10, $value, 0, 1, 'L');
}

// $story = [
//     "On the date of {$alert['date']}, an alert was reported regarding an incident. The alert ID is #{$alert['id']}. The person calling for assistance was {$alert['person_reporting']}, who can be reached at {$alert['contact_number']}.",
//     "The source of the signal was reported as: {$alert['alert_from']}. The person reporting the alert indicated that the case was related to the following symptoms: {$alert['symptoms']}.",
//     "The alert was related to a person named {$alert['alert_case_name']}, who is {$alert['alert_case_age']} years old, {$alert['alert_case_sex']}.",
//     "The location of the incident was in the village of {$alert['village']}, located in the parish of {$alert['alert_case_parish']} within the subcounty of {$alert['sub_county']} in the district of {$alert['alert_case_district']}.",
//     "Additionally, the alert reported whether this was a case that had been reported before: {$alert['alert_reported_before']}.",
//     "The emergency contact for this alert is {$alert['point_of_contact_name']}, a {$alert['point_of_contact_relationship']} to the affected individual. The contact number for {$alert['point_of_contact_name']} is {$alert['point_of_contact_phone']}.",
//     "The time of the alert was recorded as {$alert['time']}."
// ];

// // Loop through the story array and print each part
// foreach ($story as $paragraph) {
//     $pdf->MultiCell(0, 10, $paragraph);
//     $pdf->Ln(5); // Add space between paragraphs
// }



// Add a line at the bottom for the signature
$pdf->Ln(15);
$pdf->Cell(0, 10, "__________________________", 0, 1, 'C');
$pdf->Cell(0, 10, "Authorized Signature", 0, 1, 'C');

// Output PDF as a download
$pdf->Output("D", "Alert_{$alert_id}_Details.pdf");

// Close the database connection
$conn->close();
?>
