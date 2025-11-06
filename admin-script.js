jQuery(document).ready(function($) {
    
    function translatePost(retranslate = false) {
        const button = retranslate ? $('#pdt-retranslate-button') : $('#pdt-translate-button');
        const statusDiv = $('#pdt-translate-status');
        const originalText = button.html();
        
        // Collect selected custom fields
        const customFields = [];
        $('.pdt-custom-field:checked').each(function() {
            customFields.push($(this).val());
        });
        
        // Disable button and show loading
        button.prop('disabled', true).html('⏳ Translating...');
        statusDiv
            .removeClass('success error')
            .addClass('loading')
            .html('<strong>Translation in progress...</strong><br>This may take a few moments depending on content length.')
            .show();
        
        $.ajax({
            url: pdtData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'pdt_translate_post',
                nonce: pdtData.nonce,
                post_id: pdtData.postId,
                custom_fields: customFields
            },
            success: function(response) {
                if (response.success) {
                    statusDiv
                        .removeClass('loading error')
                        .addClass('success')
                        .html(
                            '<strong>✓ ' + response.data.message + '</strong><br>' +
                            '<a href="' + response.data.edit_link + '" class="button button-small" style="margin-top: 8px;">Open English Version</a>'
                        );
                    
                    // Reload page after 2 seconds to show updated state
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    statusDiv
                        .removeClass('loading success')
                        .addClass('error')
                        .html('<strong>✗ Translation Failed</strong><br>' + response.data.message);
                    button.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                statusDiv
                    .removeClass('loading success')
                    .addClass('error')
                    .html('<strong>✗ Translation Failed</strong><br>Network error. Please check your internet connection and try again.');
                button.prop('disabled', false).html(originalText);
                console.error('Translation error:', error);
            }
        });
    }
    
    // Handle translate button click
    $(document).on('click', '#pdt-translate-button', function(e) {
        e.preventDefault();
        
        // Confirm if post has unsaved changes
        if (wp.data && wp.data.select('core/editor')) {
            const isDirty = wp.data.select('core/editor').isEditedPostDirty();
            if (isDirty) {
                if (!confirm('You have unsaved changes. It\'s recommended to save your post first.\n\nTranslate anyway?')) {
                    return;
                }
            }
        }
        
        translatePost(false);
    });
    
    // Handle re-translate button click
    $(document).on('click', '#pdt-retranslate-button', function(e) {
        e.preventDefault();
        
        if (confirm('This will overwrite the existing English translation with a new translation.\n\nContinue?')) {
            translatePost(true);
        }
    });
    
});