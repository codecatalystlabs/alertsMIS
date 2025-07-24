<?php
session_start();
require('../conn.php');
if (isset($_SESSION['username'])) {
    $added_by = $_SESSION['username'];
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Fetch admin units
$sql2 = "SELECT id, district FROM districts";
$result2 = $conn->query($sql2);

// Handle form submission
if (isset($_POST['report'])) {
    // Sanitize form data
    $alert_reported_before = mysqli_real_escape_string($conn, $_POST['alert_reported_before']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);
    $person_reporting = mysqli_real_escape_string($conn, $_POST['person_reporting']);
    $village = mysqli_real_escape_string($conn, $_POST['village']);
    $sub_county = mysqli_real_escape_string($conn, $_POST['subcounty']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $alert_case_name = mysqli_real_escape_string($conn, $_POST['alert_case_name']);
    $alert_case_age = mysqli_real_escape_string($conn, $_POST['alert_case_age']);
    $alert_case_sex = mysqli_real_escape_string($conn, $_POST['alert_case_sex']);
    $alert_case_parish = mysqli_real_escape_string($conn, $_POST['alert_case_parish']);
    $point_of_contact_name = mysqli_real_escape_string($conn, $_POST['point_of_contact_name']);
    $point_of_contact_phone = mysqli_real_escape_string($conn, $_POST['point_of_contact_phone']);
    $alert_case_district = mysqli_real_escape_string($conn, $_POST['alert_case_district']);
    $source_of_alert = mysqli_real_escape_string($conn, $_POST['source_of_alert']);
    $alert_from = 'Open Alerts';
    $narrative = mysqli_real_escape_string($conn, $_POST['narrative']);
    $response = mysqli_real_escape_string($conn, $_POST['response']);
    $symptoms = isset($_POST['symptoms']) ? implode(", ", array_map(fn($symptom) => mysqli_real_escape_string($conn, $symptom), $_POST['symptoms'])) : null;
    $facilityType = mysqli_real_escape_string($conn, $_POST['facilityType']);
    $facility = mysqli_real_escape_string($conn, $_POST['facility']);

    // Insert data into alerts table
    $sql = "INSERT INTO alerts (date, time, person_reporting, village, sub_county, contact_number, alert_case_name, alert_case_age, alert_case_sex, alert_case_parish, point_of_contact_name, point_of_contact_phone, alert_reported_before, alert_case_district, alert_from, symptoms, source_of_alert, response,narrative,facilityType,facility)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssisssssssssssss", 
        $date, $time, $person_reporting, $village, $sub_county, $contact_number, 
        $alert_case_name, $alert_case_age, $alert_case_sex, $alert_case_parish, 
        $point_of_contact_name, $point_of_contact_phone, $alert_reported_before, 
        $alert_case_district, $alert_from, $symptoms, $source_of_alert, $response,$narrative,$facilityType,$facility
    );

    if ($stmt->execute()) {
        // // Update district and subcounty after inserting the alert
        // $district_change = "UPDATE alerts a
        // JOIN districts d ON a.alert_case_district = d.id
        // SET a.alert_case_district = d.district
        // WHERE a.alert_case_district = ?";
        // $stmt2 = $conn->prepare($district_change);
        // $stmt2->bind_param("i", $alert_case_district);
        // $stmt2->execute();
        // $stmt2->close();

        // $subcounty_change = "UPDATE alerts a
        // JOIN subcounties s ON a.sub_county = s.id
        // SET a.sub_county = s.subcounty
        // WHERE a.sub_county = ?";
        // $stmt3 = $conn->prepare($subcounty_change);
        // $stmt3->bind_param("i", $sub_county);
        // $stmt3->execute();
        // $stmt3->close();

        // Confirmation and redirection
        echo "<script>
            alert('Alert submitted successfully!');
            window.location.href = '" . $_SERVER['PHP_SELF'] . "';
        </script>";
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alert Call Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Add this to include Select2 CSS -->
    <link href="style/style.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

<!-- Add this to include Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    
</head>
<body>
   <?php include('../includes/nav.php');?>
    <div class="entry-screen-index-open mt-4">
        <h2 class="text-center mb-4">Alert Call Log</h2>
        <hr>
        <form action="" method="POST">
            <div class="row">
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="date" class="form-label">Call Time</label>
                    <input type="time" class="form-control" id="time" name="time">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="alert_case_sex" class="form-label">Alert reported before?</label>
                    <select class="form-select" id="alert_reported_before" name="alert_reported_before" required>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="call_taker" class="form-label">Name of person reporting</label>
                    <input type="text" class="form-control" id="person_reporting" name="person_reporting">
                </div>
            </div>
            <div class="row">
                
                <div class="col-md-3 mb-3 mt-1">
                    <label for="contact_number" class="form-label">Number of person reporting</label>
                    <input type="tel" class="form-control" id="contact_number" name="contact_number">
                </div>
                <div class="col-md-3 mb-3">
            <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status"> 
                    <option value="">---Select Status---</option>
                    <option value="Alive">Alive</option>
                    <option value="Dead">Dead</option>
                </select>
                </div>
                <div class="col-md-3 mb-3">
            <label for="status" class="form-label">Response</label>
                <select class="form-select" id="response" name="response"> 
                    <option value="">---Select Response---</option>
                    <option value="EVD">EVD</option>
                    <option value="Mpox">Mpox</option>
                    <option value="Marburg">Marburg</option>
                </select>
                </div>
            </div>
            <hr>
            <div class="row">
                <h3>Alert Location</h3>
                <div class="col-md-2 mb-3">
                    <label for="alert_case_district" class="form-label">District</label>
                        <select class="form-select" id="alert_case_districts" name="alert_case_district">
                            <option value="">-- Select District --</option>
                            <?php while ($row = $result2->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($row['id']) ?>">
                                    <?= htmlspecialchars($row['district']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>                               
                </div>
                <div class="col-md-2 mb-3">
                    <label for="subcounty">Subcounty/Division:</label>
                    <select id="subcounty" name="subcounty" class="form-control">
                        <option value="">-- Select Subcounty --</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="village" class="form-label">Village</label>
                    <input type="text" class="form-control" id="village" name="village">
                </div>
                <div class="col-md-2 mb-3">
                    <label for="alert_case_parish" class="form-label">Parish</label>
                    <input type="text" class="form-control" id="alert_case_parish" name="alert_case_parish">
                </div>
                
                
                <div class="col-md-2 mb-3">
                <label for="source_of_alert" class="form-label">Source of Alert</label>
                <select class="form-select" id="source_of_alert" name="source_of_alert">
                    <option value="">---Select source of alert---</option>
                    <option value="Community">Community</option>
                    <option value="Health Facility">Health Facility</option>
                    <option value="Contact Tracing">Contact Tracing</option>
                    <option value="VHT">VHT</option>
                    <option value="Active Case Search">Active Case Search</option>
                    <option value="SMS Alert">SMS Alert</option>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label for="facilityType" class="form-label" id="facilityLabel">Facility Type</label>
                <select class="form-select" id="facilityType" name="facilityType">
                    <option value="">---Select Facility Type---</option>
                    <option value="GOV">Public</option>
                    <option value="PFP">Private For Profit</option>
                    <option value="PNFP">Private Not For Profit</option>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label for="facility" id="facilityLabel2">Facility</label>
                <select id="facility" name="facility" class="form-control">
                    <option value="">-- Select Facility --</option>
                </select>
            </div>
            <hr>
            </div>
            <div class="row">
                <h3>Case Alert Description</h3>
            <div class="col-md-2 mb-3">
                <label for="alert_case_name" class="form-label">Case Name</label>
                <input type="text" class="form-control" id="alert_case_name" name="alert_case_name">
            </div>
            <div class="col-md-2 mb-3">
                    <label for="alert_case_age" class="form-label">Case Age</label>
                    <input type="number" class="form-control" id="alert_case_age" name="alert_case_age">
                </div>
                <div class="col-md-2 mb-3">
                    <label for="alert_case_sex" class="form-label">Case Sex</label>
                    <select class="form-select" id="alert_case_sex" name="alert_case_sex">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                 <div class="col-md-2 mb-3">
                <label for="point_of_contact_name" class="form-label">Name of Next of Kin</label>
                <input type="text" class="form-control" id="point_of_contact_name" name="point_of_contact_name">
            </div>
            <div class="col-md-3 mb-3">
                <label for="point_of_contact_phone" class="form-label">Next of Kin Phone Number</label>
                <input type="tel" class="form-control" id="point_of_contact_phone" name="point_of_contact_phone">
            </div>
        </div>
        <div class="row">
            <div class="col-md-24 input-lg mb-3">
                <label for="narrative" class="form-label">Narrative/Short Description</label></br>
                <textarea class="textarea" placeholder="Please provide a Short narrative of not mpre than 50 words"></textarea>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="mb-3">
                <h3>Signs and Symptoms</h3>
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
        let dateInput = document.getElementById("date");
        let today = new Date().toISOString().split("T")[0];
        dateInput.setAttribute("max", today);

        // Get the necessary elements
        let sexInput = document.getElementById("alert_case_sex");
        let ageInput = document.getElementById("alert_case_age");
        let pregnancyDurationInput = document.getElementById("alert_case_pregnant_duration");

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
    $(document).ready(function() {
    // Initialize select2 for search functionality
    $('#alert_case_district').select2({
        placeholder: "Search for a district...",
        allowClear: true,  // Allow clearing the selection
        ajax: {
            url: 'manage/getDistrict.php',  // Endpoint to fetch data dynamically
            dataType: 'json',
            delay: 250,  // Delay to avoid too many requests on each keystroke
            processResults: function(data) {
                return {
                    results: data  // Process the result and return it
                };
            },
            cache: true
        },
        minimumInputLength: 1 // Minimum input length before search is triggered
    });
});
    document.addEventListener("DOMContentLoaded", function () {
        let timeInput = document.getElementById("time");

        function convertTo12HourFormat(hours, minutes) {
            let period = "AM";
            if (hours >= 12) {
                period = "PM";
                if (hours > 12) {
                    hours -= 12;
                }
            }
            if (hours === 0) {
                hours = 12;
            }
            return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")} ${period}`;
        }

        function setMaxTime() {
            let now = new Date();
            let hours = now.getHours();
            let minutes = now.getMinutes();
            let currentTime = convertTo12HourFormat(hours, minutes);

            // Set the 'max' attribute as a 12-hour formatted time
            timeInput.setAttribute("max", currentTime);
        }

        setMaxTime();
        setInterval(setMaxTime, 60000);
    });

       $(document).ready(function(){
    // When a region is selected, load districts
    $('#region').change(function(){
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
                    $("#district").append("<option value='" + district.id + "'>" + district.district + "</option>");
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error loading districts:', textStatus, errorThrown);
            }
        });
    });

    // When a district is selected, load subcounties
    $('#alert_case_districts').change(function(){
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
                    $("#subcounty").append("<option value='" + subcounty.id + "'>" + subcounty.subcounty + "</option>");
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error loading subcounties:', textStatus, errorThrown);
            }
        });
    });
});
$(document).ready(function(){
   function toggleSourceOfAlert(){
    var source = document.getElementById('source_of_alert').value;
    var facilityTypes = document.getElementById('facilityType').value;
    if (source === "Health Facility") {
        $('#facilityType').show();
        $('#facilityLabel').show();
    }else{
        $('#facilityType, #facilityLabel').hide();
    }
    if (source === "Health Facility" && facilityTypes === "GOV" || facilityTypes === "PFP" || facilityTypes === "PNFP") {
        $('#facilityLabel2').show();
        $('#facility').show();
    }else{
        $('#facilityLabel2,#facility').hide();
    }
   }
   toggleSourceOfAlert();
   $('#source_of_alert').change(function(){
    toggleSourceOfAlert();
   });
   $('#facilityType').change(function(){
    toggleSourceOfAlert();
   });
});
   // When a subcounty is selected, load facilities
$('#subcounty').change(function() {
    var subcountyId = $(this).val(); // Get selected subcounty
    var facilityType = $('#facilityType').val(); // Get selected facility type
    console.log('Subcounty selected:', subcountyId, 'Facility type:', facilityType);
    $.ajax({
        url: 'getFacilities.php',
        type: 'POST',
        data: { subcounty: subcountyId, facilityType: facilityType }, // Pass both subcounty and facilityType
        dataType: 'json',
        success: function(response) {
            console.log('Subcounty response:', response);
            $("#facility").empty().append("<option value=''>-- Select Facility --</option>");
            $.each(response, function(index, facility) {
                $("#facility").append("<option value='" + facility.facility + "'>" + facility.facility + "</option>");
            });
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error loading Facilities:', textStatus, errorThrown);
        }
    });
});
</script>

</body>
</html>