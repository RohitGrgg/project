<?php
// Start the session at the beginning of the file
session_start();

include "connection.php"; // Include the database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get the selling ID from the query string
$selling_id = isset($_GET['selling_id']) ? (int) $_GET['selling_id'] : 0;

// Fetch the existing data for the specific record if `selling_id` is provided
if ($selling_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM selling WHERE id = ?");
    $stmt->bind_param("i", $selling_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $selling = $result->fetch_assoc();
    $stmt->close();

    if (!$selling) {
        echo "<script>alert('Record not found.'); window.location.href='selling.php';</script>";
        exit;
    }
} else {
    echo "<script>alert('Invalid ID.'); window.location.href='selling.php';</script>";
    exit;
}

// Handle form submission for updating the record
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
    $brand = mysqli_real_escape_string($conn, $_POST['brand']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);

    // Handle file uploads
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $front_pic_name = $selling['front_pic'];
    $back_pic_name = $selling['back_pic'];
    $bluebook_pic_name = $selling['bluebook_pic'];

    if (!empty($_FILES['front_pic']['name'])) {
        $front_pic_name = basename($_FILES['front_pic']['name']);
        move_uploaded_file($_FILES['front_pic']['tmp_name'], $upload_dir . $front_pic_name);
    }

    if (!empty($_FILES['back_pic']['name'])) {
        $back_pic_name = basename($_FILES['back_pic']['name']);
        move_uploaded_file($_FILES['back_pic']['tmp_name'], $upload_dir . $back_pic_name);
    }

    if (!empty($_FILES['bluebook_pic']['name'])) {
        $bluebook_pic_name = basename($_FILES['bluebook_pic']['name']);
        move_uploaded_file($_FILES['bluebook_pic']['tmp_name'], $upload_dir . $bluebook_pic_name);
    }

    // Update the record in the database
    $stmt = $conn->prepare("
        UPDATE selling 
        SET 
            name=?, 
            address=?, 
            gmail=?, 
            phone=?, 
            front_pic=?, 
            back_pic=?, 
            plate_no=?, 
            bike_condition=?, 
            bluebook_pic=?, 
            price=?, 
            KMs_driven=?, 
            brand=?, 
            description=?, 
            location=? 
        WHERE id=?");
    $stmt->bind_param(
        "ssssssssiiisssi",
        $name,
        $address,
        $gmail,
        $phone,
        $front_pic_name,
        $back_pic_name,
        $plate_no,
        $bike_condition,
        $bluebook_pic_name,
        $price,
        $kms,
        $brand,
        $description,
        $location,
        $selling_id
    );

    if ($stmt->execute()) {
        echo "<script>alert('Record updated successfully!'); window.location.href='selling.php';</script>";
    } else {
        echo "Error updating record: " . $stmt->error;
    }

    $stmt->close();
}

// Close the database connection
mysqli_close($conn);
?>


<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Bike Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <h2 class="mt-4">Update Bike Details</h2>
        <form action="update.php?selling_id=<?php echo $selling_id; ?>" method="POST" enctype="multipart/form-data">
            <!-- Personal Details Section -->
            <h4>Personal Details</h4>
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($selling['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($selling['address']); ?>" required>
            </div>
            <div class="form-group">
                <label for="gmail">Email</label>
                <input type="email" class="form-control" id="gmail" name="gmail" value="<?php echo htmlspecialchars($selling['gmail']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($selling['phone']); ?>" required>
            </div>

            <!-- Bike Details Section -->
            <h4>Bike Details</h4>
            <div class="form-group">

                <!-- Additional Fields -->
                <div class="form-group">
                    <label for="brand">Brand</label>
                    <input type="text" class="form-control" id="brand" name="brand" value="<?php echo htmlspecialchars($selling['brand']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($selling['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($selling['location']); ?>" required>
                </div>

                <label for="front_pic">Front Picture</label>
                <input type="file" class="form-control" id="front_pic" name="front_pic" accept="image/*">
                <p>Current: <?php echo $selling['front_pic']; ?></p>
            </div>
            <div class="form-group">
                <label for="back_pic">Back Picture</label>
                <input type="file" class="form-control" id="back_pic" name="back_pic" accept="image/*">
                <p>Current: <?php echo $selling['back_pic']; ?></p>
            </div>
            <div class="form-group">
                <label for="place_no">Plate No</label>
                <input type="text" class="form-control" id="plate_no" name="plate_no" value="<?php echo htmlspecialchars($selling['plate_no']); ?>" required>
            </div>
            <div class="form-group">
                <label for="bike_condition">Condition</label>
                <select class="form-control" id="bike_condition" name="bike_condition" required>
                    <option value="New" <?php echo ($selling['bike_condition'] == 'New') ? 'selected' : ''; ?>>New</option>
                    <option value="Used" <?php echo ($selling['bike_condition'] == 'Used') ? 'selected' : ''; ?>>Used</option>
                </select>
            </div>
            <div class="form-group">
                <label for="bluebook_pic">Bluebook Picture</label>
                <input type="file" class="form-control" id="bluebook_pic" name="bluebook_pic" accept="image/*">
                <p>Current: <?php echo $selling['bluebook_pic']; ?></p>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($selling['price']); ?>" min="0" required>
            </div>

            <div class="form-group">
                <label for="KMs_driven">KMs Driven</label>
                <input type="number" class="form-control" id="KMs_driven" name="KMs_driven" value="<?php echo htmlspecialchars($selling['KMs_driven']); ?>" required>
            </div>

            <!-- Submit Button -->
            <button type="submit" name="submit" class="btn btn-primary mt-3">Update</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>