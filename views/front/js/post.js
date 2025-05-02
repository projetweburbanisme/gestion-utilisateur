// Create a new post
function createPost(e, serviceType) {
    e.preventDefault();

    const user = JSON.parse(localStorage.getItem('current_user'));
    if (!user) {
        alert('Please login to create a post!');
        window.location.href = 'login.html';
        return;
    }

    const formData = new FormData(document.getElementById('post-form'));
    formData.append('service_type', serviceType);

    // Log the FormData being sent
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }

    fetch('../../controllers/HomeRentController.php?action=create', {
        method: 'POST',
        body: formData,
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Post created successfully!');
                window.location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error creating post:', error);
            alert('An error occurred while creating the post. Please try again.');
        });
}

// Load posts for a specific service
function loadPosts(serviceType) {
    fetch(`../../controllers/HomeRentController.php?action=fetch&service_type=${serviceType}`)
        .then(response => response.json())
        .then(posts => {
            const postsContainer = document.getElementById('posts-container');
            if (postsContainer) {
                postsContainer.innerHTML = '';

                if (posts.length === 0) {
                    postsContainer.innerHTML = '<p class="no-posts">No posts available. Be the first to create one!</p>';
                    return;
                }

                posts.forEach(post => {
                    const postElement = document.createElement('div');
                    postElement.className = 'post-card';
                    postElement.innerHTML = `
                        <div class="post-header">
                            <h3>${post.title}</h3>
                            <span class="post-category">${post.property_type}</span>
                        </div>
                        ${post.main_photo_url ? `<div class="post-image"><img src="${post.main_photo_url}" alt="${post.title}"></div>` : ''}
                        <div class="post-content">
                            <p>${post.description}</p>
                        </div>
                        <div class="post-footer">
                            <span class="post-author">Posted by ${post.user_name}</span>
                            <span class="post-date">${new Date(post.created_at).toLocaleDateString()}</span>
                        </div>
                        <div class="post-actions">
                            <button class="action-btn delete-btn" data-id="${post.rental_id}">Delete</button>
                        </div>
                    `;
                    postsContainer.appendChild(postElement);
                });

                // Add event listeners for delete buttons
                document.querySelectorAll('.delete-btn').forEach(button => {
                    button.addEventListener('click', handleDeletePost);
                });
            }
        })
        .catch(error => {
            console.error('Error loading posts:', error);
        });
}

// Handle post deletion
function handleDeletePost(event) {
    const postId = event.target.dataset.id;

    if (confirm('Are you sure you want to delete this post?')) {
        fetch(`../../controllers/HomeRentController.php?action=delete&id=${postId}`, { method: 'GET' })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Post deleted successfully!');
                    loadPosts('home-rent'); // Refresh the posts
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error deleting post:', error);
                alert('An error occurred while deleting the post. Please try again.');
            });
    }
}

// Initialize posts when page loads
document.addEventListener('DOMContentLoaded', function () {
    const postForm = document.getElementById('post-form');
    if (postForm) {
        const serviceType = 'home-rent';
        postForm.addEventListener('submit', (e) => createPost(e, serviceType));
    }

    loadPosts('home-rent'); // Load posts for the "home-rent" service
});