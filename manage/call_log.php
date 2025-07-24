<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require('../conn.php'); // Database connection

// Session variables
$affiliation  = $_SESSION['affiliation'];
$user_type    = $_SESSION['user_type'];
$username     = $_SESSION['username'];
$level        = $_SESSION['level'];

// Filter inputs (from GET parameters)
$region_filter   = $_GET['region'] ?? '';
$alert_case_district = $_GET['alert_case_district'] ?? '';
$from_date       = $_GET['from_date'] ?? '';
$to_date         = $_GET['to_date'] ?? '';
$alert_id        = $_GET['id'] ?? '';
$alert_case_name = $_GET['alert_case_name'] ?? '';
$person_reporting = $_GET['person_reporting'] ?? '';

// Pagination settings
$limit  = 50;  // Records per page
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base query
// $sql = "SELECT alerts.* FROM alerts 
//         LEFT JOIN districts ON alerts.alert_case_district = districts.id";
$sql = "SELECT * FROM alerts";
        //LEFT JOIN districts ON alerts.alert_case_district = districts.id";

$whereClauses = [];
$params = [];
$types = "";

// Apply filtering based on user type
if ($user_type == "District") {
    $whereClauses[] = "alerts.alert_case_district = ?";
    $params[] = !empty($alert_case_district) ? $alert_case_district : $affiliation;
    $types .= "s";
} elseif ($user_type == "REOC") {
    $whereClauses[] = "alerts.region = ?";
    $params[] = $affiliation;
    $types .= "s";
}

// Apply additional OR-based filters
$orConditions = [];

if (!empty($region_filter)) {
    $orConditions[] = "districts.region_id = ?";
    $params[] = $region_filter;
    $types .= "i";
}

if ($user_type != "District" && !empty($alert_case_district)) {
    $orConditions[] = "alerts.alert_case_district = ?";
    $params[] = $alert_case_district;
    $types .= "s";
}

if (!empty($from_date) && !empty($to_date)) {
    $orConditions[] = "alerts.date BETWEEN ? AND ?";
    $params[] = $from_date;
    $params[] = $to_date;
    $types .= "ss";
}

if (!empty($alert_id)) {
    $orConditions[] = "alerts.id = ?";
    $params[] = $alert_id;
    $types .= "i";
}

if (!empty($alert_case_name)) {
    $orConditions[] = "alerts.alert_case_name LIKE ?";
    $params[] = "%" . $alert_case_name . "%";
    $types .= "s";
}

if (!empty($person_reporting)) {
    $orConditions[] = "alerts.person_reporting LIKE ?";
    $params[] = "%" . $person_reporting . "%";
    $types .= "s";
}
if (!empty($alert_case_district)) {
    $orConditions[] = "alerts.alert_case_district LIKE ?";
    $params[] = "%" . $alert_case_district . "%";
    $types .= "s";
}

// Append WHERE clause
if (!empty($whereClauses) || !empty($orConditions)) {
    $sql .= " WHERE ";

    if (!empty($whereClauses)) {
        $sql .= implode(" AND ", $whereClauses);
    }

    if (!empty($orConditions)) {
        if (!empty($whereClauses)) {
            $sql .= " AND (";
        }
        $sql .= implode(" OR ", $orConditions);
        if (!empty($whereClauses)) {
            $sql .= ")";
        }
    }
}

// Get total record count for pagination
$count_sql = "SELECT COUNT(*) AS total FROM alerts";

if (!empty($whereClauses) || !empty($orConditions)) {
    $count_sql .= " WHERE " . implode(" AND ", $whereClauses);
    
    if (!empty($orConditions)) {
        if (!empty($whereClauses)) {
            $count_sql .= " AND (";
        }
        $count_sql .= implode(" OR ", $orConditions);
        if (!empty($whereClauses)) {
            $count_sql .= ")";
        }
    }
}

$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
$count_stmt->close();

// Append ORDER BY, LIMIT, OFFSET
$sql .= " ORDER BY alerts.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Prepare and execute final query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Store results in an array
$alerts = [];
while ($row = $result->fetch_assoc()) {
    $alerts[] = $row;
}

// Queries to count different alert statuses
$queries = [
    "verified" => "SELECT COUNT(*) AS count FROM alerts WHERE is_verified='1'",
    "not_verified_1h" => "SELECT COUNT(*) AS count FROM alerts WHERE is_verified='0' AND TIMESTAMPDIFF(HOUR, created_at, NOW()) > 1",
    "not_verified_less_1h" => "SELECT COUNT(*) AS count FROM alerts WHERE is_verified='0' AND TIMESTAMPDIFF(HOUR, created_at, NOW()) < 1",
    "not_verified_in_24h" => "SELECT COUNT(*) AS count FROM alerts WHERE is_verified='0' AND TIMESTAMPDIFF(HOUR, created_at, NOW()) > 23"
];

