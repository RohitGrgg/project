<?php
include 'connection.php';

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

// Query the unique models from the database
$models_query = "SELECT DISTINCT model_name FROM bike_detail";
$models_result = mysqli_query($conn, $models_query); // Use a unique variable

// Fetch the models into an array
$model_name = [];
while ($row = mysqli_fetch_assoc($models_result)) {
    $model_name[] = htmlspecialchars($row['model_name'], ENT_QUOTES, 'UTF-8');
}

try {
    // Default values for filters
    $min_price = isset($_POST['min-price']) ? (int)$_POST['min-price'] : 100000;
    $max_price = isset($_POST['max-price']) ? (int)$_POST['max-price'] : 4000000;
    $min_km = isset($_POST['min-kilometer']) ? (int)$_POST['min-kilometer'] : 0;
    $max_km = isset($_POST['max-kilometer']) ? (int)$_POST['max-kilometer'] : 200000;

    // New CC filter added
    $engine_displacement = isset($_POST['engine_displacement']) ? trim($_POST['engine_displacement']) : '';

    // Other existing filters
    // Check if city is provided through the URL or the filter
    // Check if city is provided through the URL or the filter
    $location = isset($_GET['city']) ? trim($_GET['city']) : (isset($_POST['location']) ? trim($_POST['location']) : '');
     

    $brand = isset($_POST['brand']) ? trim($_POST['brand']) : '';
    $model_name = isset($_POST['model_name']) ? trim($_POST['model_name']) : '';

    // Initialize query with basic filters
    $sql = "SELECT * FROM bike_detail WHERE price BETWEEN ? AND ? AND kilometers_driven BETWEEN ? AND ?";
    $params = [$min_price, $max_price, $min_km, $max_km];
    $types = "iiii";

    // Append location filter if set
    if (!empty($location)) {
        $sql .= " AND location = ?";
        $params[] = $location;
        $types .= "s";
    }

    // Append brand filter if set
    if (!empty($brand)) {
        $sql .= " AND brand = ?";
        $params[] = $brand;
        $types .= "s";
    }

   

    // Append engine displacement filter if set
    if (!empty($engine_displacement)) {
        // Handle the different CC ranges
        if ($engine_displacement === 'upto_125') {
            $sql .= " AND engine_displacement <= 125";
        } elseif ($engine_displacement === '125_160') {
            $sql .= " AND engine_displacement BETWEEN 125 AND 160";
        } elseif ($engine_displacement === '160_250') {
            $sql .= " AND engine_displacement BETWEEN 160 AND 250";
        } elseif ($engine_displacement === '250_400') {
            $sql .= " AND engine_displacement BETWEEN 250 AND 400";
        } elseif ($engine_displacement === 'above_400') {
            $sql .= " AND engine_displacement > 400";
        }
    }

    // Prepare and execute query
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $bikes = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    // Fetch distinct values for filters
    $filters = ['location', 'brand', 'engine_displacement'];
    $data = [];

    foreach ($filters as $filter) {
        $query = "SELECT DISTINCT $filter FROM bike_detail WHERE $filter IS NOT NULL AND $filter != ''";
        $filter_result = $conn->query($query);

        if ($filter_result && $filter_result->num_rows > 0) {
            $data[$filter] = [];
            while ($row = $filter_result->fetch_assoc()) {
                $data[$filter][] = $row[$filter];
            }
        }
    }
} catch (Exception $e) {
    // Handle errors gracefully
    die("Error: " . $e->getMessage());
} finally {
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Bike Filter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="realstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
<style>
    /* General Reset */
    body,
    html {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, Helvetica, sans-serif;
        color: #333;
        background-color: #f9f9f9;
        /* Align content to the left */
        height: 100vh;
        /* Make sure the body takes the full height */
    }

    /* Layout Container */
    .container {
        display: flex;
        width: 100%;
        max-width: 1200px;
        margin: 0;
    }

    /* Filter Container on the Left */
    .filter-container {
        width: 300px;
        /* Fixed width for the filter panel */
        padding: 20px;
        border: 1px solid #ccc;
        border-radius: 5px;
        margin: 5px 0;
        /* Space above and below, no space on the sides */
        flex-shrink: 0;
        /* Prevent shrinking on smaller screens */
    }

    .filter-container h2 {
        margin-top: 0;
    }

    .reset-button {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 15px;
        cursor: pointer;
        border-radius: 3px;
        display: block;
        width: 100%;
        margin-bottom: 20px;
    }

    .reset-button:hover {
        background-color: #0056b3;
    }

    .filter-section {
        margin-bottom: 15px;
    }

    .filter-section label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }

    .filter-section select,
    .filter-section input[type="number"] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 3px;
    }

    .price-range {
        position: relative;
        height: 60px;
        margin-top: 10px;
    }

    .slider-bar {
        position: absolute;
        top: 22px;
        left: 0;
        width: 100%;
        height: 5px;
        background: #ddd;
        border-radius: 5px;
    }

    #slider-progress {
        position: absolute;
        height: 100%;
        background: #007bff;
        border-radius: 5px;
        transition: width 0.2s ease;
    }

    input[type="range"] {
        position: absolute;
        top: 12px;
        width: 100%;
        pointer-events: none;
        -webkit-appearance: none;
        appearance: none;
        background: transparent;
        z-index: 3;
    }

    input[type="range"]::-webkit-slider-thumb {
        pointer-events: all;
        position: relative;
        width: 20px;
        height: 20px;
        background: #007bff;
        border-radius: 50%;
        border: none;
        cursor: pointer;
        -webkit-appearance: none;
    }

    input[type="range"]::-moz-range-thumb {
        pointer-events: all;
        position: relative;
        width: 20px;
        height: 20px;
        background: #007bff;
        border-radius: 50%;
        border: none;
        cursor: pointer;
    }

    .price-range-display {
        margin-top: 40px;
        font-weight: bold;
        text-align: center;
    }

    /* Bike Section on the Right */
    .bike-list {
        margin-top: 20px;
        padding: 10px;
        width: calc(100% - 320px);
        /* Full width minus filter container width */
        overflow: hidden;
    }

    .bike-item {
        margin-bottom: 15px;
    }

    .bike-item h3 {
        margin: 0;
        font-size: 1.2em;
        color: #333;
    }

    .bike-item p {
        margin: 5px 0;
    }

    /* Filtered Bikes - Card Layout */
    .bike-section {
        margin-bottom: 40px;
    }

    .card-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        /* Allow the cards to wrap to the next line */
    }

    .card {
        width: 250px;
        flex-shrink: 0;
        border-radius: 8px;
        text-align: center;
        background-color: #fff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    /* Card Image */
    .card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px 8px 0 0;
    }

    /* Card Content */
    .card-content {
        padding: 15px;
        text-align: center;
    }

    .card-content h3 {
        font-size: 1.2rem;
        color: #007bff;
        margin: 10px 0;
    }

    .card-content .price {
        font-size: 1.1rem;
        font-weight: bold;
        color: #333;
        margin: 5px 0;
    }

    .card-content .details,
    .card-content .condition {
        font-size: 0.9rem;
        color: #555;
    }

    .card-content .btn {
        padding: 8px 20px;
        font-size: 0.9rem;
    }

    /* Responsive Design */
    @media screen and (max-width: 768px) {
        .card {
            width: calc(50% - 20px);
        }

        .container {
            flex-direction: column;
            /* Stack the filter and bike section for smaller screens */
        }

        .filter-container {
            width: 100%;
            margin-bottom: 20px;
        }

        .bike-list {
            width: 100%;
        }
    }

    @media screen and (max-width: 480px) {
        .card {
            width: 100%;
        }

        .filter-container {
            width: 100%;
        }

        .bike-list {
            width: 100%;
        }
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
            <!-- Filter Section -->
            <div class="filter-container">
                <h2>Filters</h2>

                <form method="POST" action="">

                    <div class="filter-section">
                        <label for="price-range">Search by Budget:</label>

                        <div class="price-range-display">
                            Rs. <span id="min-price-display"><?php echo number_format($minPrice); ?></span>
                            - Rs. <span id="max-price-display"><?php echo number_format($maxPrice); ?></span>
                        </div>

                        <div class="price-range">
                            <?php
                            $minPrice = isset($_GET['min-price']) ? (int)$_GET['min-price'] : 100000;
                            $maxPrice = isset($_GET['max-price']) ? (int)$_GET['max-price'] : 4000000;
                            ?>


                            <input
                                type="range"
                                id="min-range"
                                name="min-price"
                                min="0" max="4000000" step="100000"
                                value="<?php echo htmlspecialchars($minPrice); ?>" onchange="this.form.submit()">
                            <input
                                type="range"
                                id="max-range"
                                name="max-price"
                                min="0" max="4000000" step="100000"
                                value="<?php echo htmlspecialchars($maxPrice); ?>" onchange="this.form.submit()">
                            <div class="slider-bar">
                                <div id="slider-progress"></div>
                            </div>

                        </div>
                    </div>


                    <div class="filter-section">
                        <label for="brand">Brand:</label>
                        <select id="brand" name="brand" onchange="this.form.submit()">
                            <option value="">Select Brand</option>
                            <?php if (!empty($data['brand'])): ?>
                                <?php foreach ($data['brand'] as $br): ?>
                                    <option value="<?php echo htmlspecialchars($br); ?>" <?php echo ($br === $brand) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($br); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>


                    <!-- Dropdown for Engine Displacement (CC) -->
                    <div class="filter-section">
                        <label for="engine_displacement">Engine Displacement (CC):</label>
                        <select id="engine_displacement" name="engine_displacement" onchange="this.form.submit()">
                            <option value="">Select Engine Displacement</option>
                            <option value="upto_125" <?php echo ($engine_displacement === 'upto_125') ? 'selected' : ''; ?>>Upto 125 CC</option>
                            <option value="125_160" <?php echo ($engine_displacement === '125_160') ? 'selected' : ''; ?>>125 - 160 CC</option>
                            <option value="160_250" <?php echo ($engine_displacement === '160_250') ? 'selected' : ''; ?>>160 - 250 CC</option>
                            <option value="250_400" <?php echo ($engine_displacement === '250_400') ? 'selected' : ''; ?>>250 - 400 CC</option>
                            <option value="above_400" <?php echo ($engine_displacement === 'above_400') ? 'selected' : ''; ?>>400 CC & Above</option>
                        </select>
                    </div>


                    <div class="filter-section">
                        <!-- Kilometer Range Filter -->

                        <label for="kilometer-range">Kilometers Driven:</label>

                        <div class="price-range-display">
                            <span id="min-km-display"><?php echo number_format($minKilometer); ?> KM</span>
                            -
                            <span id="max-km-display"><?php echo number_format($maxKilometer); ?> KM</span>
                        </div>

                        <div class="price-range">
                            <?php
                            $minKilometer = isset($_GET['min-kilometer']) ? (int)$_GET['min-kilometer'] : 0;
                            $maxKilometer = isset($_GET['max-kilometer']) ? (int)$_GET['max-kilometer'] : 200000;
                            ?>

                            <!-- Min Kilometer Slider -->
                            <input
                                type="range"
                                id="min-kilometer"
                                name="min-kilometer"
                                min="0"
                                max="200000"
                                step="1000"
                                value="<?php echo htmlspecialchars($minKilometer); ?>"
                                onchange="this.form.submit();">

                            <!-- Max Kilometer Slider -->
                            <input
                                type="range"
                                id="max-kilometer"
                                name="max-kilometer"
                                min="0"
                                max="200000"
                                step="1000"
                                value="<?php echo htmlspecialchars($maxKilometer); ?>"
                                onchange="this.form.submit();">

                            <div class="slider-bar">
                                <div id="kilometer-progress"></div>
                            </div>
                        </div>
                    </div>

                    <button class="reset-button" onclick="resetFilters()">Reset All</button>
                </form>
            </div>

            <!-- Bike List Section -->
            <div class="bike-list">
                <?php if (!empty($bikes)): ?>
                    <div class="card-row">
                        <?php foreach ($bikes as $bike): ?>
                            <div class="card">
                                <img src="uploads/<?php echo htmlspecialchars($bike['front_pic']); ?>" alt="<?php echo htmlspecialchars($bike['model_name']); ?>">
                                <div class="card-content">
                                    <h3><?php echo htmlspecialchars($bike['model_name']); ?></h3>
                                    <div class="price">Rs. <?php echo number_format($bike['price']); ?></div>
                                    <div class="details"><?php echo number_format($bike['kilometers_driven']); ?> KM | <?php echo htmlspecialchars($bike['location']); ?></div>
                                    <a href="details.php?id=<?php echo $bike['id']; ?>"><button class="btn btn-primary">Details</button></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No bikes found matching your criteria.</p>
                <?php endif; ?>
            </div>
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
    </script>

    <script>
       function resetFilters() { 
    document.getElementById("brand").value = "";
    document.getElementById("engine_displacement").value = "";
    document.getElementById("min-kilometer").value = "0";
    document.getElementById("max-kilometer").value = "200000";
    document.getElementById("min-range").value = "100000";
    document.getElementById("max-range").value = "4000000";
    this.form.submit();
}



        document.addEventListener("DOMContentLoaded", () => {
            const minRange = document.getElementById("min-range");
            const maxRange = document.getElementById("max-range");
            const minDisplay = document.getElementById("min-price-display");
            const maxDisplay = document.getElementById("max-price-display");
            const sliderProgress = document.getElementById("slider-progress");

            function updatePriceRange() {
                const rangePercent = (value, min, max) => ((value - min) * 100) / (max - min);

                // Update displayed prices
                minDisplay.textContent = new Intl.NumberFormat("en-IN").format(minRange.value);
                maxDisplay.textContent = new Intl.NumberFormat("en-IN").format(maxRange.value);

                // Update slider progress bar
                sliderProgress.style.left = rangePercent(minRange.value, minRange.min, minRange.max) + "%";
                sliderProgress.style.right = (100 - rangePercent(maxRange.value, maxRange.min, maxRange.max)) + "%";
            }

            // Initialize the sliders and progress bar positions
            updatePriceRange();

            // Add event listeners for real-time updates
            minRange.addEventListener("input", updatePriceRange);
            maxRange.addEventListener("input", updatePriceRange);
        });


        document.addEventListener("DOMContentLoaded", () => {
            const minKm = document.getElementById("min-kilometer");
            const maxKm = document.getElementById("max-kilometer");
            const minKmDisplay = document.getElementById("min-km-display");
            const maxKmDisplay = document.getElementById("max-km-display");
            const kilometerProgress = document.getElementById("kilometer-progress");

            // Update Kilometer Range function (including slider progress)
            function updateKilometerRange() {
                const rangePercent = (value, min, max) => ((value - min) * 100) / (max - min);

                // Update displayed values dynamically
                minKmDisplay.textContent = new Intl.NumberFormat().format(minKm.value) + " KM";
                maxKmDisplay.textContent = new Intl.NumberFormat().format(maxKm.value) + " KM";

                // Update progress bar positions based on the values
                const minPercent = rangePercent(minKm.value, minKm.min, minKm.max);
                const maxPercent = rangePercent(maxKm.value, minKm.min, maxKm.max);
                kilometerProgress.style.left = minPercent + "%";
                kilometerProgress.style.right = (100 - maxPercent) + "%";
            }

            // Initial call to update the display on page load
            updateKilometerRange();

            // Add event listeners for real-time updates
            minKm.addEventListener("input", updateKilometerRange);
            maxKm.addEventListener("input", updateKilometerRange);
        });

    </script>
</body>

</html>