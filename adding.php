<?php
include "connection.php"; // Include the database connection

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


// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $brand = mysqli_real_escape_string($conn, $_POST['brand']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $model = mysqli_real_escape_string($conn, $_POST['model']);
    $kms = (int)$_POST['kms'];
    $owner = mysqli_real_escape_string($conn, $_POST['owner']);
    $plate_no = mysqli_real_escape_string($conn, $_POST['plate_no']);
    $condition = mysqli_real_escape_string($conn, $_POST['condition']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = (float)$_POST['price'];
    $engine_displacement = (int)$_POST['engine_displacement']; // New field for engine displacement (CC)

    // Handle file uploads
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); // Create the directory if it doesn't exist
    }

    $front_pic_name = basename($_FILES['front_pic']['name']);
    $back_pic_name = basename($_FILES['back_pic']['name']);

    $front_pic_path = $upload_dir . $front_pic_name;
    $back_pic_path = $upload_dir . $back_pic_name;

    // Validate and move uploaded files
    if (
        move_uploaded_file($_FILES['front_pic']['tmp_name'], $front_pic_path) &&
        move_uploaded_file($_FILES['back_pic']['tmp_name'], $back_pic_path)
    ) {

        // SQL query to insert data into the bike_detail table
        $sql = "INSERT INTO bike_detail (brand, location, model_name, kilometers_driven, owner, number_plate, bike_condition, description, price, front_pic, back_pic, engine_displacement)
                VALUES ('$brand', '$location', '$model', $kms, '$owner', '$plate_no', '$condition', '$description', $price, '$front_pic_name', '$back_pic_name', $engine_displacement)";

        // Execute the query
        if (mysqli_query($conn, $sql)) {
            echo "Bike details added successfully!";
        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }
    } else {
        echo "Error uploading images. Please try again.";
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
    <title>Add Bike Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
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
    <div class="container mt-5">
        <h2>Add Bike Details</h2>
        <form action="adding.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="brand" class="form-label">Brand Name</label>
                <input type="text" class="form-control" id="brand" name="brand" required>
            </div>
            <div class="mb-3">
                <label for="location" class="form-label">Location</label>
                <input type="text" class="form-control" id="location" name="location" required>
            </div>
            <div class="mb-3">
                <label for="model" class="form-label">Model</label>
                <input type="text" class="form-control" id="model" name="model" required>
            </div>
            <div class="mb-3">
                <label for="kms" class="form-label">KMs Driven</label>
                <input type="number" class="form-control" id="kms" name="kms" required>
            </div>
            <div class="mb-3">
                <label for="engine_displacement" class="form-label">Engine Displacement (CC)</label>
                <input type="number" class="form-control" id="engine_displacement" name="engine_displacement" required>
            </div>

            <div class="mb-3">
                <label for="owner" class="form-label">Owner Name</label>
                <input type="text" class="form-control" id="owner" name="owner" required>
            </div>
            <div class="mb-3">
                <label for="plate_no" class="form-label">Plate Number</label>
                <input type="text" class="form-control" id="plate_no" name="plate_no" required>
            </div>
            <div class="mb-3">
                <label for="condition" class="form-label">Condition</label>
                <input type="text" class="form-control" id="condition" name="condition" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Price</label>
                <input type="number" class="form-control" id="price" name="price" required>
            </div>
            <div class="mb-3">
                <label for="front_pic" class="form-label">Front Picture</label>
                <input type="file" class="form-control" id="front_pic" name="front_pic" accept="image/*" required>
            </div>
            <div class="mb-3">
                <label for="back_pic" class="form-label">Back Picture</label>
                <input type="file" class="form-control" id="back_pic" name="back_pic" accept="image/*" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
    </main>

   
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white text-decoration-none">Facebook</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Twitter</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Instagram</a></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5>Overview</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white text-decoration-none">About Us</a></li>
                        <li><a href="#" class="text-white text-decoration-none">FAQs</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Privacy Policy</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Terms & Conditions</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
</body>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+CV8eGmPZ4GNTF0M0xHhxV7gUj0a6" crossorigin="anonymous"></script>
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