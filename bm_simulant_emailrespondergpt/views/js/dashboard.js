$(document).ready(function() {
    // Test Connection Button Handler
    $('#testConnection').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var originalText = $btn.html();
        
        // Show loading state
        $btn.html('<i class="icon-refresh icon-spin"></i> ' + testingConnectionText).prop('disabled', true);
        
        // Send AJAX request
        $.ajax({
            url: currentIndex + '&token=' + token + '&' + $.param({
                controller: 'AdminSimulantAISettings',
                action: 'TestConnection',
                ajax: 1
            }),
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.message);
                } else {
                    showErrorMessage(response.message);
                }
            },
            error: function() {
                showErrorMessage(connectionErrorText);
            },
            complete: function() {
                // Restore button state
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Check Inbox Button Handler
    $('#checkInbox').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var originalText = $btn.html();
        
        // Show loading state
        $btn.html('<i class="icon-refresh icon-spin"></i> ' + checkingInboxText).prop('disabled', true);
        
        // Send AJAX request
        $.ajax({
            url: currentIndex + '&token=' + token + '&' + $.param({
                controller: 'AdminSimulantAISettings',
                action: 'CheckInbox',
                ajax: 1
            }),
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.message);
                    // Refresh stats after successful check
                    refreshStats();
                } else {
                    showErrorMessage(response.message);
                }
            },
            error: function() {
                showErrorMessage(inboxCheckErrorText);
            },
            complete: function() {
                // Restore button state
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Stats Refresh Function
    function refreshStats() {
        $.ajax({
            url: currentIndex + '&token=' + token + '&' + $.param({
                controller: 'AdminSimulantAISettings',
                action: 'GetStats',
                ajax: 1
            }),
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateStatsDisplay(response.data);
                }
            }
        });
    }

    // Update Stats Display
    function updateStatsDisplay(stats) {
        $('.stat-box').each(function() {
            var $box = $(this);
            var statKey = $box.data('stat-key');
            if (stats[statKey] !== undefined) {
                $box.find('.stat-number').text(stats[statKey]);
            }
        });
    }

    // Copy Cron Command
    $('.copy-cron-command').on('click', function(e) {
        e.preventDefault();
        var command = $(this).data('command');
        
        // Create temporary textarea
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(command).select();
        
        try {
            // Copy command
            document.execCommand('copy');
            showSuccessMessage(cronCommandCopiedText);
        } catch (err) {
            showErrorMessage(copyFailedText);
        } finally {
            $temp.remove();
        }
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Initialize activity list auto-refresh
    if ($('.activity-list').length) {
        setInterval(refreshActivity, 60000); // Refresh every minute
    }

    // Refresh Activity List
    function refreshActivity() {
        $.ajax({
            url: currentIndex + '&token=' + token + '&' + $.param({
                controller: 'AdminSimulantAISettings',
                action: 'GetRecentActivity',
                ajax: 1
            }),
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateActivityList(response.data);
                }
            }
        });
    }

    // Update Activity List
    function updateActivityList(activities) {
        var $list = $('.activity-list');
        if (!$list.length || !activities.length) return;

        var html = '';
        activities.forEach(function(activity) {
            html += `
                <div class="activity-item">
                    <span class="badge badge-${activity.type}">${activity.type}</span>
                    <span class="activity-time">${activity.time}</span>
                    <span class="activity-text">${activity.message}</span>
                </div>
            `;
        });

        $list.html(html);
    }

    // Handle Setup Step Clicks
    $('.setup-steps .step:not(.done)').on('click', function() {
        var $step = $(this);
        var $btn = $step.find('.btn');
        if ($btn.length) {
            window.location.href = $btn.attr('href');
        }
    });

    // Auto-refresh stats periodically
    setInterval(refreshStats, 300000); // Every 5 minutes
});

// Show Success Message
function showSuccessMessage(message) {
    $.growl.notice({
        title: "",
        message: message,
        duration: 4000
    });
}

// Show Error Message
function showErrorMessage(message) {
    $.growl.error({
        title: "",
        message: message,
        duration: 4000
    });
}

// Add to PrestaShop's ready queue
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts if they exist
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }
});

// Initialize Dashboard Charts
function initializeCharts() {
    // Only initialize if the elements exist
    if (document.getElementById('emailStatsChart')) {
        initializeEmailStatsChart();
    }
    if (document.getElementById('responseTimeChart')) {
        initializeResponseTimeChart();
    }
}

// Initialize Email Stats Chart
function initializeEmailStatsChart() {
    const ctx = document.getElementById('emailStatsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: emailStatsData, // This should be provided by the template
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Initialize Response Time Chart
function initializeResponseTimeChart() {
    const ctx = document.getElementById('responseTimeChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: responseTimeData, // This should be provided by the template
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}