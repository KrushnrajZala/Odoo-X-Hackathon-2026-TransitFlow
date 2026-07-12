/* ============================================
   TRANSITOPS - AUTHENTICATION JAVASCRIPT
   ============================================ */

$(document).ready(function() {
    'use strict';

    // ----- Demo Credentials Auto-fill -----
    $('.demo-btn').click(function() {
        var email = $(this).data('email');
        var password = $(this).data('password');
        
        $('#email').val(email);
        $('#password').val(password);
        
        // Highlight the selected demo button
        $('.demo-btn').removeClass('active-demo');
        $(this).addClass('active-demo');
        
        // Trigger validation
        $('#email').trigger('input');
        $('#password').trigger('input');
        
        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'Credentials Filled!',
            text: 'Click Sign In to continue.',
            timer: 1500,
            showConfirmButton: false,
            position: 'center'
        });
        
        // Auto-submit after 1.5 seconds
        setTimeout(function() {
            $('#loginForm').submit();
        }, 1500);
    });

    // ----- Login Form Validation -----
    $('#loginForm').on('submit', function(e) {
        var email = $('#email').val().trim();
        var password = $('#password').val().trim();
        var isValid = true;
        var errors = [];
        
        // Validate email
        if (email === '') {
            $('#email').addClass('is-invalid');
            $('#email-error').text('Email address is required.');
            errors.push('Email address is required.');
            isValid = false;
        } else if (!isValidEmail(email)) {
            $('#email').addClass('is-invalid');
            $('#email-error').text('Please enter a valid email address.');
            errors.push('Please enter a valid email address.');
            isValid = false;
        } else {
            $('#email').removeClass('is-invalid');
            $('#email').addClass('is-valid');
        }
        
        // Validate password
        if (password === '') {
            $('#password').addClass('is-invalid');
            $('#password-error').text('Password is required.');
            errors.push('Password is required.');
            isValid = false;
        } else if (password.length < 6) {
            $('#password').addClass('is-invalid');
            $('#password-error').text('Password must be at least 6 characters.');
            errors.push('Password must be at least 6 characters.');
            isValid = false;
        } else {
            $('#password').removeClass('is-invalid');
            $('#password').addClass('is-valid');
        }
        
        if (!isValid) {
            e.preventDefault();
            
            // Show first error
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: errors[0] || 'Please fill in all fields correctly.',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'OK'
            });
            
            // Focus on first invalid field
            var firstInvalid = $('#loginForm').find('.is-invalid:first');
            if (firstInvalid.length) {
                firstInvalid.focus();
            }
        } else {
            // Show loading state
            var submitBtn = $(this).find('button[type="submit"]');
            var originalHtml = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Signing In...');
            submitBtn.prop('disabled', true);
            
            // Re-enable after submission
            setTimeout(function() {
                submitBtn.html(originalHtml);
                submitBtn.prop('disabled', false);
            }, 5000);
        }
    });

    // ----- Real-time Email Validation -----
    $('#email').on('input', function() {
        var value = $(this).val().trim();
        var $error = $('#email-error');
        
        if (value === '') {
            $(this).removeClass('is-valid is-invalid');
            $error.text('');
        } else if (isValidEmail(value)) {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $error.text('');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $error.text('Please enter a valid email address.');
        }
    });

    // ----- Real-time Password Validation -----
    $('#password').on('input', function() {
        var value = $(this).val();
        var $error = $('#password-error');
        
        if (value === '') {
            $(this).removeClass('is-valid is-invalid');
            $error.text('');
        } else if (value.length < 6) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $error.text('Password must be at least 6 characters.');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $error.text('');
        }
        
        // Update password strength
        updatePasswordStrength(value);
    });

    // ----- Password Strength Indicator -----
    function updatePasswordStrength(password) {
        var strength = 0;
        var indicator = $('#password-strength');
        
        if (!indicator.length) return;
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        var strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        var strengthColor = ['danger', 'warning', 'info', 'primary', 'success'];
        var strengthPercent = ['20%', '40%', '60%', '80%', '100%'];
        
        var level = Math.min(strength, 4);
        
        indicator.find('.strength-text').text(strengthText[level]);
        indicator.find('.strength-bar').css({
            'width': strengthPercent[level],
            'background': 'var(--' + strengthColor[level] + '-color)'
        });
        
        // Show/hide indicator
        if (password.length > 0) {
            indicator.show();
        } else {
            indicator.hide();
        }
    }

    // ----- Email Validation Helper -----
    function isValidEmail(email) {
        var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return pattern.test(email);
    }

    // ----- Password Visibility Toggle -----
    $('#togglePassword').click(function() {
        var passwordInput = $('#password');
        var icon = $(this).find('i');
        
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
            $(this).attr('title', 'Hide password');
        } else {
            passwordInput.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
            $(this).attr('title', 'Show password');
        }
    });

    // ----- Check for URL Parameters -----
    var urlParams = new URLSearchParams(window.location.search);
    
    // Check for logout success
    if (urlParams.get('logout') === 'success') {
        Swal.fire({
            icon: 'success',
            title: 'Logged Out Successfully',
            text: 'You have been safely logged out.',
            timer: 3000,
            showConfirmButton: false,
            position: 'center'
        });
        // Remove parameter from URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    // Check for session expired
    if (urlParams.get('expired') === '1') {
        Swal.fire({
            icon: 'warning',
            title: 'Session Expired',
            text: 'Your session has expired. Please login again.',
            confirmButtonColor: '#0d6efd',
            confirmButtonText: 'OK'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    // Check for login required
    if (urlParams.get('login') === 'required') {
        Swal.fire({
            icon: 'info',
            title: 'Login Required',
            text: 'Please login to access this page.',
            confirmButtonColor: '#0d6efd',
            confirmButtonText: 'OK'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // ----- Forgot Password -----
    $('#forgotPassword').click(function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Reset Password',
            html: '<input type="email" id="reset-email" class="form-control" placeholder="Enter your email">',
            showCancelButton: true,
            confirmButtonText: 'Send Reset Link',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            preConfirm: function() {
                var email = $('#reset-email').val();
                if (!email || !isValidEmail(email)) {
                    Swal.showValidationMessage('Please enter a valid email address.');
                    return false;
                }
                return email;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Reset Link Sent',
                    text: 'A password reset link has been sent to your email.',
                    confirmButtonColor: '#0d6efd'
                });
            }
        });
    });

    // ----- Remember Me -----
    $('#rememberMe').change(function() {
        if ($(this).is(':checked')) {
            localStorage.setItem('rememberEmail', $('#email').val());
        } else {
            localStorage.removeItem('rememberEmail');
        }
    });
    
    // Load remembered email
    var rememberedEmail = localStorage.getItem('rememberEmail');
    if (rememberedEmail) {
        $('#email').val(rememberedEmail);
        $('#rememberMe').prop('checked', true);
    }

    // ----- Keyboard Shortcuts -----
    $(document).on('keydown', function(e) {
        // Escape key to close alerts
        if (e.key === 'Escape') {
            $('.alert').fadeOut('fast');
            Swal.close();
        }
        
        // Enter key on login form
        if (e.key === 'Enter' && $('#loginForm').length) {
            $('#loginForm').submit();
        }
    });

    // ----- Prevent multiple form submissions -----
    $('#loginForm').on('submit', function() {
        var $submitBtn = $(this).find('button[type="submit"]');
        if ($submitBtn.prop('disabled')) {
            return false;
        }
        return true;
    });

    // ----- Browser autofill detection -----
    setTimeout(function() {
        if ($('#email').val() && $('#password').val()) {
            $('#email').trigger('input');
            $('#password').trigger('input');
        }
    }, 500);
});