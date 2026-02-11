/**
 * Authentication JavaScript
 * Handles login, registration, and session management
 */

const AUTH_API_URL = 'http://localhost/career-guide/backend/api/auth.php';

/**
 * Toggle password visibility
 */
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggle = field.nextElementSibling;
    
    if (field.type === 'password') {
        field.type = 'text';
        toggle.textContent = '🙈';
    } else {
        field.type = 'password';
        toggle.textContent = '👁';
    }
}

/**
 * Show error message
 */
function showError(message) {
    const errorDiv = document.getElementById('errorMessage');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }
}

/**
 * Show success message
 */
function showSuccess(message) {
    const successDiv = document.getElementById('successMessage');
    if (successDiv) {
        successDiv.textContent = message;
        successDiv.style.display = 'block';
        setTimeout(() => {
            successDiv.style.display = 'none';
        }, 5000);
    }
}

/**
 * Hide all messages
 */
function hideMessages() {
    const errorDiv = document.getElementById('errorMessage');
    const successDiv = document.getElementById('successMessage');
    if (errorDiv) errorDiv.style.display = 'none';
    if (successDiv) successDiv.style.display = 'none';
}

/**
 * Initialize login page
 */
function initLoginPage() {
    const loginForm = document.getElementById('loginForm');
    if (!loginForm) return;
    
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideMessages();
        
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const loginBtn = document.getElementById('loginBtn');
        
        // Validate
        if (!email || !password) {
            showError('Please fill in all fields');
            return;
        }
        
        // Disable button
        loginBtn.disabled = true;
        loginBtn.textContent = 'Logging in...';
        
        try {
            const response = await fetch(`${AUTH_API_URL}?action=login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Store user info in localStorage
                localStorage.setItem('user', JSON.stringify(data.user));
                
                // Redirect to main app
                window.location.href = 'index.html';
            } else {
                showError(data.message || 'Login failed');
                loginBtn.disabled = false;
                loginBtn.textContent = 'Login';
            }
        } catch (error) {
            console.error('Login error:', error);
            showError('Unable to login. Please try again.');
            loginBtn.disabled = false;
            loginBtn.textContent = 'Login';
        }
    });
}

/**
 * Initialize register page
 */
function initRegisterPage() {
    const registerForm = document.getElementById('registerForm');
    if (!registerForm) return;
    
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideMessages();
        
        const fullName = document.getElementById('fullName').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const registerBtn = document.getElementById('registerBtn');
        
        // Validate
        if (!fullName || !email || !password || !confirmPassword) {
            showError('Please fill in all fields');
            return;
        }
        
        if (password !== confirmPassword) {
            showError('Passwords do not match');
            return;
        }
        
        if (password.length < 6) {
            showError('Password must be at least 6 characters long');
            return;
        }
        
        // Disable button
        registerBtn.disabled = true;
        registerBtn.textContent = 'Creating account...';
        
        try {
            const response = await fetch(`${AUTH_API_URL}?action=register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    full_name: fullName,
                    email: email,
                    password: password
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showSuccess('Account created successfully! Redirecting to login...');
                registerForm.reset();
                
                // Redirect to login after 2 seconds
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                showError(data.message || 'Registration failed');
                registerBtn.disabled = false;
                registerBtn.textContent = 'Register';
            }
        } catch (error) {
            console.error('Registration error:', error);
            showError('Unable to register. Please try again.');
            registerBtn.disabled = false;
            registerBtn.textContent = 'Register';
        }
    });
}

/**
 * Check if user is logged in
 */
async function checkAuth() {
    try {
        const response = await fetch(`${AUTH_API_URL}?action=check`);
        const data = await response.json();
        
        return data.logged_in;
    } catch (error) {
        console.error('Auth check error:', error);
        return false;
    }
}

/**
 * Logout user
 */
async function logout() {
    try {
        await fetch(`${AUTH_API_URL}?action=logout`, {
            method: 'POST'
        });
        
        localStorage.removeItem('user');
        window.location.href = 'login.html';
    } catch (error) {
        console.error('Logout error:', error);
        window.location.href = 'login.html';
    }
}

/**
 * Get current user from localStorage
 */
function getCurrentUser() {
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
}

/**
 * Initialize forgot password page
 */
