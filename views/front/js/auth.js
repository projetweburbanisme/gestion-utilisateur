// Initialize database if not exists
function initDatabase() {
    if (!localStorage.getItem('clyptor_users')) {
        localStorage.setItem('clyptor_users', JSON.stringify([]));
    }
    if (!localStorage.getItem('clyptor_posts')) {
        localStorage.setItem('clyptor_posts', JSON.stringify([]));
    }
    if (!localStorage.getItem('clyptor_contacts')) {
        localStorage.setItem('clyptor_contacts', JSON.stringify([]));
    }
    
    // Check if admin exists, if not create one
    const users = JSON.parse(localStorage.getItem('clyptor_users'));
    const adminExists = users.some(user => user.email === 'admin@clyptor.com');
    
    if (!adminExists) {
        users.push({
            id: Date.now(),
            name: 'Admin',
            email: 'admin@clyptor.com',
            password: 'hashed_password_placeholder', // In real app, this would be hashed
            isAdmin: true,
            verified: true
        });
        localStorage.setItem('clyptor_users', JSON.stringify(users));
    }
}

// Check if user is logged in
function checkAuth() {
    const authLink = document.getElementById('auth-link');
    if (authLink) {
        const user = JSON.parse(localStorage.getItem('current_user'));
        if (user) {
            authLink.textContent = 'Logout';
            authLink.href = '#';
            authLink.onclick = logoutUser;
            
            // Add admin link if user is admin
            if (user.isAdmin) {
                const nav = document.querySelector('.nav ul');
                if (nav && !document.getElementById('admin-link')) {
                    const li = document.createElement('li');
                    li.innerHTML = '<a href="admin.html" id="admin-link">Admin</a>';
                    nav.insertBefore(li, nav.querySelector('li:last-child'));
                }
            }
        } else {
            authLink.textContent = 'Login';
            authLink.href = 'login.html';
            authLink.onclick = null;
        }
    }
}

// Register new user
function registerUser(e) {
    e.preventDefault();
    
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    
    if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return;
    }
    
    const users = JSON.parse(localStorage.getItem('clyptor_users'));
    const userExists = users.some(user => user.email === email);
    
    if (userExists) {
        alert('Email already registered!');
        return;
    }
    
    const newUser = {
        id: Date.now(),
        name,
        email,
        password: btoa(password), // Simple "hashing" for demo (not secure)
        isAdmin: false,
        verified: false
    };
    
    users.push(newUser);
    localStorage.setItem('clyptor_users', JSON.stringify(users));
    
    // In a real app, you would send an email verification here
    alert('Registration successful! Please login.');
    window.location.href = 'login.html';
}

// Login user
function loginUser(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    const users = JSON.parse(localStorage.getItem('clyptor_users'));
    const user = users.find(user => user.email === email);
    
    if (!user || atob(user.password) !== password) {
        alert('Invalid email or password!');
        return;
    }
    
    if (!user.verified) {
        alert('Please verify your email first!');
        return;
    }
    
    localStorage.setItem('current_user', JSON.stringify(user));
    alert('Login successful!');
    window.location.href = 'index.html';
}

// Logout user
function logoutUser() {
    localStorage.removeItem('current_user');
    window.location.href = 'index.html';
}

// Initialize auth system when page loads
document.addEventListener('DOMContentLoaded', function() {
    initDatabase();
    checkAuth();
    
    // Add event listeners for forms if they exist on the page
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', registerUser);
    }
    
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', loginUser);
    }
});