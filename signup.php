<?php
include 'connection.php';
// Start the session to store user information
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);

    // Validate input fields
    if (empty($name) || empty($email) || empty($password) || empty($phone)) {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $message = "Phone number must be 10 digits.";
    } else {
        // Hash the password for secure storage
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Insert data into the database
        $sql = "INSERT INTO users (username, email, password, phone) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Database error: " . $conn->error);
        }

        $stmt->bind_param("sssi", $name, $email, $hashedPassword, $phone);

        if ($stmt->execute()) {
            // Get the last inserted user ID and store it in the session
            // session_regenerate_id(true); // Regenerate session ID for security
            $_SESSION['user_id'] = $conn->insert_id;

            // Redirect to login page after successful registration
            header("Location: login.php");
            exit;
        } else {
            $message = "Error: Could not register. Please try again.";
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        /* General reset */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f3f4f6;
            /* Light gray background */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Container for the signup form */
        .login-container {
            background-color: #ffffff;
            /* White background */
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            /* Soft shadow */
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        /* Heading styles */
        .login-container h2 {
            margin-bottom: 20px;
            color: #333333;
            /* Dark gray */
            font-size: 24px;
        }

        /* Form group styling */
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        /* Label styling */
        .form-group label {
            display: block;
            font-size: 14px;
            color: #555555;
            /* Medium gray */
            margin-bottom: 5px;
        }

        /* Input field styling */
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #cccccc;
            /* Light gray border */
            border-radius: 4px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        /* Focus state for input fields */
        .form-group input:focus {
            border-color: #007bff;
            /* Blue border on focus */
        }

        /* Show password container */
        .show-password-container {
            text-align: left;
            font-size: 14px;
            color: #555555;
        }

        /* Register button styling */
        .btn {
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            /* Green background */
            color: #ffffff;
            /* White text */
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        /* Hover effect for register button */
        .btn:hover {
            background-color: #218838;
            /* Darker green */
        }

        /* Signup link styling */
        .signup-link {
            margin-top: 20px;
            font-size: 14px;
            color: #555555;
        }

        .signup-link a {
            color: #007bff;
            /* Blue link */
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        /* Hover effect for signup link */
        .signup-link a:hover {
            color: #0056b3;
            /* Darker blue */
        }

        /* Error message styling */
        p[style="color: red;"] {
            font-size: 14px;
            color: #ff4d4d;
            /* Red for error message */
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <h2>Sign Up</h2>
        <!-- Display error message -->
        <?php if (!empty($message)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form id="registerForm" action="" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Enter your Full Name">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" required placeholder="Enter your phone number">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Set up your password">
            </div>
            <div class="show-password-container">
                <input type="checkbox" id="showPassword">
                <label for="showPassword">Show Password</label>
            </div><br>
            <button type="submit" class="btn">Register</button>
            <p class="signup-link">Already have an Account? <a href="login.php">Login</a></p>
        </form>
    </div>

    <script>
        // JavaScript to toggle password visibility
        document.getElementById('showPassword').addEventListener('change', function() {
            var passwordField = document.getElementById('password');
            passwordField.type = this.checked ? 'text' : 'password';
        });
    </script>
</body>

</html>