$counts = [];

foreach ($queries as $key => $sql) {
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $counts[$key] = $row['count'];
}

// Assigning counts to variables for clarity
$verified_count = $counts['verified'];
$verified_late_count2 = $counts['not_verified_1h'];
$verified_late_count3 = $counts['not_verified_less_1h'];
$verified_late_count4 = $counts['not_verified_in_24h'];

$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call Log</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="../style/style.css" rel="stylesheet">
    
</head>
<body>
    <?php include('../includes/nav.php'); ?>
    <div class="call_log-body mt-1">
        
        
                <!-- Filter Form -->
        <form method="GET" class="mb-3">
      <div class="row align-items-end">
        <!-- <div class="col-md-2">
          <h5>Alert Call Log</h5>
        </div> -->
        <div class="col-md-2">
          <label for="from_date">District</label>
          <input type="text" name="alert_case_district" id="alert_case_district" class="form-control" value="<?php echo htmlspecialchars($alert_case_district); ?>">
        </div>
        <div class="col-md-2">
          <label for="from_date">Alert Case Name</label>
          <input type="text" name="alert_case_name" id="alert_case_name" class="form-control" value="<?php echo htmlspecialchars($alert_case_name); ?>">
        </div>
        <div class="col-md-2">
          <label for="from_date">Person Reporting</label>
          <input type="text" name="person_reporting" id="person_reporting" class="form-control" value="<?php echo htmlspecialchars($person_reporting); ?>">
        </div>
        <div class="col-md-2">
          <label for="from_date">ID:</label>
          <input type="number" name="id" id="id" class="form-control" value="<?php echo htmlspecialchars($alert_id); ?>">
        </div>
        <div class="col-md-2">
          <label for="from_date">From Date:</label>
          <input type="date" name="from_date" id="from_date" class="form-control" value="<?php echo htmlspecialchars($from_date); ?>">
        </div>
        <div class="col-md-2">
          <label for="to_date">To Date:</label>
          <input type="date" name="to_date" id="to_date" class="form-control" value="<?php echo htmlspecialchars($to_date); ?>">
        </div>
        <div class="col-md-1">
          <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
        <?php if($level === "Admin" || $level === "EOC Manager"): ?>
        <div class="col-md-2">
          <a href="export_excel.php?alert_case_district=<?php echo urlencode($alert_case_district); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>" class="btn btn-success mt-4">Export to Excel</a>
        </div>
    <?php endif; ?>
<div class="row">
    <?php
    // Card data for different alert statuses
    $cards = [
        ["title" => "Verified Alerts within an hour of reporting", "count" => $verified_count, "bg" => "bg-success", "query" => "verified"],
        ["title" => "Alerts Not Verified in 59 minutes", "count" => $verified_late_count3, "bg" => "bg-info bg-gradient", "query" => "not_verified_less_1h"],
        ["title" => "Not Verified for > an hour", "count" => $verified_late_count2, "bg" => "bg-warning bg-gradient", "query" => "not_verified_1h"],
        ["title" => "Not Verified within 24 hours", "count" => $verified_late_count4, "bg" => "bg-danger", "query" => "not_verified_in_24h"]
    ];

    foreach ($cards as $card) :
        ?>
    <div class="col-md-3">
        <div class="card text-white <?php echo $card['bg']; ?> mb-3">
            <div class="card-body">
                <h5 class="card-title"><?php echo $card['title']; ?></h5>
                <button type="button" class="btn btn-primary card-text display-6" data-toggle="modal" data-target=".bd-example-modal-lg" data-query="<?php echo $card['query']; ?>">
                    <?php echo $card['count']; ?>
                </button>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
      </div>
    </form>


        <div class="table-responsive" style="max-height: 1024px; overflow-y: auto;">
    <table class="table table-light table-hover table-bordered">
        <thead>
                <tr>
                    <th rowspan="2">Alert ID</th>
                    <th rowspan="2">Name of Person Calling</th>
                    <th rowspan="2">Source of Signal</th>
                    <th rowspan="2">Person Calling Phone</th>
                    <th rowspan="2">Date</th>
                    <th rowspan="2">Alert reported before?</th>
                    <th colspan="4">Signal location</th>
                    <th colspan="6">Alert Case</th>  
                    <th rowspan="2">Actions</th>
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
            <?php foreach ($alerts as $des): ?>
                <tr id="row-<?php echo $des['id']; ?>" 
    class="
        <?php 
            if ($des['is_verified'] == 1) {
                echo 'verified-row'; // Keep verified rows as they are
            } else {
                $created_at = strtotime($des['created_at']);
                $current_time = time();
                $time_diff = ($current_time - $created_at) / 3600; // Convert seconds to hours

                if ($time_diff > 1) {
                    echo 'red-row'; // More than an hour old, turn red
                } else {
                    echo 'yellow-row'; // Not verified but within an hour, turn yellow
                }
            }
        ?>
    ">
                    <td><?php echo htmlspecialchars($des['id']); ?></td>
                    <td><?php echo htmlspecialchars($des['person_reporting']); ?></td>
                    <td><?php echo htmlspecialchars($des['source_of_alert']); ?></td>
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
                <td><?php echo htmlspecialchars($des['actions']); ?></td>
                    <td><?php echo htmlspecialchars($des['time']); ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm verify-btn" data-id="<?php echo $des['id']; ?>">Verify</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div>
