<?php
// Start the session
session_start();
// Include the database connection file
include 'connection.php';

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
// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle profile picture upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'uploads/';
        $filename = time() . '_' . $_FILES['image']['name'];

        // Move the uploaded file to the target directory
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
            // Image successfully uploaded, update in the database
            $image = $filename;

            // Update the 'image' column in the users table with the new image path
            $sql = "UPDATE users SET image = ? WHERE user_id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("si", $image, $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    if (isset($_POST['update_profile'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];

        $sql = "UPDATE users SET username = ?, email = ?, phone = ? WHERE user_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssi", $username, $email, $phone, $user_id);
            if ($stmt->execute()) {
                // Return JSON response indicating success
                echo 'Profile updated successfully';
            } else {
                // Return JSON response indicating error
                echo 'Error updating profile';
            }
            $stmt->close();
        }
    }

    // Handle password change
    if (isset($_POST['change_password'])) {
        $new_password = $_POST['new_password'];
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        $sql = "UPDATE users SET password = ? WHERE user_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $hashed_password, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Retrieve user data from the database
$sql = "SELECT * FROM users WHERE user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            justify-content: center;
            align-items: center;
            height: 80%;
            flex-direction: column;
        }

        .container {
            background-color: white;

            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 900px;
        }

        h2 {
            text-align: center;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .user_profile {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile_pic {
            flex: 0 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 30px;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #007bff;
        }

        .profile-info {
            flex: 1;
            text-align: left;
        }

        .profile-info h3 {
            font-size: 24px;
            margin-bottom: 15px;
        }

        .profile-info p {
            font-size: 16px;
            line-height: 1.5;
        }

        .form-control {
            margin-bottom: 20px;
            padding: 10px;
            width: 100%;
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

        input[type="file"] {
            margin-top: 10px;
        }

        a {
            color: white;
        }

        @media (min-width: 992px) {
            .navbar-expand-lg .navbar-collapse {
                display: -ms-flexbox !important;
                display: flex !important;
                -ms-flex-preferred-size: auto;
                flex-basis: auto;
                justify-content: space-around;
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

    <main style="margin: 30px;">
        <div class="container">
            <h2>User Profile</h2>
            <section class="user_profile">

                <div class="profile_pic">
                    <?php
                    if (isset($user_data) && !empty($user_data['image']) && file_exists("uploads/" . $user_data['image'])) {
                        // Image exists, display it
                    ?>
                        <img src="uploads/<?php echo htmlspecialchars($user_data['image']); ?>" class="profile-image">
                    <?php
                    } else {
                        // Image doesn't exist, show default avatar
                    ?>
                        <img src="uploads/profile.png" class="profile-image" alt="Default Avatar">
                    <?php
                    }
                    ?>
                </div>


                <div class="profile-info">
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user_data['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
                    <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($user_data['phone']); ?></p>
                </div>
            </section>

            <form id="updateProfileForm" action="" method="post" enctype="multipart/form-data">
                <h3>Update Profile</h3>
                <input type="text" name="username" class="form-control" placeholder="Full Name" value="<?php echo $user_data['username']; ?>" required>
                <input type="email" name="email" class="form-control" placeholder="Email" value="<?php echo $user_data['email']; ?>" required>
                <input type="text" name="phone" class="form-control" placeholder="Phone Number" value="<?php echo $user_data['phone']; ?>" required>
                <input type="file" name="image" class="form-control" accept="image/*">

                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
            </form>


            <form id="changePasswordForm" method="post">
                <h3>Change Password</h3>
                <input type="password" name="new_password" class="form-control" placeholder="New Password" required>
                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
            </form>

            <br>
            <a href="logout.php" class="btn btn-primary">Logout</a>
        </div>
    </main>

</body>





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


</html>