<?php
// Include database connection
include("connection.php");

// Start the session
session_start();

// Fetch user role
$user_role = 'client';
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

// Query unique locations and brands
$locations_query = "SELECT DISTINCT location FROM bike_detail";
$brands_query = "SELECT DISTINCT brand FROM bike_detail";

$locations_result = mysqli_query($conn, $locations_query);
$brands_result = mysqli_query($conn, $brands_query);

$locations = [];
$brands = [];

// Fetch locations and brands into arrays
while ($row = mysqli_fetch_assoc($locations_result)) {
    $locations[] = htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8');
}
while ($row = mysqli_fetch_assoc($brands_result)) {
    $brands[] = htmlspecialchars($row['brand'], ENT_QUOTES, 'UTF-8');
}

// Fetch user data
$sql = "SELECT * FROM users WHERE user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
}

// Fetch all pending requests
$sql = "SELECT * FROM booking WHERE status = 'pending'";
$result = mysqli_query($conn, $sql);

// Check if the query was successful
if (!$result) {
    die("Error retrieving data: " . mysqli_error($conn));
}

// Process accept or deny request
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']); // Sanitize the ID as an integer

    if ($action == 'accept') {
        $update_sql = "UPDATE booking SET status = 'accepted' WHERE id = ?";
        if ($stmt = $conn->prepare($update_sql)) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Request has been accepted successfully.";
                header("Location: booking_request.php");
                exit();
            } else {
                $_SESSION['error'] = "Error updating request status: " . mysqli_error($conn);
                header("Location: booking_request.php");
                exit();
            }
        }
    } elseif ($action == 'deny') {
        // Begin transaction
        mysqli_begin_transaction($conn);

        try {
            // Delete from booking_requests table
            $delete_requests_sql = "DELETE FROM booking_request WHERE booking_id = ?";
            if ($stmt = $conn->prepare($delete_requests_sql)) {
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Error deleting from booking_request: " . mysqli_error($conn));
                }
            }

            // Delete from booking table
            $delete_booking_sql = "DELETE FROM booking WHERE id = ?";
            if ($stmt = $conn->prepare($delete_booking_sql)) {
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Error deleting from booking: " . mysqli_error($conn));
                }
            }

            // Commit transaction
            mysqli_commit($conn);
            $_SESSION['message'] = "Request has been denied and data deleted successfully.";
            header("Location: booking_request.php");
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $_SESSION['error'] = $e->getMessage();
            header("Location: booking_request.php");
            exit();
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <title>Booking Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="realstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

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
        </div>
    </nav>
</header>

<main>

    <body>
        <div class="container my-5">
            <h1 class="mb-4">Client Booking Requests</h1>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client Name</th>
                            <th>Bike id</th>
                            <th>Booking Date</th>
                            <th>Details</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['bike_id']); // Ensure this field exists 
                                    ?></td>
                                <td><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></td> <!-- Corrected field name -->
                                <td><a href="details.php?id=<?php echo $row['bike_id']; ?>" class="btn btn-success btn-sm">Detail</a></td> <!-- Correct link -->
                                <td><?php echo ucfirst($row['status']); ?></td>
                                <td>
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <button class="btn btn-success btn-sm btn-action btn-accept"><a href="booking_request.php?action=accept&id=<?php echo $row['id']; ?>" style="color: white;">Accept</a></button>
                                            <button class="btn btn-success btn-sm btn-action btn-deny"><a href="booking_request.php?action=deny&id=<?php echo $row['id']; ?>" style="color: white;">Deny</a></button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted"><?php echo ucfirst($row['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No booking requests found.</p>
            <?php endif; ?>
        </div>
</main>

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

</html>

<?php
// Close the database connection
mysqli_close($conn);
?>