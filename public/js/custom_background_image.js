(function($) {
  $('#upload_image_button').click(function() {
    // Open media library uploader
    var mediaUploader = wp.media({
      title: 'Select Background Image',
      button: {
        text: 'Choose Image'
      },
      multiple: false
    }).on('select', function() {
      var attachment = mediaUploader.state().get('selection').first().toJSON();
      $('#background_image_id').val(attachment.id);
      $('#preview_image').attr('src', attachment.url).show();
    }).open();
  });
})(jQuery);