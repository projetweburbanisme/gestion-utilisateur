<!-- filepath: C:\xampp\htdocs\wahdi\views\front\forgot_password.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <link rel="stylesheet" href="css/login.css">
</head>
<body>
  <div class="container">
    <h2>Reset Your Password</h2>
    <form method="POST" action="">
      <input type="email" name="email" placeholder="Enter your email" required>
      <button type="submit" name="send_reset">Send Reset Link</button>
    </form>
  </div>

  <?php
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reset'])) {
      $email = $_POST['email'];
      $token = bin2hex(random_bytes(16));
      $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

      try {
          $pdo = new PDO('mysql:host=localhost;dbname=clyptor;charset=utf8mb4', 'root', '');
          $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

          // Store token
          $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
          $stmt->execute([$token, $expires, $email]);

          $resetLink = "http://localhost/ghodwa/views/front/reset_password.php?token=$token";
          echo "<script>alert('A reset link has been sent to your email.');</script>";
          echo "<p style='color: white;'>Reset link: <a href='$resetLink'>$resetLink</a></p>"; // Simulated email
      } catch (PDOException $e) {
          echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
      }
  }
  ?>
</body>
</html>
