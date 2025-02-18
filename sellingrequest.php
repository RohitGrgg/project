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

// Fetch all requests from the "selling" table (pending requests)
$sql = "SELECT * FROM selling WHERE status = 'pending'"; // Assuming 'status' is a column to track approval
$result = mysqli_query($conn, $sql);

// Check if the query was successful
if (!$result) {
    die("Error retrieving data: " . mysqli_error($conn));
}

$success_message = ""; // To show success message after update
// Process the accept or deny request
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']); // Ensure the ID is sanitized as an integer

    // Update the request status based on the action (Accept / Deny)
    if ($action == 'accept') {
        $update_sql = "UPDATE selling SET status = 'accepted' WHERE id = $id";
        if (mysqli_query($conn, $update_sql)) {
            
            $success_message = "<div class='alert alert-success'>Request has been accepted successfully!</div>";
        } else {
            echo "<p class='error-msg'>Error updating request status: " . mysqli_error($conn) . "</p>";
        }
    } elseif ($action == 'deny') {
        // Begin transaction
        mysqli_begin_transaction($conn);

        try {
            // Delete from selling_requests table
            $delete_requests_sql = "DELETE FROM selling_requests WHERE selling_id = $id";
            if (!mysqli_query($conn, $delete_requests_sql)) {
                throw new Exception("Error deleting from selling_requests: " . mysqli_error($conn));
            }

            // Delete from selling table
            $delete_selling_sql = "DELETE FROM selling WHERE id = $id";
            if (!mysqli_query($conn, $delete_selling_sql)) {
                throw new Exception("Error deleting from selling: " . mysqli_error($conn));
            }

            // Commit transaction
            mysqli_commit($conn);
            
            $success_message = "<div class='alert alert-success'>Request has been denied and data deleted successfully!</div>";
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            echo "<p class='error-msg'>" . $e->getMessage() . "</p>";
        }
    }
}


