<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
Plugin Name: Simple File Upload Limiter
Plugin URI:
Description: A plugin to set file upload limits for different file types.
Version: 1.0.0
Author: Alberto Reineri
Author URI: https://albertoreineri.it
License: GPL2
Text Domain: simple-file-upload-limiter
Domain Path: /languages
*/

/*
  Copyright 2024 Alberto Reineri (info@albertoreineri.it)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

// Load plugin textdomain for translations
function simple_file_upload_limiter_load_textdomain()
{
    load_plugin_textdomain('simple-file-upload-limiter', false, basename(dirname(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'simple_file_upload_limiter_load_textdomain');

// Add settings menu
function simple_file_upload_limiter_menu()
{
    add_options_page(
        __('File Upload Limiter', 'simple-file-upload-limiter'),
        __('File Upload Limiter', 'simple-file-upload-limiter'),
        'manage_options',
        'simple-file-upload-limiter',
        'simple_file_upload_limiter_options_page'
    );
}
add_action('admin_menu', 'simple_file_upload_limiter_menu');

// Display options page
function simple_file_upload_limiter_options_page()
{
?>
    <div class="wrap">
        <h1><?php esc_html_e('Set File Upload Limits', 'simple-file-upload-limiter'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('simple_file_upload_limiter_options');
            do_settings_sections('simple_file_upload_limiter');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

// Initialize settings
function simple_file_upload_limiter_settings_init()
{
    register_setting('simple_file_upload_limiter_options', 'simple_file_upload_limiter_limits');

    add_settings_section(
        'simple_file_upload_limiter_section',
        __('Upload Limit Configuration', 'simple-file-upload-limiter'),
        'simple_file_upload_limiter_section_cb',
        'simple_file_upload_limiter'
    );

    $file_types = array(
        'images' => __('Images', 'simple-file-upload-limiter'),
        'documents' => __('Documents', 'simple-file-upload-limiter'),
        'audio' => __('Audio', 'simple-file-upload-limiter'),
        'video' => __('Video', 'simple-file-upload-limiter'),
        'other' => __('Other', 'simple-file-upload-limiter')
    );

    foreach ($file_types as $type => $label) {
        add_settings_field(
            "simple_file_upload_limiter_{$type}_field",
            // Translators: %s is the file type (e.g., Images, Documents)
            sprintf(__('Upload limit for %s (in KB)', 'simple-file-upload-limiter'), $label),
            'simple_file_upload_limiter_field_cb',
            'simple_file_upload_limiter',
            'simple_file_upload_limiter_section',
            array('type' => $type)
        );
    }
}
add_action('admin_init', 'simple_file_upload_limiter_settings_init');

function simple_file_upload_limiter_section_cb()
{
    echo '<p>' . esc_html__('Set the maximum upload limit for different file types in Kilobytes (KB).', 'simple-file-upload-limiter') . '</p>';
}

function simple_file_upload_limiter_field_cb($args)
{
    $type = $args['type'];
    $limits = get_option('simple_file_upload_limiter_limits', array());
    $limit = isset($limits[$type]) ? $limits[$type] : 2048;
    echo '<input type="number" name="simple_file_upload_limiter_limits[' . esc_attr($type) . ']" value="' . esc_attr($limit) . '" min="1" />';
}

// Determine file type
function simple_file_upload_limiter_get_file_type($file)
{
    $file_info = wp_check_filetype($file['name']);
    $type = 'other';

    switch ($file_info['type']) {
        case 'image/jpeg':
        case 'image/png':
        case 'image/gif':
            $type = 'images';
            break;
        case 'application/pdf':
        case 'application/msword':
        case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
            $type = 'documents';
            break;
        case 'audio/mpeg':
        case 'audio/wav':
            $type = 'audio';
            break;
        case 'video/mp4':
        case 'video/mpeg':
            $type = 'video';
            break;
    }

    return $type;
}

// Enforce upload limit
function simple_file_upload_limiter_dynamic($file)
{
    $limits = get_option('simple_file_upload_limiter_limits', array());
    $file_type = simple_file_upload_limiter_get_file_type($file);
    $limit_kb = isset($limits[$file_type]) ? $limits[$file_type] : 2048;
    $limit = $limit_kb * 1024;

    if ($file['size'] > $limit) {
        // Translators: %s is the file type (e.g., Images, Documents), %d is the limit in KB
        $file['error'] = sprintf(__('The file is too large. The limit for %1$s is %2$d KB.', 'simple-file-upload-limiter'), $file_type, $limit_kb);
    }

    return $file;
}
add_filter('wp_handle_upload_prefilter', 'simple_file_upload_limiter_dynamic');

// Add settings link on plugin page
function simple_file_upload_limiter_plugin_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=simple-file-upload-limiter">' . __('Settings', 'simple-file-upload-limiter') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'simple_file_upload_limiter_plugin_settings_link');
