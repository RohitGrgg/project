<?php 
include 'connection.php';

if (isset($_GET['id'])) {
    $bike_id = $_GET['id'];

    // Sanitize input to prevent SQL injection
    $bike_id = $conn->real_escape_string($bike_id);

    // Query to delete the bike from the database
    $sql = "DELETE FROM bike_detail WHERE id = $bike_id";

    if ($conn->query($sql) === TRUE) {
        // Redirect to bike list page with a success message
        header("Location: FRONT.php?message=Bike deleted successfully");
        exit;
    } else {
        // Display error message if something goes wrong
        echo "Error deleting bike: " . $conn->error;
    }
} else {
    echo "Bike ID not provided.";
}

$conn->close();
?>
