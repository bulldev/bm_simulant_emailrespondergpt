$(document).ready(function() {
    // Handle test connection button click
    $('button[name="testConnection"]').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalText = $button.html();
        
        // Show loading state
        $button.html('<i class="icon-refresh icon-spin"></i> ' + testingConnectionText).prop('disabled', true);
        
        // Collect current form values
        var formData = {
            'SIMULANT_AI_IMAP_HOST': $('input[name="SIMULANT_AI_IMAP_HOST"]').val(),
            'SIMULANT_AI_IMAP_USER': $('input[name="SIMULANT_AI_IMAP_USER"]').val(),
            'SIMULANT_AI_IMAP_PASS': $('input[name="SIMULANT_AI_IMAP_PASS"]').val(),
            'SIMULANT_AI_SMTP_HOST': $('input[name="SIMULANT_AI_SMTP_HOST"]').val(),
            'SIMULANT_AI_SMTP_USER': $('input[name="SIMULANT_AI_SMTP_USER"]').val(),
            'SIMULANT_AI_SMTP_PASS': $('input[name="SIMULANT_AI_SMTP_PASS"]').val(),
            'SIMULANT_AI_OPENAI_KEY': $('input[name="SIMULANT_AI_OPENAI_KEY"]').val(),
            'ajax': 1,
            'action': 'TestConnection'
        };

        // Send AJAX request
        $.ajax({
            url: currentIndex + '&token=' + token + '&' + $.param({controller: 'AdminSimulantAISettings'}),
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.message);
                } else {
                    showErrorMessage(response.message);
                }
            },
            error: function(xhr, status, error) {
                showErrorMessage(connectionErrorText);
            },
            complete: function() {
                // Restore button state
                $button.html(originalText).prop('disabled', false);
            }
        });
    });

    // Handle password field visibility toggle
    $('.toggle-password').on('click', function(e) {
        e.preventDefault();
        var input = $($(this).data('target'));
        var icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('icon-eye').addClass('icon-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('icon-eye-slash').addClass('icon-eye');
        }
    });
});