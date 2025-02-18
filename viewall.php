<?php
include 'connection.php';

session_start(); // Ensure the session is started

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

// Validate and sanitize the brand parameter from the URL
$brand = isset($_GET['brand']) ? htmlspecialchars($_GET['brand'], ENT_QUOTES, 'UTF-8') : '';

// Redirect to a 404 or error page if no brand is provided
if (empty($brand)) {
    header("Location: error.php"); // Redirect to an error page or show a meaningful error
    exit();
}

// Fetch bikes of the specified brand
$stmt = $conn->prepare("SELECT * FROM bike_detail WHERE brand = ?");
$stmt->bind_param("s", $brand);
$stmt->execute();
$bikes_result = $stmt->get_result(); // Use a unique variable

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
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= $brand ?> Bikes - View All</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="realstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
         /* Styling for the bike listing section */
.bike-listing {
    display: flex;
    flex-wrap: wrap; /* Allow wrapping to the next row if necessary */
    justify-content: center; /* Center-align the items */
    gap: 20px; /* Add spacing between cards */
    margin-top: 20px;
}

/* Individual bike card styling */
.card {
    flex: 0 1 calc(25% - 20px); /* Cards take up 25% of the row minus the gap */
    max-width: calc(25% - 20px); /* Ensure the maximum width is the same */
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    text-align: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    background-color: white;
}

/* Hover effect on cards */
.card:hover {
    transform: translateY(-10px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}

/* Card image */
.card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-bottom: 1px solid #ddd;
}

/* Card content styling */
.card-content {
    padding: 15px;
}

.card-content h3 {
    font-size: 1.2em;
    margin-bottom: 10px;
}

.card-content .price {
    font-weight: bold;
    color: #28a745;
    margin-bottom: 8px;
}

.card-content .details {
    color: #555;
    font-size: 0.9em;
    margin-bottom: 10px;
}

/* Button styling */
.card-content .btn {
    text-decoration: none;
    color: white;
    background-color: #007bff;
    border: none;
    padding: 8px 15px;
    border-radius: 5px;
    font-size: 0.9em;
    display: inline-block;
}

.card-content .btn:hover {
    background-color: #0056b3;
}

/* Media query for responsiveness */
@media (max-width: 992px) {
    .card {
        flex: 0 1 calc(33.33% - 20px); /* 3 cards per row for medium screens */
        max-width: calc(33.33% - 20px);
    }
}

@media (max-width: 768px) {
    .card {
        flex: 0 1 calc(50% - 20px); /* 2 cards per row for small screens */
        max-width: calc(50% - 20px);
    }
}

@media (max-width: 576px) {
    .card {
        flex: 0 1 calc(100% - 20px); /* 1 card per row for extra small screens */
        max-width: calc(100% - 20px);
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
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 d-flex justify-content-center" style="width: 70%; font-size: 1rem;">

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

                        <!-- Dropdown: Models -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                SELECT MODEL
                            </a>
                            <ul class="dropdown-menu">
                                <?php foreach ($model_name as $model): ?>
                                    <li><a class="dropdown-item" href="Bymodel.php?model_name=<?php echo urlencode($model); ?>"><?php echo htmlspecialchars($model); ?></a></li>
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

                        <!-- Links -->
                        <li class="nav-item"><a class="nav-link active" href="selling.php">Sell Your Bike</a></li>
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
                                <img src="profile.jpg" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%;">
                            </button>
                            <ul id="profileDropdown" class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php">View Profile</a></li>
                                <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="login.php">Login</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>


    <main>
        <div class="container">
            <h1 class="text-center mb-4">Bikes Under "<?= $brand ?>"</h1>

            <div class="bike-listing">
                <?php if ($bikes_result->num_rows > 0): ?>
                    <?php while ($row = $bikes_result->fetch_assoc()):
                        $model_name = htmlspecialchars($row['model_name'], ENT_QUOTES, 'UTF-8');
                        $price = number_format($row['price'], 2);
                        $kms = htmlspecialchars($row['kilometers_driven'], ENT_QUOTES, 'UTF-8');
                        $location = htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8');
                        $front_pic = htmlspecialchars($row['front_pic'], ENT_QUOTES, 'UTF-8');
                    ?>
                        <div class="card">
                            <img src="uploads/<?= $front_pic ?>" alt="<?= $model_name ?>" loading="lazy">
                            <div class="card-content">
                                <h3><?= $model_name ?></h3>
                                <div class="price">Rs. <?= $price ?></div>
                                <div class="details"><?= $kms ?> KM | <?= $location ?></div>
                                <a href="details.php?id=<?= (int)$row['id'] ?>" class="btn btn-primary mt-2">Details</a>
                            </div>
                        </div>


                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center">No bikes available under "<?= $brand ?>" brand.</p>
                <?php endif; ?>
            </div>


            <!-- Back button to navigate to the previous page -->
            <div class="mt-4 text-center">
                <a href="FRONT.php" class="btn btn-secondary">Back to Home</a>
            </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <script>
        // Profile dropdown toggle functionality
        document.getElementById("profileButton").addEventListener("click", function() {
            const dropdown = document.getElementById("profileDropdown");
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        });

        document.getElementById("profileButton").addEventListener("click", function() {
            const dropdown = document.getElementById("profileDropdown");
            dropdown.classList.toggle("show"); // Use CSS class for visibility
        });

        document.addEventListener("click", function(event) {
            const dropdown = document.getElementById("profileDropdown");
            if (!event.target.closest("#profileButton") && !event.target.closest("#profileDropdown")) {
                dropdown.classList.remove("show");
            }
        });
    </script>
</body>

</html>