<!-- reset_password.php -->
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
</head>
<body>
  <h2>Reset Your Password</h2>
  <form method="POST" action="send_reset_code.php">
    <input type="text" name="identifier" placeholder="Email or Phone Number" required>
    <select name="method" required>
      <option value="email">Receive code by Email</option>
      <option value="phone">Receive code by SMS</option>
    </select>
    <button type="submit">Send Code</button>
  </form>
</body>
</html>
