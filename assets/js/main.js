/* ============================================
   TRANSITOPS - MAIN JAVASCRIPT
   ============================================ */

$(document).ready(function() {
    'use strict';

    // ----- Auto-hide alerts after 5 seconds -----
    setTimeout(function() {
        $('.alert').fadeOut(500, function() {
            $(this).remove();
        });
    }, 5000);

    // ----- Enable Bootstrap Tooltips -----
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // ----- Enable Bootstrap Popovers -----
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // ----- Delete Confirmation with SweetAlert2 -----
    $(document).on('click', '.delete-confirm', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        var itemName = $(this).data('item') || 'this item';
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve) {
                    setTimeout(function() {
                        resolve();
                    }, 500);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });

    // ----- Status Change Confirmation -----
    $(document).on('click', '.status-change', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        var action = $(this).data('action') || 'change status of';
        var itemName = $(this).data('item') || 'this item';
        
        Swal.fire({
            title: 'Confirm Action',
            html: `Are you sure you want to <strong>${action}</strong> ${itemName}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, proceed!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });

    // ----- Form Validation Enhancements -----
    $('form').on('submit', function(e) {
        var isValid = true;
        var firstInvalid = null;
        
        $(this).find('input[required], select[required], textarea[required]').each(function() {
            var $this = $(this);
            var value = $this.val();
            
            if (value === '' || value === null || value === undefined) {
                isValid = false;
                $this.addClass('is-invalid');
                if (!firstInvalid) {
                    firstInvalid = $this;
                }
            } else {
                $this.removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            if (firstInvalid) {
                firstInvalid.focus();
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please fill in all required fields correctly.',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'OK'
            });
        }
    });

    // ----- Real-time Validation on Input -----
    $('input, select, textarea').on('input change', function() {
        var $this = $(this);
        if ($this.prop('required') && $this.val() !== '') {
            $this.removeClass('is-invalid');
            $this.addClass('is-valid');
        } else if ($this.prop('required')) {
            $this.removeClass('is-valid');
            $this.addClass('is-invalid');
        }
    });

    // ----- Number Input Validation -----
    $('input[type="number"]').on('keypress', function(e) {
        var charCode = e.which ? e.which : e.keyCode;
        if (charCode === 46 || charCode === 45) {
            return true;
        }
        if (charCode < 48 || charCode > 57) {
            e.preventDefault();
            return false;
        }
        return true;
    });

    // ----- Password Toggle Visibility -----
    $(document).on('click', '.toggle-password', function() {
        var $input = $(this).closest('.input-group').find('input');
        var $icon = $(this).find('i');
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // ----- Search with Debounce -----
    var searchTimeout;
    $('.search-input').on('keyup', function() {
        clearTimeout(searchTimeout);
        var $this = $(this);
        searchTimeout = setTimeout(function() {
            $this.closest('form').submit();
        }, 500);
    });

    // ----- Modal Handlers -----
    $(document).on('show.bs.modal', '.modal', function() {
        $(this).find('.modal-content').addClass('fade-in');
    });

    // ----- Print Functionality -----
    $(document).on('click', '.print-btn', function() {
        window.print();
    });

    // ----- Export CSV (Bonus Feature) -----
    $(document).on('click', '.export-csv', function() {
        var table = $(this).data('table') || 'table';
        var filename = $(this).data('filename') || 'export.csv';
        
        var csv = [];
        $(table + ' thead th').each(function() {
            csv.push($(this).text().trim());
        });
        csv = [csv.join(',')];
        
        $(table + ' tbody tr').each(function() {
            var row = [];
            $(this).find('td').each(function() {
                var text = $(this).text().trim();
                if (text.includes(',')) {
                    text = '"' + text + '"';
                }
                row.push(text);
            });
            csv.push(row.join(','));
        });
        
        var blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
        URL.revokeObjectURL(link.href);
    });

    // ----- Keyboard Shortcuts -----
    $(document).on('keydown', function(e) {
        // Alt + D = Dashboard
        if (e.altKey && e.key === 'd') {
            window.location.href = '../dashboard/index.php';
        }
        // Alt + V = Vehicles
        if (e.altKey && e.key === 'v') {
            window.location.href = '../vehicles/index.php';
        }
        // Alt + R = Drivers
        if (e.altKey && e.key === 'r') {
            window.location.href = '../drivers/index.php';
        }
        // Alt + T = Trips
        if (e.altKey && e.key === 't') {
            window.location.href = '../trips/index.php';
        }
    });

    // ----- Dark Mode Toggle (Bonus) -----
    $(document).on('click', '.dark-mode-toggle', function() {
        $('body').toggleClass('dark-mode');
        var isDark = $('body').hasClass('dark-mode');
        localStorage.setItem('darkMode', isDark);
        
        var icon = $(this).find('i');
        if (isDark) {
            icon.removeClass('fa-moon').addClass('fa-sun');
        } else {
            icon.removeClass('fa-sun').addClass('fa-moon');
        }
        
        Swal.fire({
            icon: 'success',
            title: isDark ? 'Dark Mode Enabled' : 'Light Mode Enabled',
            timer: 1500,
            showConfirmButton: false
        });
    });

    // ----- Check for saved dark mode preference -----
    if (localStorage.getItem('darkMode') === 'true') {
        $('body').addClass('dark-mode');
        $('.dark-mode-toggle i').removeClass('fa-moon').addClass('fa-sun');
    }

    // ----- Notifications (Mock) -----
    function checkNotifications() {
        $.ajax({
            url: '../api/notifications.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.count > 0) {
                    $('.notification-badge').text(data.count).show();
                }
            },
            error: function() {
                // Silently fail
            }
        });
    }

    // Check notifications every 60 seconds
    if ($('.notification-badge').length) {
        checkNotifications();
        setInterval(checkNotifications, 60000);
    }
});

// ----- Utility Functions -----

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

// Format date
function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Format date with time
function formatDateTime(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Get status badge class
function getStatusClass(status) {
    var statusMap = {
        'Available': 'success',
        'On_Trip': 'warning',
        'In_Shop': 'danger',
        'Retired': 'secondary',
        'Off_Duty': 'info',
        'Suspended': 'danger',
        'Draft': 'secondary',
        'Dispatched': 'primary',
        'Completed': 'success',
        'Cancelled': 'danger',
        'Active': 'success',
        'Closed': 'secondary'
    };
    return statusMap[status] || 'secondary';
}

// Generate random color
function getRandomColor() {
    var colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe', '#43e97b', '#38f9d7'];
    return colors[Math.floor(Math.random() * colors.length)];
}

// Debounce function
function debounce(func, wait) {
    var timeout;
    return function executedFunction() {
        var context = this;
        var args = arguments;
        var later = function() {
            clearTimeout(timeout);
            func.apply(context, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle function
function throttle(func, limit) {
    var inThrottle;
    return function() {
        var context = this;
        var args = arguments;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(function() {
                inThrottle = false;
            }, limit);
        }
    };
}