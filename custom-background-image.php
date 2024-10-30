<?php
/*
Plugin Name:        Custom Background Image
Description:        Allows you to set a custom background image for wp-site-blocks class in block themes. In case your theme is not a block theme the custom background image is set to the body.
Contributors:       https://profiles.wordpress.org/anilsardemann3close/
Tags:               Background Image
Version:            1.0.6
Author:             Anil Sardemann
Requires at least:  5.2
Tested up to:       6.5
Requires PHP:       7.0
Author URI:         https://www.3close.de/
License:            GPLv2 or later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:        custom-background-image
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cbi_custom_background_image_menu() {
    add_menu_page(
        'Custom Background Image',
        'Background Image',
        'manage_options',
        'cbi_custom-background-image',
        'cbi_custom_background_image_page',
        'dashicons-admin-generic',
        30
    );

    add_submenu_page(
        'cbi_custom-background-image',
        'Reset Settings',
        'Reset Settings',
        'manage_options',
        'cbi_custom-background-image-reset',
        'cbi_custom_background_image_reset_page'
    );
}
add_action('admin_menu', 'cbi_custom_background_image_menu');

function cbi_is_block_theme() {

  $theme_url = get_theme_file_uri( 'theme.json' ); // Get the theme's URI (URL)

  // Use wp_remote_get to retrieve the theme.json data
  $response = wp_remote_get( $theme_url );

  if ( is_wp_error( $response ) ) {
      return false;
  }

  $body = wp_remote_retrieve_body( $response ); // Get the response body

  if ( empty( $body ) ) {
      return false;
  }

  $theme_json = json_decode( $body, true ); // Decode the JSON data

  if ( isset( $theme_json['$schema'] ) ) {
      return true; // Block theme detected
  }

  return false; // Not a block theme
}


function cbi_custom_background_image_reset_page() {

  // Check and verify nonce
  if (isset($_POST['reset'])) {
      if (!wp_verify_nonce( $_POST['_wpnonce'], 'cbi_custom_background_image_reset' )) {
          wp_die('Invalid nonce! Please try again.');
      }

      delete_option('cbi_custom_background_image');

      $notice = sprintf(esc_html__('Background image settings reset successfully.'));
      $notice_html = sprintf('<div class="notice notice-success">%s</div>', wpautop($notice));

      echo wp_kses_post($notice_html);

      // Enqueue script to clear CSS
      wp_enqueue_script('cbi_custom-background-reset', plugin_dir_url(__FILE__) . '/public/js/custom-background-reset.js', array(), '1.0', true);
  }

  ?>
  <div class="wrap">
      <h1>Reset Background Image Settings</h1>
      <form method="post">
          <p>Are you sure you want to reset the background image settings?</p>
          <?php wp_nonce_field('cbi_custom_background_image_reset'); ?>
          <?php submit_button('Reset Settings', 'primary', 'reset'); ?>
      </form>
  </div>
  <?php
}

  function cbi_custom_background_image_page() {
    // Media library selection setup
    wp_enqueue_media();

        // Check and verify nonce
        if (isset($_POST['submit'])) {
          if (!wp_verify_nonce( $_POST['_wpnonce'], 'cbi_custom_background_image' )) {
              wp_die('Invalid nonce! Please try again.');
          }
      }
  
    if (isset($_POST['submit'])) {
      // Check if user selected an image from the media library
      if (empty($_POST['background_image_id'])) {
        $notice = sprintf( esc_html__('Please select an image from the media library.') );
        $notice_html = sprintf( '<div class="notice notice-error">%s</div>', wpautop( $notice ) );
        echo wp_kses_post( $notice_html );
        return;
      }
  
      $attachment_id = intval($_POST['background_image_id']);
  
      // Additional security: Check mime type after upload
      $mime_type = get_post_mime_type($attachment_id);
      if (!in_array($mime_type, array('image/jpeg', 'image/png', 'image/avif'))) {
        $notice = sprintf( esc_html__( 'Invalid image file content. Please try again.') );
        $notice_html = sprintf( '<div class="notice notice-error">%s</div>', wpautop( $notice ) );
        echo wp_kses_post( $notice_html );
        return;
      }
  
      update_option('cbi_custom_background_image', $attachment_id);
  
      $notice = sprintf( esc_html__( 'Background image updated successfully.') );
      $notice_html = sprintf( '<div class="notice notice-success">%s</div>', wpautop( $notice ) );
      echo wp_kses_post( $notice_html );
    }
  
    $attachment_id = get_option('cbi_custom_background_image');
    $image_url = wp_get_attachment_image_src($attachment_id, 'full')[0] ?? '';

    wp_enqueue_script('cbi_custom_background_image', plugin_dir_url(__FILE__) . '/public/js/custom_background_image.js', array(), '1.0', true);
  
    ?>
    <div class="wrap">
        <h1>Custom Background Image</h1>
        <form method="post" enctype="multipart/form-data">
            <p>
                <button type="button" class="button" id="upload_image_button">Select Image from Media Library</button>
            </p>
            <input type="hidden" name="background_image_id" id="background_image_id" value="">
            <p><img src="<?php echo esc_url($image_url); ?>" id="preview_image" style="max-width: 300px; display: none;"></p>
            <?php wp_nonce_field('cbi_custom_background_image'); ?>
            <?php submit_button('Update Background', 'primary', 'submit'); ?>
        </form>
    </div>
    <?php
  }


wp_register_style( 'cbi_custom-background-body', plugin_dir_url(__FILE__) . '/public/css/custom-background-body.css', array(), '1.0' );
wp_register_style( 'cbi_custom-background-blocks', plugin_dir_url(__FILE__) . '/public/css/custom-background-blocks.css', array(), '1.0' );

function cbi_custom_background_image_css() {
    $attachment_id = get_option('cbi_custom_background_image');

    $image_url = wp_get_attachment_image_src($attachment_id, 'full')[0] ?? '';

    if (!cbi_is_block_theme()) {
        if (!empty($image_url)) {
            echo '<style>body { background-image: url(' . esc_url($image_url) . ') !important}</style>';
            wp_enqueue_style('cbi_custom-background-body', plugin_dir_url(__FILE__) . '/public/css/custom-background-body.css', array(), 1.0, true);
        }
    } else {
        if (!empty($image_url)) {
            echo '<style>.wp-site-blocks { background-image: url(' . esc_url($image_url) . ') !important}</style>';
            wp_enqueue_style('cbi_custom-background-blocks', plugin_dir_url(__FILE__) . '/public/css/custom-background-blocks.css', array(), 1.0, true);
        }
    }
}

add_action('wp_head', 'cbi_custom_background_image_css');
