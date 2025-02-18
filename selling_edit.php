<?php
// Include the database connection
include 'connection.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if the ID is passed in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the details of the selected selling request from the "selling" table
    $sql = "SELECT * FROM selling WHERE id = $id AND user_id = $user_id";
    $result = mysqli_query($conn, $sql);

    // Fetch the row data
    $row = mysqli_fetch_assoc($result);
    if (!$row) {
        echo "<p>No details found for this request.</p>";
        exit;
    }

    // Handle form submission for editing the selling request
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Sanitize and retrieve form data
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $bike_condition = mysqli_real_escape_string($conn, $_POST['bike_condition']);
        $price = mysqli_real_escape_string($conn, $_POST['price']);
        $plate_no = mysqli_real_escape_string($conn, $_POST['plate_no']);

        // Update the record in the database
        $update_sql = "UPDATE selling SET name = ?, phone = ?, bike_condition = ?, price = ?, plate_no = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssssi", $name, $phone, $bike_condition, $price, $plate_no, $id);

        // Execute the query and check for success
        if ($stmt->execute()) {
            // Redirect to the requests page after update
            header("Location: requests.php");
            exit;
        } else {
            // Display error if the update failed
            echo "<p>Error updating the request: " . $stmt->error . "</p>";
        }
    }
} else {
    echo "<p>Invalid request.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Motorcycle Request</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Edit Motorcycle Request</h2>
        <form action="edit_request.php?id=<?php echo $row['id']; ?>" method="POST">
            <div class="form-group">
                <label for="name">Bike Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($row['phone']); ?>" required>
            </div>
            <div class="form-group">
                <label for="bike_condition">Condition</label>
                <select class="form-control" id="bike_condition" name="bike_condition" required>
                    <option value="New" <?php echo $row['bike_condition'] == 'New' ? 'selected' : ''; ?>>New</option>
                    <option value="Used" <?php echo $row['bike_condition'] == 'Used' ? 'selected' : ''; ?>>Used</option>
                </select>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" class="form-control" id="price" name="price" value="<?php echo $row['price']; ?>" required>
            </div>
            <div class="form-group">
                <label for="plate_no">Plate Number</label>
                <input type="text" class="form-control" id="plate_no" name="plate_no" value="<?php echo $row['plate_no']; ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Request</button>
        </form>
    </div>
</body>
</html>
