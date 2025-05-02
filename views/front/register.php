<!-- filepath: c:\xampp1\htdocs\ghodwa\views\front\register.php -->
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    try {
        // Database connection using PDO
        $dsn = 'mysql:host=localhost;dbname=clyptorweb;charset=utf8mb4';
        $pdo = new PDO($dsn, 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Debugging: Log input data
        error_log("Form Data: " . print_r($_POST, true));

        // Get form data
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $addressLine1 = $_POST['address_line1'];
        $addressLine2 = $_POST['address_line2'];
        $city = $_POST['city'];
        $stateProvince = $_POST['state_province'];
        $postalCode = $_POST['postal_code'];
        $country = $_POST['country'];
        $phoneNumber = $_POST['phone_number'];

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);

        if ($stmt->rowCount() > 0) {
            echo "<script>alert('Email already exists. Please use a different email.');</script>";
        } else {
            // Hash the password
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Insert user into the database
            $stmt = $pdo->prepare("INSERT INTO users (
                username, email, password_hash, first_name, last_name, address_line1, address_line2, city, state_province, postal_code, country, phone_number, created_at
            ) VALUES (
                :username, :email, :password_hash, :first_name, :last_name, :address_line1, :address_line2, :city, :state_province, :postal_code, :country, :phone_number, NOW()
            )");

            // Debugging: Log SQL query and parameters
            error_log("SQL Query: " . $stmt->queryString);
            error_log("Parameters: " . print_r([
                'username' => $username,
                'email' => $email,
                'password_hash' => $passwordHash,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'address_line1' => $addressLine1,
                'address_line2' => $addressLine2,
                'city' => $city,
                'state_province' => $stateProvince,
                'postal_code' => $postalCode,
                'country' => $country,
                'phone_number' => $phoneNumber,
            ], true));

            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => $passwordHash,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'address_line1' => $addressLine1,
                'address_line2' => $addressLine2,
                'city' => $city,
                'state_province' => $stateProvince,
                'postal_code' => $postalCode,
                'country' => $country,
                'phone_number' => $phoneNumber,
            ]);

            echo "<script>alert('Registration successful! You can now log in.');</script>";
            header("Location: login.php");
            exit();
        }
    } catch (PDOException $e) {
        // Log database errors
        error_log("Database Error: " . $e->getMessage());
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register</title>
  <link rel="stylesheet" href="css/login.css" />
</head>
<body>
  <div class="container">
    <h2>Register</h2>
    <form method="POST" action="register.php">
      <input type="text" name="username" placeholder="Username" required />
      <input type="email" name="email" placeholder="Email" required />
      <input type="password" name="password" placeholder="Password" required />
      <input type="text" name="first_name" placeholder="First Name" />
      <input type="text" name="last_name" placeholder="Last Name" />
      <input type="text" name="address_line1" placeholder="Address Line 1" />
      <input type="text" name="address_line2" placeholder="Address Line 2" />
      <input type="text" name="city" placeholder="City" />
      <input type="text" name="state_province" placeholder="State/Province" />
      <input type="text" name="postal_code" placeholder="Postal Code" />
      <input type="text" name="country" placeholder="Country" />
      <input type="text" name="phone_number" placeholder="Phone Number" />
      <button type="submit" name="register">Register</button>
    </form>
    <p style="text-align:center; margin-top: 20px;">
      Already have an account? <a href="login.php" style="color: #0ff;">Login</a>
    </p>
  </div>
</body>
</html>