<!-- Pagination -->
<nav>
  <ul class="pagination justify-content-center">
    <?php if ($page > 1): ?>
      <li class="page-item">
        <a class="page-link" href="?page=<?php echo $page - 1; ?>&alert_case_district=<?php echo urlencode($alert_case_district); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>">Previous</a>
      </li>
    <?php endif; ?>

    <?php
      // Determine start and end page
      $start_page = max(1, $page - 1);
      $end_page = min($total_pages, $start_page + 1);

      // Ensure we always show two pages
      if ($end_page - $start_page < 1) {
          $start_page = max(1, $end_page - 1);
      }

      for ($i = $start_page; $i <= $end_page; $i++): 
    ?>
      <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
        <a class="page-link" href="?page=<?php echo $i; ?>&alert_case_district=<?php echo urlencode($alert_case_district); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>"><?php echo $i; ?></a>
      </li>
    <?php endfor; ?>

    <?php if ($page < $total_pages): ?>
      <li class="page-item">
        <a class="page-link" href="?page=<?php echo $page + 1; ?>&alert_case_district=<?php echo urlencode($alert_case_district); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>">Next</a>
      </li>
    <?php endif; ?>
  </ul>
</nav>
<!-- Large modal -->
<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="myLargeModalLabel">Alert Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <!-- Modal Body -->
            <div class="modal-body" id="modal-body">
      <table class="table table-light table-hover table-bordered">
        <thead>
          <tr>
            <th rowspan="2">Alert ID</th>
            <th rowspan="2">Name of Person Calling</th>
            <th rowspan="2">Source of Signal</th>
            <th rowspan="2">Person Calling Phone</th>
            <th rowspan="2">Date</th>
            <th rowspan="2">Alert reported before?</th>
            <th colspan="4">Signal Location</th>
            <th colspan="6">Alert Case</th>
            <th rowspan="2">Time</th>
            <th rowspan="2">Action</th>
            <th rowspan="2">Download</th>
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
        <tbody id="alert-data-tbody"> <!-- No need for modal-body and table classes here -->
        </tbody>
      </table>
    </div>
<!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
  </div>
</div>

<!-- <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script> -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
$(document).ready(function(){
    $('.verify-btn').click(function(){
        var alertId = $(this).data('id');

        $.ajax({
            url: 'generateToken.php',
            type: 'POST',
            data: { alert_id: alertId },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Redirect to verification page with token
                    window.location.href = 'alert_verification.php?token=' + response.token + '&id=' + alertId;
                } else {
                    alert('Error generating token.');
                }
            }
        });
    });
});

$(document).ready(function() {
    // When a card is clicked
    $('.btn-primary').on('click', function() {
        // Get the query type associated with the clicked card
        var queryType = $(this).data('query');
        console.log("Query Type: " + queryType); // Log the query type for debugging
        
        // Clear existing rows before inserting new data
        $('#alert-data-tbody').empty();

        // Make an AJAX request to fetch data for the selected query
        $.ajax({
            url: 'fetch_alert_data.php',  // Path to your PHP file to fetch data
            method: 'POST',
            data: { query: queryType },
            success: function(response) {
                console.log(response);  // Log the response to ensure data is returned
                // Check if the response is valid (HTML rows)
                if (response) {
                    // Insert the fetched data into the modal's table body
                    $('#alert-data-tbody').html(response);
                } else {
                    $('#alert-data-tbody').html('<tr><td colspan="12">No data available</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + status + ": " + error); // Log any AJAX errors
            }
        });
    });
});
</script>

</body>
</html>
