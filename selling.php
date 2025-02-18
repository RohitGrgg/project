<?php
// Start the session at the beginning of the file
session_start();

include "connection.php"; // Include the database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit;
}
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
    // Retrieve and sanitize form data
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $gmail = mysqli_real_escape_string($conn, $_POST['gmail']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $plate_no = mysqli_real_escape_string($conn, $_POST['plate_no']);
    $bike_condition = mysqli_real_escape_string($conn, $_POST['bike_condition']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $kms = mysqli_real_escape_string($conn, $_POST['KMs_driven']);
    $model_names = mysqli_real_escape_string($conn, $_POST['model_name']);
    $brands = mysqli_real_escape_string($conn, $_POST['brand']);
    $locations = mysqli_real_escape_string($conn, $_POST['location']);


    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Redirect to login page if not logged in
        header("Location: login.php");
        exit;
    }

    // Handle file uploads
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); // Create the directory if it doesn't exist
    }

    // Process file uploads
    $front_pic_name = basename($_FILES['front_pic']['name']);
    $back_pic_name = basename($_FILES['back_pic']['name']);
    $bluebook_pic_name = basename($_FILES['bluebook_pic']['name']);

    $front_pic_path = $upload_dir . $front_pic_name;
    $back_pic_path = $upload_dir . $back_pic_name;
    $bluebook_pic_path = $upload_dir . $bluebook_pic_name;

    // Validate and move uploaded files
    if (
        move_uploaded_file($_FILES['front_pic']['tmp_name'], $front_pic_path) &&
        move_uploaded_file($_FILES['back_pic']['tmp_name'], $back_pic_path) &&
        move_uploaded_file($_FILES['bluebook_pic']['tmp_name'], $bluebook_pic_path)
    ) {
        // Prepare the SQL query for inserting into 'selling' table
        $stmt = $conn->prepare("INSERT INTO selling (name, address, gmail, phone, front_pic, back_pic, plate_no, bike_condition, bluebook_pic, price, KMs_driven, model_name, brand, location) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssiiisss", $name, $address, $gmail, $phone, $front_pic_name, $back_pic_name, $plate_no, $bike_condition, $bluebook_pic_name, $price, $kms, $model_names, $brands, $locations);

        $success_message = ""; // To show success message after update

        // Execute the query for 'selling' table
        if ($stmt->execute()) {
            // Get the id of the newly inserted selling record
            $selling_id = $stmt->insert_id;

            // Get the user_id from session
            $user_id = $_SESSION['user_id'];

            // Now insert the same data into 'selling_requests' table
            $stmt_request = $conn->prepare("INSERT INTO selling_requests (user_id, selling_id) VALUES (?, ?)");
            $stmt_request->bind_param("ii", $user_id, $selling_id);

            // Execute the query for 'selling_requests' table
            if ($stmt_request->execute()) {
                // Set success message
                $success_message = "<div class='alert alert-success'>selling listed successfully!</div>";
            } else {
                echo "Error inserting data into selling_requests table: " . $stmt_request->error;
            }

            // Close the 'selling_requests' statement
            $stmt_request->close();
        } else {
            echo "Error: " . $stmt->error;
        }

        // Close the 'selling' statement
        $stmt->close();
    } else {
        echo "Error uploading images. Please try again.";
    }
}

// Close the database connection
mysqli_close($conn);
?>






<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Your Bike</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="realstyle.css">
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
                                    <img src="uploads/<?php echo htmlspecialchars($user_data['image']); ?>"  alt="Profile" style="width: 40px; height: 40px; border-radius: 50%;     object-fit: cover;">
                                    <?php else: ?>
                                        <img src="uploads/profile.jpg"  alt="Profile" style="width: 40px; height: 40px; border-radius: 50%;     object-fit: cover;">
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
            <h2>Sell Your Bike</h2>
            
                <!-- Display success message -->
                <?php if (!empty($success_message)) echo $success_message; ?>


            <form action="selling.php" method="POST" enctype="multipart/form-data">
                <!-- Personal Details Section -->
                <h4>Personal Details</h4>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" class="form-control" id="address" name="address" required>
                </div>
                <div class="form-group">
                    <label for="gmail">Email</label>
                    <input type="email" class="form-control" id="gmail" name="gmail" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" required>
                </div>

                <!-- Bike Details Section -->
                <h4>Bike Details</h4>
                <div class="form-group">
                    <label for="model_name">Model Name</label>
                    <input type="text" class="form-control" id="model_name" name="model_name" required>
                </div>

                <div class="form-group">
                    <label for="brand">Brand</label>
                    <input type="text" class="form-control" id="brand" name="brand" required>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" class="form-control" id="location" name="location" required>
                </div>

                <div class="form-group">
                    <label for="front_pic">Front Picture</label>
                    <input type="file" class="form-control" id="front_pic" name="front_pic" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="back_pic">Back Picture</label>
                    <input type="file" class="form-control" id="back_pic" name="back_pic" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="place_no">plate_no No</label>
                    <input type="text" class="form-control" id="plate_no" name="plate_no" required>
                </div>
                <div class="form-group">
                    <label for="bike_condition">Condition</label>
                    <select class="form-control" id="bike_condition" name="bike_condition" required>
                        <option value="New">New</option>
                        <option value="Used">Used</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="bluebook_pic">Bluebook Pictures (Page 3 and 4)</label>
                    <input type="file" class="form-control" id="bluebook_pic" name="bluebook_pic" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" class="form-control" id="price" name="price" min="0" required>
                </div>

                <div class="form-group">
                    <label for="KMs_driven">KMs driven</label>
                    <input type="number" class="form-control" id="KMs_driven" name="KMs_driven" required>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="submit" class="btn btn-primary mt-3">Submit</button>
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