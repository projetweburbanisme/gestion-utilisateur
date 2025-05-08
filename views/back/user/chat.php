<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="container">
    <aside>
        <div class="toggle">
            <div class="logo">
                <img src="images/logo.png">
                <h2>Cly<span class="danger">Ptor</span></h2>
            </div>
            <div class="close" id="close-btn">
                <span class="material-icons-sharp">
                    close
                </span>
            </div>
        </div>
      <div class="sidebar">
        <a href="user-info.php">
          <span class="fas fa-user"></span>
          <h3>User Information</h3>
        </a>
        <a href="history.php">
          <span class="fas fa-history"></span>
          <h3>History</h3>
        </a>
        <a href="chat.php" class="active">
          <span class="fas fa-comments"></span>
          <h3>Chat</h3>
        </a>
        <a href="#" id="logout-button">
          <span class="fas fa-sign-out-alt"></span>
          <h3>Logout</h3>
        </a>
      </div>
    </aside>
    <main>
      <h1>Chat</h1>
      <div class="chat-box">
        <div class="messages">
          <div class="message received">
            <p>Hi! How can I help you?</p>
          </div>
          <div class="message sent">
            <p>I'm looking for a carpool to downtown.</p>
          </div>
        </div>
        <form id="chat-form">
          <input type="text" placeholder="Type a message..." required />
          <button type="submit" class="btn btn-primary">Send</button>
        </form>
      </div>
    </main>
  </div>
  <script>
    document.getElementById("logout-button").addEventListener("click", () => {
      localStorage.removeItem("isLoggedIn");
      localStorage.removeItem("username");
      window.location.href = "../index.html";
    });

    const chatForm = document.getElementById("chat-form");
    const messages = document.querySelector(".messages");

    chatForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const input = chatForm.querySelector("input");
      const message = input.value.trim();

      if (message) {
        const sentMessage = document.createElement("div");
        sentMessage.classList.add("message", "sent");
        sentMessage.innerHTML = `<p>${message}</p>`;
        messages.appendChild(sentMessage);
        input.value = "";
        messages.scrollTop = messages.scrollHeight;
      }
    });
  </script>
</body>
</html>