// Admin functionality
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is admin
    const user = JSON.parse(localStorage.getItem('current_user'));
    if (!user || !user.isAdmin) {
        alert('Access denied! Admins only.');
        window.location.href = 'index.html';
        return;
    }
    
    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons and content
            tabButtons.forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            const tabId = this.getAttribute('data-tab') + '-tab';
            document.getElementById(tabId).classList.add('active');
            
            // Load data for the tab
            if (this.getAttribute('data-tab') === 'users') {
                loadUsers();
            } else if (this.getAttribute('data-tab') === 'posts') {
                loadPosts();
            } else if (this.getAttribute('data-tab') === 'messages') {
                loadMessages();
            }
        });
    });
    
    // Load initial data
    loadUsers();
    
    // Filter posts when dropdown changes
    document.getElementById('post-filter-service')?.addEventListener('change', loadPosts);
    document.getElementById('post-filter-status')?.addEventListener('change', loadPosts);
});

function loadUsers() {
    const users = JSON.parse(localStorage.getItem('clyptor_users')) || [];
    const tableBody = document.getElementById('users-table');
    
    if (tableBody) {
        tableBody.innerHTML = '';
        
        users.forEach(user => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${user.id}</td>
                <td>${user.name}</td>
                <td>${user.email}</td>
                <td>${user.isAdmin ? 'Admin' : (user.verified ? 'Verified' : 'Pending')}</td>
                <td>
                    ${!user.isAdmin ? `<button class="admin-btn delete-user" data-id="${user.id}">Delete</button>` : ''}
                    ${!user.isAdmin && !user.verified ? `<button class="admin-btn verify-user" data-id="${user.id}">Verify</button>` : ''}
                </td>
            `;
            tableBody.appendChild(row);
        });
        
        // Add event listeners to buttons
        document.querySelectorAll('.delete-user').forEach(button => {
            button.addEventListener('click', function() {
                deleteUser(this.getAttribute('data-id'));
            });
        });
        
        document.querySelectorAll('.verify-user').forEach(button => {
            button.addEventListener('click', function() {
                verifyUser(this.getAttribute('data-id'));
            });
        });
    }
}

function loadPosts() {
    const posts = JSON.parse(localStorage.getItem('clyptor_posts')) || [];
    const serviceFilter = document.getElementById('post-filter-service')?.value || 'all';
    const statusFilter = document.getElementById('post-filter-status')?.value || 'all';
    
    const filteredPosts = posts.filter(post => {
        return (serviceFilter === 'all' || post.serviceType === serviceFilter) &&
               (statusFilter === 'all' || post.status === statusFilter);
    });
    
    const tableBody = document.getElementById('posts-table');
    
    if (tableBody) {
        tableBody.innerHTML = '';
        
        filteredPosts.forEach(post => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${post.id}</td>
                <td>${post.title}</td>
                <td>${post.serviceType}</td>
                <td>${post.userName}</td>
                <td>${new Date(post.date).toLocaleDateString()}</td>
                <td>${post.status}</td>
                <td>
                    <button class="admin-btn ${post.status === 'active' ? 'remove-post' : 'restore-post'}" 
                            data-id="${post.id}">
                        ${post.status === 'active' ? 'Remove' : 'Restore'}
                    </button>
                </td>
            `;
            tableBody.appendChild(row);
        });
        
        // Add event listeners to buttons
        document.querySelectorAll('.remove-post').forEach(button => {
            button.addEventListener('click', function() {
                togglePostStatus(this.getAttribute('data-id'), 'removed');
            });
        });
        
        document.querySelectorAll('.restore-post').forEach(button => {
            button.addEventListener('click', function() {
                togglePostStatus(this.getAttribute('data-id'), 'active');
            });
        });
    }
}

function loadMessages() {
    const messages = JSON.parse(localStorage.getItem('clyptor_contacts')) || [];
    const tableBody = document.getElementById('messages-table');
    
    if (tableBody) {
        tableBody.innerHTML = '';
        
        messages.forEach(message => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${message.id}</td>
                <td>${message.name}</td>
                <td>${message.email}</td>
                <td class="message-preview">${message.message.substring(0, 50)}...</td>
                <td>${new Date(message.date).toLocaleDateString()}</td>
                <td>
                    <button class="admin-btn view-message" data-id="${message.id}">View</button>
                    <button class="admin-btn delete-message" data-id="${message.id}">Delete</button>
                </td>
            `;
            tableBody.appendChild(row);
        });
        
        // Add event listeners to buttons
        document.querySelectorAll('.view-message').forEach(button => {
            button.addEventListener('click', function() {
                viewMessage(this.getAttribute('data-id'));
            });
        });
        
        document.querySelectorAll('.delete-message').forEach(button => {
            button.addEventListener('click', function() {
                deleteMessage(this.getAttribute('data-id'));
            });
        });
    }
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        const users = JSON.parse(localStorage.getItem('clyptor_users'));
        const updatedUsers = users.filter(user => user.id !== parseInt(userId));
        localStorage.setItem('clyptor_users', JSON.stringify(updatedUsers));
        loadUsers();
    }
}

function verifyUser(userId) {
    const users = JSON.parse(localStorage.getItem('clyptor_users'));
    const userIndex = users.findIndex(user => user.id === parseInt(userId));
    
    if (userIndex !== -1) {
        users[userIndex].verified = true;
        localStorage.setItem('clyptor_users', JSON.stringify(users));
        loadUsers();
    }
}

function togglePostStatus(postId, newStatus) {
    const posts = JSON.parse(localStorage.getItem('clyptor_posts'));
    const postIndex = posts.findIndex(post => post.id === parseInt(postId));
    
    if (postIndex !== -1) {
        posts[postIndex].status = newStatus;
        localStorage.setItem('clyptor_posts', JSON.stringify(posts));
        loadPosts();
    }
}

function viewMessage(messageId) {
    const messages = JSON.parse(localStorage.getItem('clyptor_contacts'));
    const message = messages.find(msg => msg.id === parseInt(messageId));
    
    if (message) {
        alert(`Message from ${message.name} (${message.email}):\n\n${message.message}`);
    }
}

function deleteMessage(messageId) {
    if (confirm('Are you sure you want to delete this message?')) {
        const messages = JSON.parse(localStorage.getItem('clyptor_contacts'));
        const updatedMessages = messages.filter(msg => msg.id !== parseInt(messageId));
        localStorage.setItem('clyptor_contacts', JSON.stringify(updatedMessages));
        loadMessages();
    }
}