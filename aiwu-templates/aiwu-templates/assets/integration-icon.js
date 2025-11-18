jQuery(document).ready(function($) {
    var mediaUploader;

    // Upload icon button
    $('#upload_icon_button').on('click', function(e) {
        e.preventDefault();

        // If the uploader object has already been created, reopen it
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        // Create a new media uploader
        mediaUploader = wp.media({
            title: 'Choose Integration Icon',
            button: {
                text: 'Use this icon'
            },
            multiple: false,
            library: {
                type: ['image']
            }
        });

        // When an image is selected, run a callback
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#icon_image_id').val(attachment.id);
            $('#icon_preview').html('<img src="' + attachment.url + '" style="max-width:80px;max-height:80px;display:block;margin-bottom:10px;">');
            $('#upload_icon_button').text('Change Icon');
            $('#remove_icon_button').show();
        });

        // Open the uploader dialog
        mediaUploader.open();
    });

    // Remove icon button
    $('#remove_icon_button').on('click', function(e) {
        e.preventDefault();
        $('#icon_image_id').val('');
        $('#icon_preview').html('');
        $('#upload_icon_button').text('Upload Icon');
        $(this).hide();
    });
});