function initForgotPasswordPage() {
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    if (!forgotPasswordForm) return;
    
    forgotPasswordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideMessages();
        
        const email = document.getElementById('email').value;
        const continueBtn = document.getElementById('continueBtn');
        
        // Validate
        if (!email) {
            showError('Please enter your email address');
            return;
        }
        
        // Disable button
        continueBtn.disabled = true;
        continueBtn.textContent = 'Sending code...';
        
        try {
            const response = await fetch(`${AUTH_API_URL}?action=send-otp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Store email in sessionStorage for next steps
                sessionStorage.setItem('reset_email', email);
                
                showSuccess('Verification code sent! Redirecting...');
                
                // Redirect to verify OTP page
                setTimeout(() => {
                    window.location.href = 'verify-otp.html';
                }, 1500);
            } else {
                showError(data.message || 'Failed to send verification code');
                continueBtn.disabled = false;
                continueBtn.textContent = 'Continue';
            }
        } catch (error) {
            console.error('Send OTP error:', error);
            showError('Unable to send verification code. Please try again.');
            continueBtn.disabled = false;
            continueBtn.textContent = 'Continue';
        }
    });
}

/**
 * Initialize verify OTP page
 */
function initVerifyOtpPage() {
    const verifyOtpForm = document.getElementById('verifyOtpForm');
    if (!verifyOtpForm) return;
    
    // Get email from sessionStorage
    const email = sessionStorage.getItem('reset_email');
    if (!email) {
        window.location.href = 'forgot-password.html';
        return;
    }
    
    // Display email
    const userEmailElement = document.getElementById('userEmail');
    if (userEmailElement) {
        userEmailElement.textContent = email;
    }
    
    // OTP input auto-focus logic
    const otpInputs = document.querySelectorAll('.otp-digit');
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            const value = e.target.value;
            
            // Only allow numbers
            if (!/^\d*$/.test(value)) {
                e.target.value = '';
                return;
            }
            
            // Move to next input
            if (value && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
        });
        
        input.addEventListener('keydown', (e) => {
            // Move to previous input on backspace
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
        
        // Handle paste
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = e.clipboardData.getData('text').trim();
            
            if (/^\d{6}$/.test(pasteData)) {
                pasteData.split('').forEach((char, i) => {
                    if (otpInputs[i]) {
                        otpInputs[i].value = char;
                    }
                });
                otpInputs[5].focus();
            }
        });
    });
    
    // Auto-focus first input
    otpInputs[0].focus();
    
    // Form submission
    verifyOtpForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideMessages();
        
        // Collect OTP
        const otp = Array.from(otpInputs).map(input => input.value).join('');
        const verifyBtn = document.getElementById('verifyBtn');
        
        // Validate
        if (otp.length !== 6) {
            showError('Please enter the complete 6-digit code');
            return;
        }
        
        // Disable button
        verifyBtn.disabled = true;
        verifyBtn.textContent = 'Verifying...';
        
        try {
            const response = await fetch(`${AUTH_API_URL}?action=verify-otp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, otp })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Store verification token
                sessionStorage.setItem('otp_verified', 'true');
                
                showSuccess('Code verified! Redirecting...');
                
                // Redirect to reset password page
                setTimeout(() => {
                    window.location.href = 'reset-password.html';
                }, 1500);
            } else {
                showError(data.message || 'Invalid verification code');
                verifyBtn.disabled = false;
                verifyBtn.textContent = 'Verify';
                
                // Clear OTP inputs
                otpInputs.forEach(input => input.value = '');
                otpInputs[0].focus();
            }
        } catch (error) {
            console.error('Verify OTP error:', error);
            showError('Unable to verify code. Please try again.');
            verifyBtn.disabled = false;
            verifyBtn.textContent = 'Verify';
        }
    });
    
    // Resend OTP functionality
    const resendBtn = document.getElementById('resendBtn');
    if (resendBtn) {
        resendBtn.addEventListener('click', async () => {
            hideMessages();
            resendBtn.disabled = true;
            resendBtn.textContent = 'Sending...';
            
            try {
                const response = await fetch(`${AUTH_API_URL}?action=send-otp`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess('New code sent to your email!');
                    
                    // Clear inputs
                    otpInputs.forEach(input => input.value = '');
                    otpInputs[0].focus();
                    
                    // Re-enable after 30 seconds
                    setTimeout(() => {
                        resendBtn.disabled = false;
                        resendBtn.textContent = 'Resend OTP';
                    }, 30000);
                } else {
                    showError(data.message || 'Failed to resend code');
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'Resend OTP';
                }
            } catch (error) {
                console.error('Resend OTP error:', error);
                showError('Unable to resend code. Please try again.');
                resendBtn.disabled = false;
                resendBtn.textContent = 'Resend OTP';
            }
        });
    }
}

/**
 * Initialize reset password page
 */
function initResetPasswordPage() {
    const resetPasswordForm = document.getElementById('resetPasswordForm');
    if (!resetPasswordForm) return;
    
    // Check if OTP was verified
    const email = sessionStorage.getItem('reset_email');
    const otpVerified = sessionStorage.getItem('otp_verified');
    
    if (!email || !otpVerified) {
        window.location.href = 'forgot-password.html';
        return;
    }
    
    resetPasswordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideMessages();
        
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const saveBtn = document.getElementById('saveBtn');
        
        // Validate
        if (!newPassword || !confirmPassword) {
            showError('Please fill in all fields');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            showError('Passwords do not match');
            return;
        }
        
        if (newPassword.length < 6) {
            showError('Password must be at least 6 characters long');
            return;
        }
        
        // Disable button
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';
        
        try {
            const response = await fetch(`${AUTH_API_URL}?action=reset-password`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    email, 
                    password: newPassword 
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showSuccess('Password reset successfully! Redirecting to login...');
                
                // Clear session storage
                sessionStorage.removeItem('reset_email');
                sessionStorage.removeItem('otp_verified');
                
                resetPasswordForm.reset();
                
                // Redirect to login after 2 seconds
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                showError(data.message || 'Failed to reset password');
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Password';
            }
        } catch (error) {
            console.error('Reset password error:', error);
            showError('Unable to reset password. Please try again.');
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Password';
        }
    });
}
