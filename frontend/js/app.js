/**
 * Main Application Logic
 */

let isEditMode = false;
let editingUserId = null;

// DOM Elements
const userForm = document.getElementById('userForm');
const usersList = document.getElementById('usersList');
const loadingSpinner = document.getElementById('loadingSpinner');
const noUsers = document.getElementById('noUsers');
const submitBtn = document.getElementById('submitBtn');
const cancelBtn = document.getElementById('cancelBtn');
const refreshBtn = document.getElementById('refreshBtn');

/**
 * Initialize app
 */
document.addEventListener('DOMContentLoaded', async () => {
    // Check authentication
    const isLoggedIn = await checkAuth();
    if (!isLoggedIn) {
        window.location.href = 'login.html';
        return;
    }
    
    // Display logged in user name
    const user = getCurrentUser();
    if (user && user.full_name) {
        document.getElementById('userName').textContent = user.full_name;
    }
    
    loadUsers();
    setupEventListeners();
});


/**
 * Setup event listeners
 */
function setupEventListeners() {
    userForm.addEventListener('submit', handleFormSubmit);
    cancelBtn.addEventListener('click', cancelEdit);
    refreshBtn.addEventListener('click', loadUsers);
}

/**
 * Load all users from API
 */
async function loadUsers() {
    showLoading(true);
    usersList.innerHTML = '';
    
    try {
        const response = await api.getUsers();
        
        if (response.success && response.data.length > 0) {
            renderUsers(response.data);
            noUsers.style.display = 'none';
        } else {
            noUsers.style.display = 'block';
        }
    } catch (error) {
        showToast('Failed to load users', 'error');
        console.error('Error loading users:', error);
    } finally {
        showLoading(false);
    }
}

/**
 * Render users to DOM
 */
function renderUsers(users) {
    usersList.innerHTML = users.map(user => `
        <div class="user-card" data-id="${user.id}">
            <div class="user-info">
                <div class="user-name">${escapeHtml(user.name)}</div>
                <div class="user-email">${escapeHtml(user.email)}</div>
                ${user.phone ? `<div class="user-phone">${escapeHtml(user.phone)}</div>` : ''}
            </div>
            <div class="user-actions">
                <button class="btn btn-small btn-edit" onclick="editUser(${user.id})">
                    <span class="btn-text">Edit</span>
                </button>
                <button class="btn btn-small btn-delete" onclick="deleteUser(${user.id})">
                    <span class="btn-text">Delete</span>
                </button>
            </div>
        </div>
    `).join('');
}

/**
 * Handle form submission
 */
async function handleFormSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(userForm);
    const userData = {
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone') || null
    };
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="btn-text">Processing...</span>';
    
    try {
        if (isEditMode && editingUserId) {
            await api.updateUser(editingUserId, userData);
            showToast('User updated successfully!', 'success');
        } else {
            await api.createUser(userData);
            showToast('User created successfully!', 'success');
        }
        
        userForm.reset();
        cancelEdit();
        loadUsers();
    } catch (error) {
        showToast(error.message || 'Operation failed', 'error');
        console.error('Error:', error);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<span class="btn-text">Add User</span>';
    }
}

/**
 * Edit user
 */
async function editUser(id) {
    try {
        const response = await api.getUser(id);
        
        if (response.success && response.data) {
            const user = response.data;
            
            document.getElementById('name').value = user.name;
            document.getElementById('email').value = user.email;
            document.getElementById('phone').value = user.phone || '';
            
            isEditMode = true;
            editingUserId = id;
            
            submitBtn.innerHTML = '<span class="btn-text">Update User</span>';
            cancelBtn.style.display = 'inline-flex';
            
            // Scroll to form
            document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
        }
    } catch (error) {
        showToast('Failed to load user data', 'error');
        console.error('Error:', error);
    }
}

/**
 * Delete user
 */
async function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user?')) {
        return;
    }
    
    try {
        await api.deleteUser(id);
        showToast('User deleted successfully!', 'success');
        loadUsers();
    } catch (error) {
        showToast('Failed to delete user', 'error');
        console.error('Error:', error);
    }
}

/**
 * Cancel edit mode
 */
function cancelEdit() {
    isEditMode = false;
    editingUserId = null;
    userForm.reset();
    submitBtn.innerHTML = '<span class="btn-text">Add User</span>';
    cancelBtn.style.display = 'none';
}

/**
 * Show loading spinner
 */
function showLoading(show) {
    loadingSpinner.style.display = show ? 'block' : 'none';
}

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast show ${type}`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
