<?php

// Include the database connection file
include 'connection.php';


// Start the session
session_start();

// Default user role is `client` for not logged in
$user_role = 'client';

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Fetch user role from the database
    $query = "SELECT user_role FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_result->num_rows > 0) {
        $row = $user_result->fetch_assoc();
        $user_role = $row['user_role'];
    }
}

// Query the unique cities (locations) from the database
$locations_query = "SELECT DISTINCT location FROM bike_detail";
$locations_result = mysqli_query($conn, $locations_query); // Use a unique variable

// Fetch the cities into an array
$locations = [];
while ($row = mysqli_fetch_assoc($locations_result)) {
    $locations[] = htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8');
}

// Query the unique brands from the database
$brands_query = "SELECT DISTINCT brand FROM bike_detail";
$brands_result = mysqli_query($conn, $brands_query); // Use a unique variable

// Fetch the brands into an array
$brands = [];
while ($row = mysqli_fetch_assoc($brands_result)) {
    $brands[] = htmlspecialchars($row['brand'], ENT_QUOTES, 'UTF-8');
}

$sql = "SELECT * FROM users WHERE user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
}
// Define the statuses for filtering
$statuses = ['Accepted', 'Denied', 'Pending'];

// Check for selected status in URL parameters (optional filtering)
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';


// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// You should replace this with the actual logic to get the user's ID (e.g., using $_SESSION)
$user_id = $_SESSION['user_id'];  // Replace with actual method for getting the logged-in user's ID

// Construct the query to retrieve motorcycles based on the selected status and user
$query = "
    SELECT selling.*, selling.status
    FROM selling
    JOIN selling_requests ON selling.id = selling_requests.selling_id
    WHERE selling_requests.user_id = '$user_id'";

// Add condition to filter by status if provided
if ($statusFilter && in_array($statusFilter, $statuses)) {
    $query .= " AND selling_requests.status = '$statusFilter'";
}



// Execute the query
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error retrieving data: " . mysqli_error($conn));
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bike Listings Status</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            padding: 30px;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .status-filter {
            margin-bottom: 20px;
        }
        .btn-back {
            margin-top: 20px;
            text-align: center;
            display: block;
            background-color: #007bff;
            color: white;
            padding: 10px;
            border-radius: 5px;
            text-decoration: none;
        }
    </style>
</head>
<body>


<main>
    <div class="container">
        <h2>Bike Listings - Status</h2>
        
        <!-- Status filter dropdown -->
        <div class="status-filter">
            <form method="GET" action="">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="Accepted" <?php if ($statusFilter == 'Accepted') echo 'selected'; ?>>Accepted</option>
                    <option value="Denied" <?php if ($statusFilter == 'Denied') echo 'selected'; ?>>Denied</option>
                    <option value="Pending" <?php if ($statusFilter == 'Pending') echo 'selected'; ?>>Pending</option>
                </select>
            </form>
        </div>

        <!-- Bike listings table -->
        <?php if (mysqli_num_rows($result) > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Bike Name</th>
                        <th>Phone Number</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th>Plate Number</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php echo ucfirst($row['status']); ?></td>
                            <td><?php echo number_format($row['price'], 2); ?></td>
                            <td><?php echo $row['plate_no']; ?></td>
                            <td>
                                <a href="selledit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="selldetail.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">Detail</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No motorcycles found for the selected status.</p>
        <?php endif; ?>
        
        <a href="FRONT.php" class="btn-back">Back to Home</a>
    </div>
    </main>
</body>
</html>
