<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Verify Code</title>
</head>
<body>
  <h2>Enter the Verification Code</h2>
  <form method="POST" action="reset_password_final.php">
    <input type="text" name="code" placeholder="Code" required>
    <input type="password" name="new_password" placeholder="New Password" required>
    <button type="submit">Reset Password</button>
  </form>
</body>
</html>
