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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $bike_id = $_POST['id'];
    $model_name = $_POST['model_name'];
    $owner = $_POST['owner'];
    $brand = $_POST['brand'];
    $location = $_POST['location'];
    $kilometers_driven = $_POST['kilometers_driven'];
    $number_plate = $_POST['number_plate'];
    $bike_condition = $_POST['bike_condition'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $cc = $_POST['engine_displacement'];
 
    $front_pic = $target_dir . basename($_FILES['front_pic']['name']);
    $back_pic = $target_dir . basename($_FILES['back_pic']['name']);

    // Check if files are uploaded correctly
    if (move_uploaded_file($_FILES['front_pic']['tmp_name'], $front_pic) && move_uploaded_file($_FILES['back_pic']['tmp_name'], $back_pic)) {
        echo "Files have been uploaded successfully."; // For debugging

        // Query to update bike details
        $sql = "UPDATE bike_detail 
                SET model_name='$model_name', 
                    owner='$owner', 
                    brand='$brand', 
                    location='$location', 
                    kilometers_driven='$kilometers_driven', 
                    number_plate='$number_plate', 
                    bike_condition='$bike_condition', 
                    description='$description', 
                    price='$price', 
                    front_pic='$front_pic', 
                    back_pic='$back_pic', 
                    engine_displacement='$cc' 
                WHERE id='$bike_id'";

        if ($conn->query($sql) === TRUE) {
            header("Location: details.php?id=$bike_id&message=Bike details updated successfully.");
            exit;
        } else {
            echo "Error updating record: " . $conn->error;
        }
    } else {
        echo "Sorry, there was an error uploading your files."; // For debugging
    }
}

if (isset($_GET['id'])) {
    $bike_id = $_GET['id'];
    $sql = "SELECT * FROM bike_detail WHERE id=$bike_id";
    $result = $conn->query($sql);
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bike Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="realstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
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
    <h2>Edit Bike Details</h2>

    <form method="POST" enctype="multipart/form-data"> <!-- Add enctype here -->
        <input type="hidden" name="id" value="<?php echo $bike['id']; ?>">

        <div class="form-group">
            <label for="model_name">Model Name</label>
            <input type="text" class="form-control" name="model_name" value="<?php echo $bike['model_name']; ?>" required>
        </div>

        <div class="form-group">
            <label for="owner">Owner</label>
            <input type="text" class="form-control" name="owner" value="<?php echo $bike['owner']; ?>" required>
        </div>

        <div class="form-group">
            <label for="brand">Brand</label>
            <input type="text" class="form-control" name="brand" value="<?php echo $bike['brand']; ?>" required>
        </div>

        <div class="form-group">
            <label for="location">Location</label>
            <input type="text" class="form-control" name="location" value="<?php echo $bike['location']; ?>" required>
        </div>

        <div class="form-group">
            <label for="kilometers_driven">Kilometers Driven</label>
            <input type="number" class="form-control" name="kilometers_driven" value="<?php echo $bike['kilometers_driven']; ?>" required>
        </div>

        <div class="form-group">
            <label for="number_plate">Number Plate</label>
            <input type="text" class="form-control" name="number_plate" value="<?php echo $bike['number_plate']; ?>" required>
        </div>

        <div class="form-group">
            <label for="bike_condition">Condition</label>
            <input type="text" class="form-control" name="bike_condition" value="<?php echo $bike['bike_condition']; ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" name="description" rows="5"><?php echo $bike['description']; ?></textarea>
        </div>

        <div class="form-group">
            <label for="price">Price</label>
            <input type="text" class="form-control" name="price" value="<?php echo $bike['price']; ?>" required>
        </div>

        <div class="form-group">
            <label for="front_pic">Front Pic</label>
            <input type="file" class="form-control" name="front_pic" required>
        </div>

        <div class="form-group">
            <label for="back_pic">Back Pic</label>
            <input type="file" class="form-control" name="back_pic" required>
        </div>

        <div class="form-group">
            <label for="engine_displacement">Engine Displacement</label>
            <input type="number" class="form-control" name="engine_displacement" value="<?php echo $bike['engine_displacement']; ?>" required>
        </div>

        <button type="submit" name="update" class="btn btn-primary">Update</button>
        <a href="detail.php?id=<?php echo $bike['id']; ?>" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</main>

</body>


<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
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
// Close the connection
$conn->close();
?>
