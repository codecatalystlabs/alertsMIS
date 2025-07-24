<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require('../conn.php');

$updated_id = isset($_GET['updated_id']) ? $_GET['updated_id'] : null;
$affiliation = $_SESSION['affiliation'];
$user_type = $_SESSION['user_type'];
$username = $_SESSION['username'];

// Base query
$sql = "SELECT * FROM alerts";
$params = [];
$types = "";

// Restrict results if affiliation is set
if (!empty($affiliation) && !in_array($affiliation, ['MoH Call Centre', 'EMS', 'REOC'])) {
    $sql .= " WHERE alert_case_district = ?";
    $params[] = $affiliation;
    $types .= "s";
}

// Append ORDER BY clause
$sql .= " ORDER BY date DESC";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../style/style.css" rel="stylesheet" type="text/css">
</head>
<body>
    <?php include('../includes/nav.php'); ?>
    <div class="container mt-2">
        <h2 class="text-center mb-4">Alerts Management</h2>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Call Taker</th>
                       
                        <th>Name of Person</th>
                        
                        <th>Source of Alert</th>
                        <th>Village</th>
                        <th>Alert Case Parish</th>
                        <th>Alert Case District</th>
                        <th>Sub County</th>
                        <th>Contact Number</th>
                        <th>Alert Case Name</th>
                        <th>Alert Case Age</th>
                        <th>Alert Case Sex</th>
                        <th>Alert Case Pregnant Duration</th>
                        <th>Alert Case Village</th>
                        
                        <th>Alert Case Sub County</th>
                        
                        <th>Alert Case Nationality</th>
                        <th>Point of Contact Name</th>
                        <th>Point of Contact Relationship</th>
                        <th>Point of Contact Phone</th>
                        <th>History</th>
                        <th>Health Facility Visit</th>
                        <th>Traditional Healer Visit</th>
                        <th>Symptoms</th>
                        <th>Action Taken</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($des = $result->fetch_assoc()): ?>
                    <tr id="row-<?php echo $des['id']; ?>" class="<?php echo ($updated_id == $des['id']) ? 'highlight' : ''; ?>">
                        <td><?php echo htmlspecialchars($des['status']); ?></td>
                        <td><?php echo htmlspecialchars($des['date']); ?></td>
                        <td><?php echo htmlspecialchars($des['time']); ?></td>
                        <td><?php echo htmlspecialchars($des['call_taker']); ?></td>
                        <td><?php echo htmlspecialchars($des['person_reporting']); ?></td>
                        
                        <td><?php echo htmlspecialchars($des['source_of_alert']); ?></td>
                        <td><?php echo htmlspecialchars($des['village']); ?></td>
                        <td><?php echo htmlspecialchars($des['alert_case_parish']); ?></td>
                        <td><?php echo htmlspecialchars($des['alert_case_district']); ?></td>
                        <td><?php echo htmlspecialchars($des['sub_county']); ?></td>
                        <td><?php echo htmlspecialchars($des['contact_number']); ?></td>
                        <td><?php echo htmlspecialchars($des['alert_case_name']); ?></td>
                        <td><?php echo htmlspecialchars($des['alert_case_age']); ?></td>
                        <td><?php echo htmlspecialchars($des['alert_case_sex']); ?></td>
                        <td><?php echo htmlspecialchars($des['alert_case_pregnant_duration']); ?></td>
                        <td><?php echo htmlspecialchars($des['alert_case_village']); ?></td>
                        
                        <td><?php echo htmlspecialchars($des['alert_case_sub_county']); ?></td>
                        
                        <td><?php echo htmlspecialchars($des['alert_case_nationality']); ?></td>
                        <td><?php echo htmlspecialchars($des['point_of_contact_name']); ?></td>
                        <td><?php echo htmlspecialchars($des['point_of_contact_relationship']); ?></td>
                        <td><?php echo htmlspecialchars($des['point_of_contact_phone']); ?></td>
                        <td><?php echo htmlspecialchars($des['history']); ?></td>
                        <td><?php echo htmlspecialchars($des['health_facility_visit']); ?></td>
                        <td><?php echo htmlspecialchars($des['traditional_healer_visit']); ?></td>
                        <td><?php echo htmlspecialchars($des['symptoms']); ?></td>
                        <td><?php echo htmlspecialchars($des['actions']); ?></td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="alert_verification.php?id=<?php echo $des['id']; ?>">Edit</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let updatedRow = document.querySelector(".highlight");
            if (updatedRow) {
                updatedRow.scrollIntoView({ behavior: "smooth", block: "center" });
            }
        });
    </script>
</body>
</html>
