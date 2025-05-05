<?php
session_start();

// Traitement de la connexion classique
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=clyptorweb;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit();
        } else {
            $loginError = "Invalid email or password.";
        }
    } catch (PDOException $e) {
        $loginError = "Database error: " . $e->getMessage();
    }
}

// Facebook et GitHub config
$facebookAppId = 'YOUR_FACEBOOK_APP_ID';
$facebookRedirect = urlencode('http://localhost/wahdi/facebook_callback.php');

$githubClientId = 'YOUR_GITHUB_CLIENT_ID';
$githubRedirect = urlencode('http://localhost/wahdi/github_callback.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login / Register</title>
  <script type="module" src="https://unpkg.com/@splinetool/viewer@1.9.82/build/spline-viewer.js"></script>
  <link rel="stylesheet" href="css/login.css" />
</head>
<body>

  <!-- Spline 3D Background -->
  <spline-viewer url="https://prod.spline.design/NOspby6AJwzuaFUg/scene.splinecode"></spline-viewer>

  <!-- Main Form Container -->
  <main class="container" id="form-container">
    <h2 id="form-title">Login</h2>

    <!-- Login Form -->
    <form id="login-form" method="POST" action="">
      <input type="email" name="email" placeholder="Email" required />
      <input type="password" name="password" placeholder="Password" required />
      <button type="submit" name="login">Login</button>

      <!-- Display errors -->
      <?php if (isset($loginError)) { ?>
        <p style="color: red; text-align: center;"><?php echo $loginError; ?></p>
      <?php } ?>

      <!-- Forgot Password -->
      <div style="text-align: center; margin-top: 15px;">
        <a href="reset_password.php" style="color: #0ff; text-decoration: none;">Forgot Password?</a>
      </div>

      <!-- Social Login Buttons -->
      <div style="text-align: center; margin-top: 20px;">
        <!-- Facebook -->
        <a href="https://www.facebook.com/v11.0/dialog/oauth?client_id=<?= $facebookAppId ?>&redirect_uri=<?= $facebookRedirect ?>&response_type=code&scope=email">
          <div class="social-btn" style="background-color: #3b5998; color: white; display: inline-flex; align-items: center; padding: 10px 20px; margin: 5px; border-radius: 4px;">
            <img src="https://upload.wikimedia.org/wikipedia/commons/5/51/Facebook_f_logo_%282019%29.svg" alt="Facebook Logo" style="height: 20px; margin-right: 10px;" />
            <span>Login with Facebook</span>
          </div>
        </a>

        <!-- GitHub -->
        <a href="https://github.com/login/oauth/authorize?client_id=<?= $githubClientId ?>&redirect_uri=<?= $githubRedirect ?>&scope=user:email">
          <div class="social-btn" style="background-color: #333; color: white; display: inline-flex; align-items: center; padding: 10px 20px; margin: 5px; border-radius: 4px;">
            <img src="https://upload.wikimedia.org/wikipedia/commons/9/91/Octicons-mark-github.svg" alt="GitHub Logo" style="height: 20px; margin-right: 10px;" />
            <span>Login with GitHub</span>
          </div>
        </a>
      </div>
    </form>

    <!-- Register Form -->
    <form id="register-form" method="POST" action="register.php" style="display: none;">
      <input type="text" name="username" placeholder="Username" required />
      <input type="email" name="email" placeholder="Email" required />
      <input type="password" name="password" placeholder="Password" required />
      <button type="submit" name="register">Register</button>
    </form>

    <!-- Toggle Link -->
    <p style="text-align:center; margin-top: 20px; color: white;">
      <span id="toggle-text">Don't have an account?</span>
      <a href="#" id="toggle-link" style="color: #0ff;">Register</a>
    </p>
  </main>

  <!-- JS to toggle forms -->
  <script>
    const loginForm = document.getElementById("login-form");
    const registerForm = document.getElementById("register-form");
    const formTitle = document.getElementById("form-title");
    const toggleText = document.getElementById("toggle-text");
    const toggleLink = document.getElementById("toggle-link");

    toggleLink.addEventListener("click", (e) => {
      e.preventDefault();
      const isLogin = loginForm.style.display !== "none";

      if (isLogin) {
        loginForm.style.display = "none";
        registerForm.style.display = "block";
        formTitle.textContent = "Register";
        toggleText.textContent = "Already have an account?";
        toggleLink.textContent = "Login";
      } else {
        loginForm.style.display = "block";
        registerForm.style.display = "none";
        formTitle.textContent = "Login";
        toggleText.textContent = "Don't have an account?";
        toggleLink.textContent = "Register";
      }
    });
  </script>
</body>
</html>
