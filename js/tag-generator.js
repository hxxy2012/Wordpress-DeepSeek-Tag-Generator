jQuery(document).ready(function($) {
    var $generateButton = $('#deepseek-generate-tags');
    var $loadingIndicator = $('#deepseek-tags-loading');
    var $resultContainer = $('#deepseek-tags-result');
    var $tagsList = $('#deepseek-tags-list');
    var $errorContainer = $('#deepseek-tags-error');
    
    $generateButton.on('click', function() {
        // Hide previous results
        $resultContainer.hide();
        $errorContainer.hide();
        
        // Show loading indicator
        $loadingIndicator.show();
        
        // Disable button during API call
        $(this).prop('disabled', true);
        
        // Make AJAX request
        $.ajax({
            url: deepseekTagGenerator.ajaxUrl,
            type: 'POST',
            data: {
                action: 'generate_deepseek_tags',
                nonce: deepseekTagGenerator.nonce,
                post_id: deepseekTagGenerator.postId
            },
            success: function(response) {
                // Hide loading indicator
                $loadingIndicator.hide();
                
                // Enable button
                $generateButton.prop('disabled', false);
                
                if (response.success) {
                    // Clear previous results
                    $tagsList.empty();
                    
                    var tags = response.data.tags;
                    var existingTags = response.data.existing_tags;
                    
                    // Display tags
                    $.each(tags, function(index, tag) {
                        var isExisting = $.inArray(tag, existingTags) !== -1;
                        var $tag = $('<span class="tag-item">' + tag + '</span>');
                        
                        if (isExisting) {
                            $tag.css('opacity', '0.6');
                            $tag.attr('title', 'Already added');
                        }
                        
                        $tag.on('click', function() {
                            // Add tag to WordPress tag input
                            addTagToWordPress(tag);
                            $(this).css('opacity', '0.6');
                            $(this).attr('title', 'Added');
                        });
                        
                        $tagsList.append($tag);
                    });
                    
                    // Add button to add all tags at once
                    var $addAllButton = $('<button type="button" class="button">' + deepseekTagGenerator.i18n.addTags + '</button>');
                    $addAllButton.on('click', function() {
                        $.each(tags, function(index, tag) {
                            addTagToWordPress(tag);
                        });
                        $('.tag-item').css('opacity', '0.6').attr('title', 'Added');
                    });
                    $tagsList.append($('<div style="margin-top: 10px;"></div>').append($addAllButton));
                    
                    // Show results
                    $resultContainer.show();
                } else {
                    // Show error
                    $errorContainer.html(deepseekTagGenerator.i18n.error + ' ' + response.data).show();
                    $resultContainer.show();
                }
            },
            error: function() {
                // Hide loading indicator
                $loadingIndicator.hide();
                
                // Enable button
                $generateButton.prop('disabled', false);
                
                // Show error
                $errorContainer.html(deepseekTagGenerator.i18n.error + ' ' + 'Failed to connect to server.').show();
                $resultContainer.show();
            }
        });
    });
    
    // Function to add a tag to WordPress tag input
    function addTagToWordPress(tag) {
        var $tagInput = $('#new-tag-post_tag');
        var currentTags = $tagInput.val();
        
        if (currentTags) {
            // Add comma if there are already tags
            $tagInput.val(currentTags + ', ' + tag);
        } else {
            // First tag
            $tagInput.val(tag);
        }
    }
});