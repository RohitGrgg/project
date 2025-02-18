<?php
// Include the database connection script
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
// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Bike ID is missing.");
}

$bike_id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $gmail = mysqli_real_escape_string($conn, $_POST['gmail']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
    
    if (!isset($_SESSION['user_id'])) {
        die("User is not logged in.");
    }
    $user_id = $_SESSION['user_id']; 

    $target_dir = '';
    $file_name = basename($_FILES["document_photo"]["name"]);
    $document_photo = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["document_photo"]["tmp_name"], $document_photo)) {
        $conn->begin_transaction();

        try {
            $sql_booking = "INSERT INTO booking (name, address, gmail, phone_number, document_photo, bike_id) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql_booking);
            $stmt->bind_param("sssssi", $name, $address, $gmail, $phone_number, $file_name, $bike_id);

            if ($stmt->execute()) {
                $booking_id = $stmt->insert_id;

                $sql_booking_request = "INSERT INTO booking_request (user_id, booking_id) VALUES (?, ?)";
                $stmt_req = $conn->prepare($sql_booking_request);
                $stmt_req->bind_param("ii", $user_id, $booking_id);

                if ($stmt_req->execute()) {
                    $conn->commit();
                    $success_message = "<div class='alert alert-success'>Booking successfully completed!</div>";
                } else {
                    throw new Exception("Error executing booking_request insertion: " . $stmt_req->error);
                }
                $stmt_req->close();
            } else {
                throw new Exception("Error executing booking insertion: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            echo "<p class='error-msg'>Transaction failed: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='error-msg'>Error uploading the document. Please try again.</p>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motorcycle Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="realstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;

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

        form label {
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 15px;
        }

        input,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        textarea {
            resize: vertical;
            height: 100px;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            padding: 12px 20px;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-primary:hover {
            background-color: #0056b3;
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

        .file-input {
            padding: 10px;
        }

        .back-link {
            margin-top: 15px;
            display: block;
            text-align: center;
        }

        .back-link a {
            color: #007bff;
            text-decoration: none;
            font-size: 18px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .detail-btn {
            display: block;
            text-align: center;
            margin-top: 20px;
        }

        main {

            padding: 40px;
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
    <div class="container">
        <h2>Motorcycle Booking Form</h2>
        <?php if (!empty($success_message)) echo $success_message; ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea id="address" name="address" required></textarea>
            </div>
            <div class="form-group">
                <label for="gmail">Gmail Address:</label>
                <input type="email" id="gmail" name="gmail" required>
            </div>
            <div class="form-group">
                <label for="phone_number">Phone Number:</label>
                <input type="text" id="phone_number" name="phone_number" required>
            </div>
            <div class="form-group file-input">
                <label for="document_photo">Upload Document Photo (ID/License/Passport):</label>
                <input type="file" id="document_photo" name="document_photo" required>
            </div>
            <button type="submit" class="btn btn-primary">Book Now</button>
        </form>
        <div class="detail-btn">
            <a href="details.php?id=<?php echo htmlspecialchars($bike_id); ?>" class="btn btn-secondary">View Details</a>
        </div>
        <div class="back-link">
            <a href="FRONT.php">Back to Homepage</a>
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