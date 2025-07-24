<?php
// fetch_alert_data.php
include('../conn.php'); // Ensure you have a database connection here

if (isset($_POST['query'])) {
    $queryType = $_POST['query'];

    // Define queries based on query type
    $queries = [
        "verified" => "SELECT * FROM alerts WHERE is_verified='1'",
        "not_verified_1h" => "SELECT * FROM alerts WHERE is_verified='0' AND TIMESTAMPDIFF(HOUR, created_at, NOW()) > 1",
        "not_verified_less_1h" => "SELECT * FROM alerts WHERE is_verified='0' AND TIMESTAMPDIFF(HOUR, created_at, NOW()) < 1",
        "not_verified_in_24h" => "SELECT * FROM alerts WHERE is_verified='0' AND TIMESTAMPDIFF(HOUR, created_at, NOW()) > 23"
    ];

    if (isset($queries[$queryType])) {
        $sql = $queries[$queryType];
        $result = mysqli_query($conn, $sql);
        
        // Loop through the result and build table rows
        $rows = "";
        while ($row = mysqli_fetch_assoc($result)) {
            // Get the created_at time and calculate the time difference
            $created_at = strtotime($row['created_at']);
            $current_time = time();
            $time_diff = ($current_time - $created_at) / 3600; // Convert seconds to hours
            
            // Determine the row class based on the verification status and time difference
            $row_class = '';
            if ($row['is_verified'] == 1) {
                $row_class = 'verified-row'; // Verified rows
            } else {
                if ($time_diff > 1) {
                    $row_class = 'red-row'; // More than an hour old
                } else {
                    $row_class = 'yellow-row'; // Not verified but within an hour
                }
            }

            // Create the table row with dynamic class
            $rows .= "<tr id='row-{$row['id']}' class='{$row_class}'>";
            $rows .= "<td>" . htmlspecialchars($row['id']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['person_reporting']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['alert_from']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['contact_number']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['date']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['alert_reported_before']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['village']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['alert_case_parish']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['sub_county']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['alert_case_district']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['alert_case_name']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['alert_case_age']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['alert_case_sex']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['point_of_contact_name']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['point_of_contact_relationship']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['point_of_contact_phone']) . "</td>";
            $rows .= "<td>" . htmlspecialchars($row['time']) . "</td>";
            $rows .= "<td><button class='btn btn-primary btn-sm verify-btn' data-id='{$row['id']}'>Verify</button></td>";
            $rows .= "<td><button class='btn btn-primary btn-sm verify-btn'><a href='download_alert.php?id={$row['id']}'>Download</a></button></td>";
            $rows .= "</tr>";
        }

        // Output the rows
        echo $rows;
    }
}
?>
