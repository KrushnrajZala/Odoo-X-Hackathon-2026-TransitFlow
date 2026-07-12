/* ============================================
   TRANSITOPS - DASHBOARD JAVASCRIPT
   ============================================ */

$(document).ready(function() {
    'use strict';

    // ----- Initialize Dashboard Charts -----
    initializeCharts();

    // ----- Auto-refresh Statistics every 30 seconds -----
    if ($('#auto-refresh').length && $('#auto-refresh').val() === '1') {
        setInterval(refreshStats, 30000);
    }

    // ----- Animate Progress Bars -----
    $('.progress-bar').each(function() {
        var width = $(this).css('width');
        $(this).css('width', '0%');
        setTimeout(function() {
            $(this).css('width', width);
        }.bind(this), 300);
    });

    // ----- Counter Animation -----
    $('.stat-number').each(function() {
        var $this = $(this);
        var target = parseInt($this.text().replace(/,/g, ''));
        if (!isNaN(target)) {
            animateCounter($this, target);
        }
    });
});

// ----- Chart Initialization -----
function initializeCharts() {
    // Trip Activity Chart
    initTripChart();
    
    // Fleet Utilization Chart
    initUtilizationChart();
    
    // Expense Breakdown Chart
    initExpenseChart();
}

// ----- Trip Activity Chart -----
function initTripChart() {
    var canvas = document.getElementById('tripChart');
    if (!canvas) return;
    
    var ctx = canvas.getContext('2d');
    var data = JSON.parse($(canvas).attr('data-chart') || '{"labels":[],"values":[]}');
    
    var gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(102, 126, 234, 0.3)');
    gradient.addColorStop(1, 'rgba(102, 126, 234, 0)');
    
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Trips',
                data: data.values || [0, 0, 0, 0, 0, 0, 0],
                borderColor: '#667eea',
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#667eea',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#667eea',
                    borderWidth: 2,
                    cornerRadius: 10,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' trips';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        color: '#6c757d',
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)',
                        drawBorder: false
                    }
                },
                x: {
                    ticks: {
                        color: '#6c757d',
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            },
            animation: {
                duration: 1500
            }
        }
    });
    
    // Store chart instance for updates
    window.tripChart = chart;
}

// ----- Fleet Utilization Chart -----
function initUtilizationChart() {
    var canvas = document.getElementById('utilizationChart');
    if (!canvas) return;
    
    var ctx = canvas.getContext('2d');
    var data = JSON.parse($(canvas).attr('data-chart') || '{"utilization":0,"vehicles":[]}');
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'In Shop', 'Retired'],
            datasets: [{
                data: [
                    data.active || 0,
                    data.inShop || 0,
                    data.retired || 0
                ],
                backgroundColor: [
                    '#43e97b',
                    '#fda085',
                    '#a8a8a8'
                ],
                borderColor: '#fff',
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#667eea',
                    borderWidth: 2,
                    cornerRadius: 10,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                            var percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                            return context.label + ': ' + context.parsed + ' vehicles (' + percentage + '%)';
                        }
                    }
                }
            },
            cutout: '70%',
            animation: {
                animateRotate: true,
                duration: 1500
            }
        }
    });
}

// ----- Expense Breakdown Chart -----
function initExpenseChart() {
    var canvas = document.getElementById('expenseChart');
    if (!canvas) return;
    
    var ctx = canvas.getContext('2d');
    var data = JSON.parse($(canvas).attr('data-chart') || '{"labels":[],"values":[]}');
    
    var colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe'];
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels || ['Fuel', 'Maintenance', 'Tolls', 'Insurance', 'Other'],
            datasets: [{
                label: 'Expenses ($)',
                data: data.values || [0, 0, 0, 0, 0],
                backgroundColor: colors.map(function(color) {
                    return color + '80';
                }),
                borderColor: colors,
                borderWidth: 2,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#667eea',
                    borderWidth: 2,
                    cornerRadius: 10,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            return '$' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        },
                        color: '#6c757d',
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)',
                        drawBorder: false
                    }
                },
                x: {
                    ticks: {
                        color: '#6c757d',
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            },
            animation: {
                duration: 1500
            }
        }
    });
}

// ----- Refresh Statistics -----
function refreshStats() {
    $.ajax({
        url: '../dashboard/get_stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            // Update statistics cards
            if (data.active_vehicles !== undefined) {
                $('#stat-active-vehicles').text(data.active_vehicles);
                animateCounter($('#stat-active-vehicles'), data.active_vehicles);
            }
            if (data.available_vehicles !== undefined) {
                $('#stat-available-vehicles').text(data.available_vehicles);
                animateCounter($('#stat-available-vehicles'), data.available_vehicles);
            }
            if (data.active_trips !== undefined) {
                $('#stat-active-trips').text(data.active_trips);
                animateCounter($('#stat-active-trips'), data.active_trips);
            }
            if (data.drivers_on_duty !== undefined) {
                $('#stat-drivers-on-duty').text(data.drivers_on_duty);
                animateCounter($('#stat-drivers-on-duty'), data.drivers_on_duty);
            }
            if (data.fleet_utilization !== undefined) {
                $('#stat-utilization').text(data.fleet_utilization + '%');
                $('#utilization-bar').css('width', data.fleet_utilization + '%');
            }
            if (data.maintenance_vehicles !== undefined) {
                $('#stat-maintenance').text(data.maintenance_vehicles);
            }
            
            // Show refresh notification
            $('#last-refresh').text('Updated: ' + formatDateTime(new Date()));
            $('#refresh-indicator').fadeIn('fast').fadeOut('slow');
        },
        error: function() {
            // Silent fail
            console.log('Failed to refresh statistics');
        }
    });
}

// ----- Counter Animation -----
function animateCounter(element, target) {
    var duration = 1000;
    var start = parseInt(element.text().replace(/,/g, '')) || 0;
    var difference = target - start;
    var startTime = Date.now();
    
    function updateCounter() {
        var elapsed = Date.now() - startTime;
        var progress = Math.min(elapsed / duration, 1);
        var current = start + (difference * progress);
        
        if (target >= 1) {
            element.text(Math.round(current));
        } else {
            element.text(current.toFixed(1));
        }
        
        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        }
    }
    
    updateCounter();
}

// ----- Export Dashboard (Bonus) -----
$(document).on('click', '.export-dashboard', function() {
    Swal.fire({
        title: 'Export Dashboard',
        text: 'Choose export format:',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'PDF',
        cancelButtonText: 'CSV',
        showDenyButton: true,
        denyButtonText: 'Image'
    }).then((result) => {
        if (result.isConfirmed) {
            // PDF Export - Print to PDF
            window.print();
        } else if (result.isDismissed && result.dismiss === 'cancel') {
            // CSV Export
            exportDashboardCSV();
        } else if (result.isDenied) {
            // Image Export - Screenshot
            html2canvas(document.querySelector('.dashboard-content')).then(function(canvas) {
                var link = document.createElement('a');
                link.download = 'dashboard.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }
    });
});

// ----- Export Dashboard CSV -----
function exportDashboardCSV() {
    var csv = [];
    var rows = [];
    
    // Get statistics
    $('.stat-card').each(function() {
        var title = $(this).find('h6').text().trim();
        var value = $(this).find('h2, h3').text().trim();
        rows.push([title, value]);
    });
    
    // Convert to CSV
    rows.forEach(function(row) {
        csv.push(row.join(','));
    });
    
    var blob = new Blob(['Statistic,Value\n' + csv.join('\n')], { type: 'text/csv' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'dashboard-stats.csv';
    link.click();
    URL.revokeObjectURL(link.href);
}