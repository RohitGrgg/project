<?php
// Include the database connection file
include 'connection.php';

// Start the session to manage user data
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

// Get the booking ID from the URL
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0; // Ensure valid integer

if ($booking_id > 0) {
    // Fetch booking details to display
    $query = "SELECT * FROM booking WHERE id = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if the booking record exists
        if ($result->num_rows > 0) {
            $booking = $result->fetch_assoc();
        } else {
            echo "<div class='alert alert-danger'>Booking not found!</div>";
            exit;
        }
    } else {
        echo "<div class='alert alert-danger'>Error: Could not prepare query! (" . $conn->error . ")</div>";
        exit;
    }
} else {
    echo "<div class='alert alert-danger'>Invalid booking ID.</div>";
    exit;
}

$success_message = ""; // To show success message after update

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $name = htmlspecialchars(trim($_POST['name']));
    $address = htmlspecialchars(trim($_POST['address']));
    $gmail = htmlspecialchars(trim($_POST['gmail']));
    $phone_number = htmlspecialchars(trim($_POST['phone_number']));

    // Directory for uploaded files
    $upload_dir = "uploads/";

    // Check if the booking ID is set
    if (!isset($booking_id)) {
        echo "<div class='alert alert-danger'>Error: Booking ID is missing.</div>";
        exit;
    }

    // Use existing file as default
    $document_photo = $booking['document_photo'];

    // Handle file upload if a new file is submitted
    if (!empty($_FILES['document_photo']['name'])) {
        $target_file = $upload_dir . basename($_FILES['document_photo']['name']);
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Allowed file types (Add more if needed)
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($_FILES['document_photo']['tmp_name'], $target_file)) {
                $document_photo = $target_file; // Update image path
            } else {
                echo "<div class='alert alert-danger'>Error uploading document photo. Try again.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP allowed.</div>";
        }
    }

    // Update query
    $update_query = "UPDATE booking 
                     SET name = ?, address = ?, gmail = ?, phone_number = ?, document_photo = ? 
                     WHERE id = ?";

    if ($stmt = $conn->prepare($update_query)) {
        $stmt->bind_param("sssssi", $name, $address, $gmail, $phone_number, $document_photo, $booking_id);
        if ($stmt->execute()) {
            // Set success message
            $success_message = "<div class='alert alert-success'>Booking updated successfully!</div>";

            // Update the fetched details to reflect updated information
            $booking = array_merge($booking, compact('name', 'address', 'gmail', 'phone_number', 'document_photo'));
        } else {
            echo "<div class='alert alert-danger'>Error executing query: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        echo "<div class='alert alert-danger'>Error preparing query: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="realstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
</head>

<main>

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
                                <li class="nav-item"><a class="nav-link active" href="sellstatus.php">Client Sell Listing</a></li>
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
            <div class="container mt-4">
                <h2>Update Booking Request</h2>

                <!-- Display success message -->
                <?php if (!empty($success_message)) echo $success_message; ?>

                <!-- HTML Form -->
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Full Name:</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($booking['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address">Address:</label>
                        <textarea name="address" id="address" class="form-control" required><?php echo htmlspecialchars($booking['address']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="gmail">Gmail:</label>
                        <input type="email" name="gmail" id="gmail" class="form-control" value="<?php echo htmlspecialchars($booking['gmail']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Phone Number:</label>
                        <input type="text" name="phone_number" id="phone_number" class="form-control" value="<?php echo htmlspecialchars($booking['phone_number']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="document_photo">Document Photo:</label>
                        <input type="file" name="document_photo" id="document_photo" class="form-control-file">
                        <div>
                            <img src="<?php echo htmlspecialchars($booking['document_photo']); ?>" alt="Document Photo" style="max-width: 200px; max-height: 200px; margin-bottom: 10px;">
                        </div>
                        
                    </div>

                    <button type="submit" class="btn btn-primary">Update Booking</button>
                </form>
                <div class="mt-3">
                    <a href="FRONT.php?id=<?php echo $booking_id; ?>" class="btn btn-secondary">Back to the page</a>
                </div>
            </div>
        </main>
    </body>
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

</html>