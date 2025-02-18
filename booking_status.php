
<?php

// Include the database connection file
include 'connection.php';

session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Define the statuses for filtering (ensure these match your actual statuses in `booking_request` table)
$statuses = ['Accepted', 'Denied', 'Pending'];

// Check for selected status in URL parameters (optional filtering)
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';



// Get the user ID from the session
$user_id = $_SESSION['user_id'];  

// Construct the query to retrieve booking requests based on the selected status and user
$query = "
    SELECT 
        b.*,                         -- All bike details
        bk.*,                        -- All booking details
        br.user_id, br.booking_id    -- User and booking relationship
    FROM 
        booking_request AS br
    INNER JOIN 
        booking AS bk ON br.booking_id = bk.id    -- Link booking_request to booking
    INNER JOIN 
        bike_detail AS b ON bk.bike_id = b.id    -- Link booking to bike_detail
    WHERE 
        br.user_id = '$user_id'";    


// Add condition to filter by status if provided
if ($statusFilter && in_array($statusFilter, $statuses)) {
    $query .= " AND booking_request.status = '$statusFilter'";
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
    <title>Booking Requests Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="realstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
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

<mian>
    <div class="container">
        <h2>Booking Requests - Status</h2>
        
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

        <!-- Booking requests table -->
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
                            <td><?php echo $row['model_name']; ?></td>
                            <td><?php echo $row['phone_number']; ?></td>
                            <td><?php echo ucfirst($row['status']); ?></td>
                            <td><?php echo number_format($row['price'], 2); ?></td>
                            <td><?php echo $row['number_plate']; ?></td>
                            <td>
                                <a href="bookedit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="details.php?id=<?php echo $row['bike_id']; ?>" class="btn btn-info btn-sm"> Bike Detail</a>
                                <a href="bookdetail.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">Booking Detail</a>

                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No booking requests found for the selected status.</p>
        <?php endif; ?>
        
        <a href="FRONT.php" class="btn-back">Back to Home</a>
    </div>
    </mian>

</body>
 
</html>
