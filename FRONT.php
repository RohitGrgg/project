<?php

// Include the database connection
include('connection.php');


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
?>


<html lang="en">

<head>
    <title>MAIN PAGE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="realstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


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
                        
                        <li class="nav-item"><a class="nav-link active" href="sellstatus.php">Sell Listing</a></li>
                        
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
            <?php
            // Fetch unique brands from the database
            $brand_query = "SELECT DISTINCT brand FROM bike_detail ORDER BY brand ASC";
            $brand_result = mysqli_query($conn, $brand_query);

            if (mysqli_num_rows($brand_result) > 0):
                while ($brand_row = mysqli_fetch_assoc($brand_result)):
                    $brand = htmlspecialchars($brand_row['brand'], ENT_QUOTES, 'UTF-8');
            ?>
                    <section class="bike-section">
                        <div class="section-header d-flex justify-content-between align-items-center">
                            <h2 class="section-title"><?= $brand ?></h2>
                            <a href="viewall.php?brand=<?= urlencode($brand) ?>" class="btn btn-outline-primary view-all-btn">View All</a>
                        </div>
                        <div class="scroll-wrapper">
                            <button class="scroll-btn left" onclick="scrollRow(this, -1)" aria-label="Scroll Left">&lt;</button>
                            <div class="card-row">
                                <?php
                                $bike_query = $conn->prepare("SELECT * FROM bike_detail WHERE brand = ?");
                                $bike_query->bind_param("s", $brand);
                                $bike_query->execute();
                                $bike_result = $bike_query->get_result();

                                if ($bike_result->num_rows > 0):
                                    while ($bike_row = $bike_result->fetch_assoc()):
                                        $model_name = htmlspecialchars($bike_row['model_name'], ENT_QUOTES, 'UTF-8');
                                        $price = number_format($bike_row['price'], 2);
                                        $kms = htmlspecialchars($bike_row['kilometers_driven'], ENT_QUOTES, 'UTF-8');
                                        $location = htmlspecialchars($bike_row['location'], ENT_QUOTES, 'UTF-8');
                                        $front_pic = htmlspecialchars($bike_row['front_pic'], ENT_QUOTES, 'UTF-8');
                                ?>
                                        <div class="card">
                                            <img src="uploads/<?= $front_pic ?>" alt="<?= $model_name ?>" loading="lazy">
                                            <div class="card-content">
                                                <h3><?= $model_name ?></h3>
                                                <div class="price">Rs. <?= $price ?></div>
                                                <div class="details"><?= $kms ?> KM | <?= $location ?></div>
                                                <a href="details.php?id=<?= (int)$bike_row['id'] ?>" class="btn btn-primary mt-2">Details</a>
                                            </div>
                                        </div>
                                    <?php endwhile;
                                else: ?>
                                    <p>No bikes available under <?= $brand ?>.</p>
                                <?php endif; ?>
                            </div>
                            <button class="scroll-btn right" onclick="scrollRow(this, 1)" aria-label="Scroll Right">&gt;</button>
                        </div>
                    </section>
                <?php endwhile;
            else: ?>
                <p>No brands available.</p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function scrollRow(button, direction) {
            const cardRow = button.parentElement.querySelector('.card-row');
            const scrollAmount = direction * 300; // Adjust scroll amount
            cardRow.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });
        }
    </script>

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

<?php
// Closing the database connection
mysqli_close($conn);
?>