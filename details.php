<?php
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
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_role = $row['user_role'];
    }
}

$sql = "SELECT * FROM users WHERE user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
}
// Query the unique cities (locations) from the database
$query = "SELECT DISTINCT location FROM bike_detail";
$result = mysqli_query($conn, $query);

// Fetch the cities into an array
$locations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $locations[] = $row['location'];
}

// Query the unique cities (brand) from the database
$query = "SELECT DISTINCT brand FROM bike_detail";
$result = mysqli_query($conn, $query);

// Fetch the brands into an array
$brand = [];
while ($row = mysqli_fetch_assoc($result)) {
    $brand[] = $row['brand'];
}

// Query the unique cities (model_name) from the database
$query = "SELECT DISTINCT model_name FROM bike_detail";
$result = mysqli_query($conn, $query);

// Fetch the models into an array
$model_name = [];
while ($row = mysqli_fetch_assoc($result)) {
    $model_name[] = $row['model_name'];
}

// Check if an 'id' is passed as a query parameter
if (isset($_GET['id'])) {
    $bike_id = $_GET['id'];

    // Sanitize the input to prevent SQL injection
    $bike_id = $conn->real_escape_string($bike_id);

    // Query to fetch bike details by id
    $sql = "SELECT * FROM bike_detail WHERE id = $bike_id";
    $result = $conn->query($sql);

    // Check if any record is found
    if ($result->num_rows > 0) {
        $bike = $result->fetch_assoc();
    } else {
        echo "Bike not found.";
        exit;
    }
} else {
    echo "No bike ID provided.";
    exit;
}
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Bike Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="realstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            text-align: center;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 90%;
            max-width: 80%;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin: auto;
            margin-top: 20px;
        }

        .f-container {
            width: 90%;
            max-width: 80%;
            background: #212529;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin: auto;
            margin-top: 20px;
        }

        .image-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            padding: 50px;
        }

        .bike-image {
            width: 300px;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
            border-radius: 5px;
            transition: transform 0.3s ease-in-out;
        }

        .bike-image:hover {
            transform: scale(1.1);
        }

        /* Lightbox (Popup) */
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
        }

        .lightbox img {
            width: 50%;
            max-height: 80vh;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.3);
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 40px;
            font-size: 30px;
            color: white;
            cursor: pointer;
        }

        .scroll-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 15px;
            border: none;
            cursor: pointer;
            font-size: 24px;
            border-radius: 5px;
        }

        .scroll-button:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }

        .prev {
            left: 20px;
        }

        .next {
            right: 20px;
        }

        .action-buttons {
            padding: 20px;
        }

        a {
            padding: 50px;
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
            <h2>Bike Details</h2>

            <?php
            if (isset($_GET['message'])) {
                $message = htmlspecialchars($_GET['message']);
                echo "<div class='alert alert-success'>$message</div>";
            }
            ?>

            <!-- Display bike details -->
            <table class="table">
                <div class="container">
                    <div class="image-container">
                        <?php if (!empty($bike['front_pic'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($bike['front_pic']); ?>" alt="Front Bike Image" class="bike-image" onclick="openLightbox(0)">
                        <?php else: ?>
                            <p>No front image available for this bike.</p>
                        <?php endif; ?>

                        <?php if (!empty($bike['back_pic'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($bike['back_pic']); ?>" alt="Back Bike Image" class="bike-image" onclick="openLightbox(1)">
                        <?php else: ?>
                            <p>No back image available for this bike.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lightbox -->
                <div class="lightbox" id="lightbox">
                    <span class="close-btn" onclick="closeLightbox()">&times;</span>
                    <button class="scroll-button prev" onclick="changeImage(-1)">&#10094;</button>
                    <img id="lightbox-img">
                    <button class="scroll-button next" onclick="changeImage(1)">&#10095;</button>
                </div>


                <!-- Action Buttons: Book Now, Add to Cart, Edit, Delete -->
                <div class="action-buttons">
                    <!-- Add Book Now button to proceed to book.php -->
                    <a href="book.php?id=<?php echo $bike_id; ?>">
                        <button class="btn btn-success">Book Now</button>
                    </a>

                    <!-- Add to Cart Button -->
                    <a href="cart.php?action=add&id=<?php echo $bike_id; ?>">
                        <button class="btn btn-warning">Add to Cart</button>
                    </a>

                    <?php if ($user_role == 'admin') : ?>
                        <a href="detailedit.php?id=<?php echo $bike_id; ?>">
                            <button class="btn btn-info">Edit</button>
                        </a>
                        <a href="delete.php?id=<?php echo $bike_id; ?>" onclick="return confirm('Are you sure you want to delete this bike?');">
                            <button class="btn btn-danger">Delete</button>
                        </a>
                    <?php endif; ?> <!-- End of if -->
                    <!-- End of Admin Buttons -->




                    <!-- Display bike details in a table -->
                    <tr>
                        <th>Model Name</th>
                        <td><?php echo htmlspecialchars($bike['model_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Owner</th>
                        <td><?php echo htmlspecialchars($bike['owner']); ?></td>
                    </tr>
                    <tr>
                        <th>Brand</th>
                        <td><?php echo htmlspecialchars($bike['brand']); ?></td>
                    </tr>
                    <tr>
                        <th>Location</th>
                        <td><?php echo htmlspecialchars($bike['location']); ?></td>
                    </tr>
                    <tr>
                        <th>Kilometers Driven</th>
                        <td><?php echo htmlspecialchars($bike['kilometers_driven']); ?> km</td>
                    </tr>
                    <tr>
                        <th>Engine Displacement (CC)</th>
                        <td><?php echo htmlspecialchars($bike['engine_displacement']); ?> CC</td>
                    </tr>

                    <tr>
                        <th>Number Plate</th>
                        <td><?php echo htmlspecialchars($bike['number_plate']); ?></td>
                    </tr>
                    <tr>
                        <th>Condition</th>
                        <td><?php echo htmlspecialchars($bike['bike_condition']); ?></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><?php echo nl2br(htmlspecialchars($bike['description'])); ?></td>
                    </tr>
                    <tr>
                        <th>Price</th>
                        <td class="price">Nrs.<?php echo number_format($bike['price'], 2); ?></td>
                    </tr>
                    <tr>
                        <th>Added Date</th>
                        <td><?php echo date('d M Y, h:i A', strtotime($bike['added_date'])); ?></td>
                    </tr>
            </table>

            <!-- Back button -->
            <a href="FRONT.php" class="btn btn-secondary btn-back">Back to Bike List</a>

    </main>
    <footer class="bg-dark text-white py-4">
        <div class="f-container">
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
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    const container = document.querySelector('.image-container');

    function scrollLeft() {
        container.scrollBy({
            left: -600,
            behavior: 'smooth'
        });
    }

    function scrollRight() {
        container.scrollBy({
            left: 600,
            behavior: 'smooth'
        });
    }
</script>

<script>
    let currentIndex = 0;
    const images = document.querySelectorAll('.bike-image');
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');

    function openLightbox(index) {
        currentIndex = index;
        lightbox.style.display = 'flex';
        lightboxImg.src = images[currentIndex].src;
    }

    function closeLightbox() {
        lightbox.style.display = 'none';
    }

    function changeImage(direction) {
        currentIndex += direction;
        if (currentIndex < 0) currentIndex = images.length - 1;
        if (currentIndex >= images.length) currentIndex = 0;
        lightboxImg.src = images[currentIndex].src;
    }
</script>

</html>