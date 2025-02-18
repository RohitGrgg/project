<?php
include 'connection.php';
// Start session to store user information
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$message = ""; // Default message
$loggedInUser = ""; // For username alert

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $message = "Email and Password are required!";
    } else {
        $sql = "SELECT user_id, username, password FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Database error: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if user exists
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['user_id'];
                $loggedInUser = htmlspecialchars($user['username']); // For alert

                // Output JavaScript for the alert
                echo "<script>
                    alert('You are now logged in as $loggedInUser');
                    window.location.href = 'FRONT.php'; // Redirect after alert
                </script>";
                exit;
            } else {
                $message = "Invalid password.";
            }
        } else {
            $message = "No account found with that email.";
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
    <title>Login</title>
    <style>
        /* General reset */
body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    background-color: #f3f4f6; /* Light gray background */
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

/* Container for the login form */
.login-container {
    background-color: #ffffff; /* White background */
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1); /* Soft shadow */
    width: 100%;
    max-width: 400px;
    text-align: center;
}

/* Heading styles */
.login-container h2 {
    margin-bottom: 20px;
    color: #333333; /* Dark gray */
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
    color: #555555; /* Medium gray */
    margin-bottom: 5px;
}

/* Input field styling */
.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #cccccc; /* Light gray border */
    border-radius: 4px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.3s ease;
}

/* Focus state for input fields */
.form-group input:focus {
    border-color: #007bff; /* Blue border on focus */
}

/* Show password container */
.show-password-container {
    text-align: left;
    font-size: 14px;
    color: #555555;
}

/* Login button styling */
.btn {
    width: 100%;
    padding: 12px;
    background-color: #007bff; /* Blue background */
    color: #ffffff; /* White text */
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

/* Hover effect for login button */
.btn:hover {
    background-color: #0056b3; /* Darker blue */
}

/* Signup link styling */
.signup-link {
    margin-top: 20px;
    font-size: 14px;
    color: #555555;
}

.signup-link a {
    color: #007bff; /* Blue link */
    text-decoration: none;
    font-weight: bold;
    transition: color 0.3s ease;
}

/* Hover effect for signup link */
.signup-link a:hover {
    color: #0056b3; /* Darker blue */
}

/* Error message styling */
p[style="color: red;"] {
    font-size: 14px;
    color: #ff4d4d; /* Red for error message */
    margin-bottom: 15px;
}

    </style>
</head>

<body>

    <div class="login-container">
        <h2>Login</h2>
        <!-- Display error message -->
        <?php if (!empty($message)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form id="loginForm" action="" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>
            <div class="show-password-container">
                <input type="checkbox" id="showPassword">
                <label for="showPassword">Show Password</label>
            </div><br>
            <button type="submit" class="btn">Login</button>
            <p class="signup-link">Don't have an Account? <a href="signup.php">Sign up</a></p>
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
