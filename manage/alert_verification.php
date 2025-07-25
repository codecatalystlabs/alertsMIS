<?php
session_start();
require('../conn.php');

// Securely fetch the alert ID from URL
$alert_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$alert_id) {
    die("Invalid Alert ID.");
}

$token = $_GET['token'] ?? null;

// Fetch existing alert data
$stmt = $conn->prepare("SELECT * FROM alerts WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $alert_id);
$stmt->execute();
$alert_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$alert_data) {
    die("Alert ID does not exist in the database.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report'])) {
    if (!$token) {
        die("Missing token.");
    }

    // Validate token
    $stmt = $conn->prepare("SELECT id FROM alert_verification_tokens WHERE alert_id = ? AND token = ? AND used = 0");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("is", $alert_id, $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        die("Invalid or already used token!");
    }
    $stmt->close();

    // Sanitize inputs and prepare data array
    $inputs = [
        'status', 'verification_date', 'verification_time', 'cif_no', 'person_reporting',
        'village', 'sub_county', 'contact_number', 'source_of_alert', 'alert_case_name',
        'alert_case_age', 'alert_case_sex', 'alert_case_pregnant_duration', 'alert_case_village',
        'alert_case_parish', 'alert_case_sub_county', 'alert_case_district', 'alert_case_nationality',
        'point_of_contact_name', 'point_of_contact_relationship', 'point_of_contact_phone',
        'history', 'health_facility_visit', 'traditional_healer_visit', 'symptoms', 'actions', 'feedback', 'is_verified', 'verified_by'
    ];

    $data = [];
    foreach ($inputs as $input) {
        $data[$input] = isset($_POST[$input]) ? (is_array($_POST[$input]) ? $_POST[$input] : trim($_POST[$input])) : null;
    }

    // Convert CIF Number to uppercase
    $data['cif_no'] = isset($data['cif_no']) ? strtoupper($data['cif_no']) : null;

    // Process history field (ensure it's an array before imploding)
    if (isset($_POST['history'])) {
        $data['history'] = is_array($_POST['history']) ? implode(", ", array_map('trim', $_POST['history'])) : trim($_POST['history']);
    } else {
        $data['history'] = null;
    }

    $data['symptoms'] = isset($_POST['symptoms']) ? implode(", ", array_map('trim', $_POST['symptoms'])) : null;
    $data['is_verified'] = 1;

    // Prepare update statement
    $update_sql = "UPDATE alerts 
                   SET status=?, verification_date=?, verification_time=?, cif_no=?, person_reporting=?, village=?, sub_county=?, 
                       contact_number=?, source_of_alert=?, alert_case_name=?, alert_case_age=?, alert_case_sex=?, 
                       alert_case_pregnant_duration=?, alert_case_village=?, alert_case_parish=?, alert_case_sub_county=?, 
                       alert_case_district=?, alert_case_nationality=?, point_of_contact_name=?, point_of_contact_relationship=?, 
                       point_of_contact_phone=?, history=?, health_facility_visit=?, traditional_healer_visit=?, symptoms=?, actions=?, feedback=?, is_verified=?, verified_by=?
                   WHERE id=?";

    $stmt = $conn->prepare($update_sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $data_values = array_values($data);
    $data_values[] = $alert_id;

    $type_definition = str_repeat('s', count($data_values) - 1) . 'i';

    $stmt->bind_param($type_definition, ...$data_values);

    if ($stmt->execute()) {
        // Mark token as used
        $stmt = $conn->prepare("UPDATE alert_verification_tokens SET used = 1 WHERE token = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();

        echo "Alert updated successfully!";

        // Notify EMS if action includes "EMS"
        if (strpos($data['actions'], 'EMS') !== false) {
            // Generate a new verification token
            $new_token = bin2hex(random_bytes(16));

            $stmt = $conn->prepare("INSERT INTO alert_verification_tokens (alert_id, token, used) VALUES (?, ?, 0)");
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("is", $alert_id, $new_token);
            $stmt->execute();
            $stmt->close();

            // Fetch user details
            $stmt = $conn->prepare("SELECT contact_number, person_reporting FROM alerts WHERE id = ?");
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $alert_id);
            $stmt->execute();
            $details = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("SELECT email, gname, surname FROM users WHERE affiliation IN ('EMS', 'MoH Call Centre', 'REOC')");
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->execute();
            $users = $stmt->get_result();

            // Use new token if generated, otherwise use the existing one
            $final_token = isset($new_token) ? $new_token : $token;

            $verification_url = "https://alerts.health.go.ug/manage/alert_verification.php?id=$alert_id&token=$final_token";
            $download_url = "https://alerts.health.go.ug/manage/download_alert.php?id=$alert_id";

            while ($person = $users->fetch_assoc()) {
                $to = $person['email'];
                $subject = "Action needed for alert #$alert_id";

                $message = "Dear EMS Team,\n\n";
                $message .= "After verification, alert #$alert_id needs your attention.\n";
                $message .= "Please contact {$details['person_reporting']} at {$details['contact_number']} for more details.\n\n";
                $message .= "Verify the alert by clicking here: $verification_url\n\n";
                $message .= "Download alert details here: $download_url\n\n";
                $message .= "Best Regards,\nAlerts System";

                $headers = "From: no-reply@alerts.health.go.ug\r\n";
                $headers .= "Reply-To: no-reply@alerts.health.go.ug\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                if (!mail($to, $subject, $message, $headers)) {
                    error_log("Failed to send email to {$person['gname']} {$person['surname']} ($to)");
                }
            }
        }

        header("Location: alert_verification.php?id=$alert_id");
        exit();
    } else {
        die("Error updating alert: " . $stmt->error);
    }

    $stmt->close();
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alert Verification Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Add this to include Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<!-- Only include one version of jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- Add this to include Select2 JS -->
<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
<!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script> -->

    <link href="../style/style.css" rel="stylesheet">
</head>
<body>
    <?php include('../includes/nav.php');?>
   <div id="side-pane-container">
        <?php include("../includes/side-pane.php"); ?>
    </div>
    <div class="entry-screen mt-1">
        <h2 class="text-center mb-2">Alert Verification Form</h2>
        <hr>
        <form method="POST" action="">
            <input type="hidden" name="alert_id" value="<?php echo $alert_data['id']; ?>">
            <div class="mb-2">
                
            </div>
            <div class="row">
            <div class="col-md-3 mb-3">
            <label for="status">Status:</label>
                <select id="status" name="status" required class="form-control">
                    <option value="">---Please select---</option>
                    <option value="Alive" <?= (isset($alert_data['status']) && $alert_data['status'] == 'Alive') ? 'selected' : ''; ?>>Alive</option>
                    <option value="Dead" <?= (isset($alert_data['status']) && $alert_data['status'] == 'Dead') ? 'selected' : ''; ?>>Dead</option>
                </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="date" class="form-label">Verification Date</label>
                    <input type="date" class="form-control" id="verification_date" name="verification_date" value="<?= isset($alert_data['verification_date']) ? htmlspecialchars($alert_data['verification_date']) : ''; ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="verification_time" class="form-label">Verification Time</label>
                    <input type="time" class="form-control" id="verification_time" name="verification_time" value="<?= isset($alert_data['verification_time']) ? htmlspecialchars($alert_data['verification_time']) : ''; ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="cif_no" class="form-label">CIF No</label>
                    <input type="text" class="form-control" id="cif_no" name="cif_no" value="<?= isset($alert_data['cif_no']) ? htmlspecialchars($alert_data['cif_no']) : ''; ?>">
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-3 mb-3">
                <label for="person_reporting" class="form-label">Who reported the Alert</label>
                <input type="text" class="form-control" id="person_reporting" name="person_reporting" value="<?= isset($alert_data['person_reporting']) ? htmlspecialchars($alert_data['person_reporting']) : ''; ?>" disabled>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="source_of_alert">Source of Alert:</label>
                    <select id="source_of_alert" name="source_of_alert" required class="form-control">
                        <option value="">---Please select---</option>
                        <option value="Community" <?= (isset($alert_data['source_of_alert']) && $alert_data['source_of_alert'] == 'Community') ? 'selected' : ''; ?>>Community</option>
                        <option value="Facility" <?= (isset($alert_data['source_of_alert']) && $alert_data['source_of_alert'] == 'Facility') ? 'selected' : ''; ?>>Facility</option>
                        <option value="Contact Tracing">Contact Tracing</option>
                    <option value="VHT" <?= (isset($alert_data['source_of_alert']) && $alert_data['source_of_alert'] == 'VHT') ? 'selected' : ''; ?>>VHT</option>
                    <option value="Active Case Search">Active Case Search</option>
                    <option value="SMS Alert" <?= (isset($alert_data['source_of_alert']) && $alert_data['source_of_alert'] == 'SMS Alert') ? 'selected' : ''; ?>>SMS Alert</option>
                    </select>
            </div>
                <div class="col-md-3 mb-3">
                    <label for="alert_case_village" class="form-label">Village/Institution Name</label>
                    <input type="text" class="form-control" id="alert_case_village" name="alert_case_village" value="<?= isset($alert_data['alert_case_village']) ? htmlspecialchars($alert_data['alert_case_village']) : ''; ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="contact_number" class="form-label">Contact Number</label>
                    <input type="tel" class="form-control" id="contact_number" name="contact_number" value="<?= isset($alert_data['contact_number']) ? htmlspecialchars($alert_data['contact_number']) : ''; ?>">
                </div>
            </div>
            <hr>
            <div class="row">
                
                
                <div class="col-md-3 mb-3">
                <label for="alert_case_name" class="form-label">Name</label>
                <input type="text" class="form-control" id="alert_case_name" name="alert_case_name" value="<?= isset($alert_data['alert_case_name']) ? htmlspecialchars($alert_data['alert_case_name']) : ''; ?>">
            </div>
            <div class="col-md-3 mb-3">
                    <label for="alert_case_age" class="form-label">Age</label>
                    <input type="number" class="form-control" id="alert_case_age" name="alert_case_age" value="<?= isset($alert_data['alert_case_age']) ? htmlspecialchars($alert_data['alert_case_age']) : ''; ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="alert_case_sex">Sex:</label>
                        <select id="alert_case_sex" name="alert_case_sex" required class="form-control">
                            <option value="">---Please select---</option>
                            <option value="Male" <?= (isset($alert_data['alert_case_sex']) && $alert_data['alert_case_sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?= (isset($alert_data['alert_case_sex']) && $alert_data['alert_case_sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="alert_case_pregnant_duration" class="form-label">Pregnant Duration</label>
                    <input type="number" class="form-control" id="alert_case_pregnant_duration" name="alert_case_pregnant_duration" placeholder="(In Months)" 
                        value="<?= isset($alert_data['alert_case_pregnant_duration']) && $alert_data['alert_case_pregnant_duration'] !== '' ? htmlspecialchars($alert_data['alert_case_pregnant_duration']) : 0; ?>">
                </div>

            </div>
            <div class="row">
                
            <hr>
                
        </div>
            <div class="row"> 
                <div class="col-md-3 mb-3">
                    <label>Region:</label>
                        <select name="region" id="region" class="form-control">
                            <option value="">Select Region</option>
                            <?php foreach ($regions as $region): ?>
                                <option value="<?= $region['region'] ?>" <?= ($saved_region == $region['id']) ? 'selected' : '' ?>>
                                    <?= $region['region'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                </div>

                <div class="col-md-3 mb-3">
                <label for="district">District:</label>
                    <select id="district" name="alert_case_district" class="form-control">
                        <option value="">-- Select District --</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="subcounty">Subcounty:</label>
                    <select id="subcounty" name="subcounty" class="form-control">
                        <option value="">-- Select Subcounty --</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="alert_case_parish" class="form-label">Parish</label>
                    <input type="text" class="form-control" id="alert_case_parish" name="alert_case_parish" value="<?= isset($alert_data['alert_case_parish']) ? htmlspecialchars($alert_data['alert_case_parish']) : ''; ?>">
                </div>
            </div>
            <hr>
            <div class="row">
                
                  <div class="col-md-3 mb-3">
                    <label for="nationality">Nationality:</label>
                        <select id="nationality" name="nationality" required class="form-control">
                            <option value="Ugandan" <?= (isset($alert_data['nationality']) && $alert_data['nationality'] == 'Ugandan') ? 'selected' : ''; ?>>Ugandan</option>
                            <option value="Other" <?= (isset($alert_data['nationality']) && $alert_data['nationality'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                </div>
            <div class="col-md-3 mb-3">
                <label for="point_of_contact_name" class="form-label">Point of Contact Name</label>
                <input type="text" class="form-control" id="point_of_contact_name" name="point_of_contact_name" value="<?= isset($alert_data['point_of_contact_name']) ? htmlspecialchars($alert_data['point_of_contact_name']) : ''; ?>">
            </div>
        
          
                <div class="col-md-3 mb-3">
                    <label for="point_of_contact_relationship" class="form-label">Relationship</label>
                    <input type="text" class="form-control" id="point_of_contact_relationship" name="point_of_contact_relationship" value="<?= isset($alert_data['point_of_contact_relationship']) ? htmlspecialchars($alert_data['point_of_contact_relationship']) : ''; ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="point_of_contact_phone" class="form-label">Phone</label>
                    <input type="tel" class="form-control" id="point_of_contact_phone" name="point_of_contact_phone" value="<?= isset($alert_data['point_of_contact_phone']) ? htmlspecialchars($alert_data['point_of_contact_phone']) : ''; ?>">
                </div>
            </div>
            <hr>
            <div class="mb-3">
                <label for="history" class="form-label"><strong>History (Last 21 Days)</strong></label></br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="Other mass gathering" id="mass_gathering" name="history[]" <?= (isset($alert_data['history']) && strpos($alert_data['history'], 'Other mass gathering') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="mass_gathering">Other mass gathering</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="Contact of suspect/probable/confirmed case" id="contact_case" name="history[]" <?= (isset($alert_data['history']) && strpos($alert_data['history'], 'Contact of suspect/probable/confirmed case') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="contact_case">Contact of suspect/probable/confirmed case</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="Contact of sudden/unexplained death" id="unexplained_death" name="history[]" <?= (isset($alert_data['history']) && strpos($alert_data['history'], 'Contact of sudden/unexplained death') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="unexplained_death">Contact of sudden/unexplained death</label>
                </div>
            </div>
            <div class="row">
            <div class=" col-md-6 mb-3">
                <label for="health_facility_visit" class="form-label">Visited Health Facility</label>
                <input class="form-control" id="health_facility_visit" name="health_facility_visit" rows="2" placeholder="Include date, facility name, and contact/location.">
            </div>
            <div class=" col-md-6 mb-3">
                <label for="traditional_healer_visit" class="form-label">Visited Traditional Healer</label>
                <input class="form-control" id="traditional_healer_visit" name="traditional_healer_visit" rows="2" placeholder="Include date, healer name, and contact/location.">
            </div>
        </div>
            <hr>
            <div class="mb-3">
                <label for="symptoms" class="form-label"><strong>Signs and Symptoms</strong></label></br>
                
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="Fever" id="fever" name="symptoms[]" <?= (isset($alert_data['symptoms']) && strpos($alert_data['symptoms'], 'Fever') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="fever">Fever (&ge;38&deg;C)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="Headache" id="headache" name="symptoms[]" <?= (isset($alert_data['symptoms']) && strpos($alert_data['symptoms'], 'Headache') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="headache">Headache</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="General Weakness" id="weakness" name="symptoms[]" <?= (isset($alert_data['symptoms']) && strpos($alert_data['symptoms'], 'General Weakness') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="weakness">General Weakness</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="Rash" id="rash" name="symptoms[]" <?= (isset($alert_data['symptoms']) && strpos($alert_data['symptoms'], 'rash') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="rash">Skin/Body Rash</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="Sore Throat" id="sore_throat" name="symptoms[]" <?= (isset($alert_data['symptoms']) && strpos($alert_data['symptoms'], 'Sore Throat') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="sore_throat">Sore Throat</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="vomiting" id="vomiting" name="symptoms[]" <?= (isset($alert_data['symptoms']) && strpos($alert_data['symptoms'], 'vomiting') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="vomiting">Vomiting</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="bleeding" id="bleeding" name="symptoms[]" <?= (isset($alert_data['symptoms']) && strpos($alert_data['symptoms'], 'bleeding') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="bleeding">Bleeding</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="Abdominal Pain" id="Abdominal Pain" name="symptoms[]" <?= (isset($alert_data['symptoms']) && strpos($alert_data['symptoms'], 'abdominal_pain') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="Abdominal Pain">Abdominal Pain</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="Aching Muscles/Joints" id="aching_muscle" name="symptoms[]" <?= (isset($alert_data['symptoms']) && strpos($alert_data['symptoms'], 'Aching Muscles/Joints') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="aching_muscle">Aching Muscles/ Pain</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="Difficulty Swallowing" id="difficult_swallowing" name="symptoms[]" <?= (isset($alert_data['symptoms']) && strpos($alert_data['symptoms'], 'Difficulty Swallowing') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="Difficulty Swallowing">Difficulty Swallowing</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="Difficulty Breathing" id="difficulty_breathing" name="symptoms[]" <?= (isset($alert_data['symptoms']) && strpos($alert_data['symptoms'], 'Difficulty Breathing') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="Difficulty Breathing">Difficulty Breathing</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="Lethergy/Weakness" id="lethergy_weakness" name="symptoms[]" <?= (isset($alert_data['symptoms']) && strpos($alert_data['symptoms'], 'Lethergy/Weakness') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="Lethergy/Weakness">Lethergy/Weakness</label>
                </div>
            </div>
       
            <hr>
            <div class="mb-3" class="dva">
                <label for="actions" class="form-label"><strong>Desk Verification Actions</strong></label></br>
                
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" value="Field Case Verification" id="Field_Case_Verification" name="actions" <?= (isset($alert_data['actions']) && strpos($alert_data['actions'], 'Case Verification') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="Field_Case_Verification">Field Case Verification</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" value="Discarded" id="Discarded" name="actions" <?= (isset($alert_data['actions']) && strpos($alert_data['actions'], 'Discarded') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="Discarded">Discarded</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" value="Validated for EMS Evacuation" id="Validated_for_EMS_Evacuation" name="actions" <?= (isset($alert_data['actions']) && strpos($alert_data['actions'], 'Validated for EMS Evacuation') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="Validated_for_EMS_Evacuation">Validated for EMS Evacuation</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" value="Mortality Survaillance/Supervised Burial" id="Supervised_Burial" name="actions" <?= (isset($alert_data['actions']) && strpos($alert_data['actions'], 'Mortality Survaillance/Supervised Burial') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="Supervised_Burial">Mortality Survaillance/Supervised Burial</label>
                </div>
            </div>
            <div class="row">
                
            <div class="mb-3" id="field">
                <label for="feedback" class="form-label"><strong>Field Verification Feedback</strong></label></br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" value="SDB" id="SDB" name="feedback" <?= (isset($alert_data['feedback']) && strpos($alert_data['feedback'], 'SDB') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="SDB">SDB</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" value="Discard" id="Discard" name="feedback" <?= (isset($alert_data['feedback']) && strpos($alert_data['feedback'], 'Discard') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="Discard">Discard</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" value="Sample Collection" name="feedback" <?= (isset($alert_data['feedback']) && strpos($alert_data['feedback'], 'Sample_Collection') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="Sample_Collection">Sample collection</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" value="Mortality Surveillance/Supervised Burial" id="Supervised_Burial" name="feedback" <?= (isset($alert_data['feedback']) && strpos($alert_data['feedback'], 'Mortality Surveillance/Supervised Burial') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="Supervised_Burial">Mortality Surveillance/Supervised Burial</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" value="Reccomend for Evacuation" id="Reccomend_for_Evacuation" name="feedback" <?= (isset($alert_data['feedback']) && strpos($alert_data['feedback'], 'Reccomend for Evacuation') !== false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="Reccomend_for_Evacuation">Reccomend for Evacuation</label>
                </div>
            </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-3 mb-3">
                <label for="verified_by" class="form-label">Verified By:</label>
                <input type="text" class="form-control" id="verified_by" name="verified_by" value="<?= isset($alert_data['verified_by']) ? htmlspecialchars($alert_data['verified_by']) : ''; ?>" required>
            </div>
            </div>
            <button type="submit" class="btn btn-primary" name="report">Submit</button>
        </form>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        
    function refreshSidePane() {
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                document.getElementById("side-pane-container").innerHTML = xhr.responseText;
            }
        };
        xhr.open("GET", "includes/side-pane.php", true);
        xhr.send();
    }

    // Refresh every 3 minutes (180000 milliseconds)
    setInterval(refreshSidePane, 15000);
    document.addEventListener("DOMContentLoaded", function () {
        // Restrict date input to today or earlier
        let dateInput = document.getElementById("verification_date");
        let today = new Date().toISOString().split("T")[0];
        dateInput.setAttribute("max", today);

        // Get the necessary elements
        let sexInput = document.getElementById("alert_case_sex");
        let ageInput = document.getElementById("alert_case_age");
        let pregnancyDurationInput = document.getElementById("alert_case_pregnant_durations");

        function togglePregnancyField() {
            let sex = sexInput.value;
            let age = parseInt(ageInput.value, 10);

            if (sex === "Male" || (age && age < 13)) {
                pregnancyDurationInput.value = ""; // Clear input
                pregnancyDurationInput.setAttribute("disabled", "disabled");
            } else {
                pregnancyDurationInput.removeAttribute("disabled");
            }
        }

        // Attach event listeners
        sexInput.addEventListener("change", togglePregnancyField);
        ageInput.addEventListener("input", togglePregnancyField);

        // Call function on page load to apply rules if fields are prefilled
        togglePregnancyField();
    });

    $(document).ready(function(){
    var savedDistrict = $("#district").data("saved");
    var savedSubcounty = $("#subcounty").data("saved");

    // When a region is selected, load districts
    $('#regions').change(function(){
        var regionId = $(this).val();
        console.log('Region selected:', regionId);
        
        $.ajax({
            url: 'getDistrict.php',
            type: 'POST',
            data: { region: regionId },
            dataType: 'json',
            success: function(response) {
                console.log('District response:', response);
                $("#district").empty().append("<option value=''>-- Select District --</option>");
                $("#subcounty").empty().append("<option value=''>-- Select Subcounty --</option>");

                $.each(response, function(index, district) {
                    var selected = (district.id == savedDistrict) ? "selected" : "";
                    $("#district").append("<option value='" + district.id + "' " + selected + ">" + district.district + "</option>");
                });

                // If there's a saved district, trigger change to load subcounties
                if (savedDistrict) {
                    $("#district").trigger("change");
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error loading districts:', textStatus, errorThrown);
            }
        });
    });

    // When a district is selected, load subcounties
    $('#districts').change(function(){
        var districtId = $(this).val();
        console.log('District selected:', districtId);

        $.ajax({
            url: 'getSubcounties.php',
            type: 'POST',
            data: { district: districtId },
            dataType: 'json',
            success: function(response) {
                console.log('Subcounty response:', response);
                $("#subcounty").empty().append("<option value=''>-- Select Subcounty --</option>");

                $.each(response, function(index, subcounty) {
                    var selected = (subcounty.id == savedSubcounty) ? "selected" : "";
                    $("#subcounty").append("<option value='" + subcounty.id + "' " + selected + ">" + subcounty.subcounty + "</option>");
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error loading subcounties:', textStatus, errorThrown);
            }
        });
    });

    // Auto-load districts if a region is preselected
    var regionId = $("#region").val();
    if (regionId) {
        $("#region").trigger("change");
    }
});


//============================================
// Switch for desk and field verification
//============================================
    $(document).ready(function() {
    function toggleFieldCaseVerification() {
        var selectedValue = $('input[name="actions"]:checked').val();
        
        if (selectedValue === "Field Case Verification") {  
            $('#field').show();
        } else {
            $('#field').hide();
        }
    }

    // Check on page load
    toggleFieldCaseVerification();

    // Attach event listener to radio buttons with name "actions"
    $('input[name="actions"]').change(function() {
        toggleFieldCaseVerification();
    });
});


</script>

</body>
</html>