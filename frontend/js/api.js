/**
 * API Service - Handles all HTTP requests to backend
 */

const API_BASE_URL = 'http://localhost/php-project/backend/api';

class ApiService {
    /**
     * Make HTTP request
     */
    async request(endpoint, options = {}) {
        const url = `${API_BASE_URL}${endpoint}`;
        
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Request failed');
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    /**
     * Get all users
     */
    async getUsers() {
        return this.request('/users.php');
    }

    /**
     * Get single user by ID
     */
    async getUser(id) {
        return this.request(`/users.php?id=${id}`);
    }

    /**
     * Create new user
     */
    async createUser(userData) {
        return this.request('/users.php', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    }

    /**
     * Update existing user
     */
    async updateUser(id, userData) {
        return this.request('/users.php', {
            method: 'PUT',
            body: JSON.stringify({ id, ...userData })
        });
    }

    /**
     * Delete user
     */
    async deleteUser(id) {
        return this.request('/users.php', {
            method: 'DELETE',
            body: JSON.stringify({ id })
        });
    }
}

// Export instance
const api = new ApiService();