// Close the database connection
mysqli_close($conn);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Selling Requests</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;

        }

        .container {

            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ccc;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        .btn-action {
            padding: 5px 10px;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-accept {
            background-color: green;
        }

        .btn-deny {
            background-color: red;
        }

        .btn-view {
            background-color: #007bff;
        }

        .error-msg {
            color: red;
            text-align: center;
            margin-top: 20px;
        }

        .success-msg {
            color: green;
            text-align: center;
            margin-top: 20px;
        }

        a {
            color: white;
        }
    </style>
</head>

<body>

    <header>
        <nav class="navbar navbar-expand-lg bg-primary" data-bs-theme="dark">
            <div class="container-fluid">
                <a class="navbar-brand fw-bold text-white" href="FRONT.php" style="font-size: 1.8rem;">Kin Bech</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 justify-content: space-evenly;" style="width: 70%; font-size: 1rem;">

                        <!-- Dropdown: Used Bikes by City -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Used Bikes by City
                            </a>
                            <ul class="dropdown-menu">
                                <?php foreach ($locations as $location): ?>
                                    <li><a class="dropdown-item" href="ByLocation.php?city=<?php echo urlencode($location); ?>"><?php echo htmlspecialchars($location); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>

                        <!-- Dropdown: Brands -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Brands
                            </a>
                            <ul class="dropdown-menu">
                                <?php foreach ($brands as $brandItem): ?>
                                    <li><a class="dropdown-item" href="Bybrand.php?brand=<?php echo urlencode($brandItem); ?>"><?php echo htmlspecialchars($brandItem); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>



                        <!-- Dropdown: Bikes by CC -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Used Bikes by CC
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="ByCC.php?cc=upto_125">Upto 125 CC</a></li>
                                <li><a class="dropdown-item" href="ByCC.php?cc=125_160">125 - 160 CC</a></li>
                                <li><a class="dropdown-item" href="ByCC.php?cc=160_250">160 - 250 CC</a></li>
                                <li><a class="dropdown-item" href="ByCC.php?cc=250_400">250 - 400 CC</a></li>
                                <li><a class="dropdown-item" href="ByCC.php?cc=above_400">400 CC & Above</a></li>
                            </ul>
                        </li>

                        <li class="nav-item"><a class="nav-link active" href="cart.php">cart</a></li>

                        <!-- Links -->
                        <li class="nav-item"><a class="nav-link active" href="selling.php">Sell Your Bike</a></li>

                        <li class="nav-item"><a class="nav-link active" href="sellstatus.php">Client Sell Listing</a></li>

                        <li class="nav-item"><a class="nav-link active" href="booking_status.php">Booking Status</a></li>
                        <?php if ($user_role == 'admin'): ?>
                            <li class="nav-item"><a class="nav-link active" href="adding.php">Add Motorcycles</a></li>
                            <li class="nav-item"><a class="nav-link active" href="sellingrequest.php">Selling Requests</a></li>
                            
                            <li class="nav-item"><a class="nav-link active" href="booking_request.php">Booking Requests</a></li>
                            <li class="nav-item"><a class="nav-link active" href="booking_status.php">Booking Status</a></li>
                        <?php endif; ?>
                    </ul>


                </div>
                <!-- Profile and Logout Button -->
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <button id="profileButton" class="btn btn-light p-0 rounded-circle" type="button">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($user_data['image']); ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%;     object-fit: cover;">
                            <?php else: ?>
                                <img src="uploads/profile.jpg" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%;     object-fit: cover;">
                            <?php endif; ?>
                        </button>

                        <ul id="profileDropdown" class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">View Profile</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a class="dropdown-item" href="logout.php" onclick="return confirmLogout()">Logout</a>
                                <?php else: ?>
                                    <a class="dropdown-item" href="login.php">Login</a>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <h2>Manage Selling Requests</h2>
            <?php if (!empty($success_message)) echo $success_message; ?>

            <?php if (mysqli_num_rows($result) > 0) { ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone Number</th>
                            <th>Bike Condition</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo $row['phone']; ?></td>
                                <td><?php echo $row['bike_condition']; ?></td>
                                <td><?php echo 'Nrs. ' . number_format($row['price'], 2); ?></td>
                                <td>
                                    <a href="sellingrequest.php?action=accept&id=<?php echo $row['id']; ?>" class="btn-action btn-accept">Accept</a>
                                    <a href="sellingrequest.php?action=deny&id=<?php echo $row['id']; ?>" class="btn-action btn-deny">Deny</a>
                                    <a href="selldetail.php?id=<?php echo $row['id']; ?>" class="btn-action btn-view">Details</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <p>No pending requests found.</p>
            <?php } ?>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <script>
        // Toggle dropdown visibility
        document.getElementById("profileButton").addEventListener("click", function() {
            const dropdown = document.getElementById("profileDropdown");
            dropdown.classList.toggle("show");
            ensureDropdownInView(dropdown);
        });

        // Close dropdown if clicking outside
        document.addEventListener("click", function(event) {
            const dropdown = document.getElementById("profileDropdown");
            if (!event.target.closest("#profileButton") && !event.target.closest("#profileDropdown")) {
                dropdown.classList.remove("show");
            }
        });

        // Ensure dropdown stays within the screen
        function ensureDropdownInView(dropdown) {
            const rect = dropdown.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;

            // Adjust dropdown position if it's outside the right edge
            if (rect.right > viewportWidth) {
                dropdown.style.left = "auto"; // Reset left
                dropdown.style.right = "0"; // Align to parent
            }

            // Adjust dropdown position if it's outside the bottom edge
            if (rect.bottom > viewportHeight) {
                dropdown.style.top = "auto"; // Reset top
                dropdown.style.bottom = "100%"; // Align above button
            }
        }

        function confirmLogout() {
            alert("You are now logged out.");
            return true; // Allows navigation to logout.php
        }
    </script>

</body>

</html>