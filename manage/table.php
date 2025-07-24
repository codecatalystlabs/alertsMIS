<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clean Table Design</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        /* Full-width table */
        .table-container {
            width: 100%;
            overflow: auto;
            max-height: 80vh; /* Prevent excessive scrolling */
            position: relative;
        }

        /* Freeze first row */
        .table thead {
            position: sticky;
            top: 0;
            background-color: #343a40; /* Dark header */
            z-index: 100;
        }

        /* Alternating row colors */
        .table tbody tr:nth-child(odd) {
            background-color: #f8f9fa;
        }

        .table tbody tr:nth-child(even) {
            background-color: #e9ecef;
        }

        /* Table Styling */
        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
            padding: 10px;
        }
    </style>
</head>
<body>

<div class="container-fluid mt-3">
    <div class="table-container">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th rowspan="2">Name of Person Calling</th>
                    <th rowspan="2">Source of Signal</th>
                    <th rowspan="2">Person Calling Phone</th>
                    <th rowspan="2">Date</th>
                    <th rowspan="2">Alert reported before?</th>
                    <th colspan="4">Signal Location</th>
                    <th colspan="6">Alert Case</th>  
                    <th rowspan="2">Time</th>
                    <th rowspan="2">Action</th>
                </tr>
                <tr>
                    <th>Village</th>
                    <th>Parish</th>
                    <th>Subcounty</th>
                    <th>District</th>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Sex</th>
                    <th>Contact</th>
                    <th>Next of Kin</th>
                    <th>Contact of Next of Kin</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($des = $result->fetch_assoc()): ?>
                    <?php
                    // Generate a unique one-time token
                    $token = generateToken();
                    $alert_id = $des['id'];
                    $expires_at = date('Y-m-d H:i:s', strtotime('+20 hour'));

                    // Insert token into the database
                    $stmt = $conn->prepare("INSERT INTO alert_verification_tokens (alert_id, token, expires_at) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $alert_id, $token, $expires_at);
                    $stmt->execute();
                    ?>
                <tr id="row-<?php echo $des['id']; ?>" class="<?php echo ($updated_id == $des['id']) ? 'highlight' : ''; ?>">
                    <td><?php echo htmlspecialchars($des['person_reporting']); ?></td>
                    <td><?php echo htmlspecialchars($des['alert_from']); ?></td>
                    <td><?php echo htmlspecialchars($des['contact_number']); ?></td>
                    <td><?php echo htmlspecialchars($des['date']); ?></td>
                    <td><?php echo htmlspecialchars($des['alert_reported_before']); ?></td>
                    <td><?php echo htmlspecialchars($des['village']); ?></td>
                    <td><?php echo htmlspecialchars($des['alert_case_parish']); ?></td>
                    <td><?php echo htmlspecialchars($des['sub_county']); ?></td>
                    <td><?php echo htmlspecialchars($des['alert_case_district']); ?></td>                
                    <td><?php echo htmlspecialchars($des['alert_case_name']); ?></td>
                    <td><?php echo htmlspecialchars($des['alert_case_age']); ?></td>
                    <td><?php echo htmlspecialchars($des['alert_case_sex']); ?></td>
                    <td><?php echo htmlspecialchars($des['point_of_contact_name']); ?></td>
                    <td><?php echo htmlspecialchars($des['point_of_contact_relationship']); ?></td>
                    <td><?php echo htmlspecialchars($des['point_of_contact_phone']); ?></td>
                    <td><?php echo htmlspecialchars($des['time']); ?></td>
                    <td>
                        <a class="btn btn-primary btn-sm" href="alert_verification.php?token=<?php echo $token; ?>&id=<?php echo $alert_id; ?>">Verify</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
