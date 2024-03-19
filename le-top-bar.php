<?php
/*
Plugin Name: Le Top Bar
Description: Adds a simple text bar at the top of the site.
Version: 1.0
Author: Daniel Paz
*/

function ltb_enqueue_styles()
{
    wp_enqueue_style('le-top-bar-css', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'ltb_enqueue_styles');

function ltb_enqueue_styles_admin($hook) {
    if ('tools_page_le-top-bar-settings' !== $hook) {
        return;
    }
    wp_enqueue_style('le-top-bar-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
}

add_action('admin_enqueue_scripts', 'ltb_enqueue_styles_admin');


function ltb_enqueue_color_picker($hook_suffix)
{
    if ('tools_page_le-top-bar-settings' !== $hook_suffix) {
        return;
    }

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');

    wp_add_inline_script('wp-color-picker', '
        jQuery(document).ready(function($){
            $(".ltb-color-picker").wpColorPicker();
        });
    ');
}

add_action('admin_enqueue_scripts', 'ltb_enqueue_color_picker');



function ltb_register_admin_page()
{
    add_submenu_page(
        'tools.php', // Parent slug
        'Le Top Bar Settings', // Page title
        'Top Bar Settings', // Menu title
        'manage_options', // Capability
        'le-top-bar-settings', // Menu slug
        'ltb_admin_page_content' // Function to display the page content
    );
}
add_action('admin_menu', 'ltb_register_admin_page');

function ltb_admin_page_content()
{
    $icon_path = plugin_dir_path(__FILE__) . 'icons/';
    $icon_url = plugin_dir_url(__FILE__) . 'icons/';

    $icons = glob($icon_path . '*.svg');

    ?>
    <div class="wrap">
        <h2>Top Bar Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('le-top-bar-settings');
            do_settings_sections('le-top-bar-settings');
            submit_button();
            ?>
        </form>
        
        <!-- Sección de iconos -->
        <h3>Available Icons</h3>
        <p>Insert one of the following codes to use an icon:</p>
        <div class="icon-list">
            <?php foreach ($icons as $icon) : ?>
                <?php $icon_name = basename($icon);  ?>
                <div class="icon-item">
                    <img src="<?php echo esc_url($icon_url . $icon_name); ?>" alt="<?php echo esc_attr($icon_name); ?>" />
                    <p><code>{icon:<?php echo esc_html($icon_name); ?>}</code></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}


function ltb_register_settings()
{
    register_setting('le-top-bar-settings', 'ltb_settings');

    add_settings_section(
        'ltb_settings_section',
        'Customize Top Bar',
        'ltb_settings_section_callback',
        'le-top-bar-settings'
    );

    add_settings_field(
        'ltb_background_color',
        'Background Color',
        'ltb_background_color_callback',
        'le-top-bar-settings',
        'ltb_settings_section'
    );

    add_settings_field(
        'ltb_text_color',
        'Text Color',
        'ltb_text_color_callback',
        'le-top-bar-settings',
        'ltb_settings_section'
    );

    add_settings_field(
        'ltb_text',
        'Text',
        'ltb_text_callback',
        'le-top-bar-settings',
        'ltb_settings_section'
    );
}
add_action('admin_init', 'ltb_register_settings');

function ltb_settings_section_callback()
{
    echo '<p>Customize the appearance of the top bar.</p>';
}

function ltb_background_color_callback()
{
    $options = get_option('ltb_settings');
    echo '<input type="text" id="ltb_background_color" name="ltb_settings[background_color]" class="ltb-color-picker" value="' . esc_attr($options['background_color'] ?? '') . '"/>';
}

function ltb_text_color_callback()
{
    $options = get_option('ltb_settings');
    echo '<input type="text" id="ltb_text_color" name="ltb_settings[text_color]" class="ltb-color-picker" value="' . esc_attr($options['text_color'] ?? '') . '"/>';
}

function ltb_text_callback()
{
    $options = get_option('ltb_settings');
    echo '<textarea id="ltb_text" name="ltb_settings[text]" rows="5" cols="50">' . esc_textarea($options['text'] ?? '') . '</textarea>';
}


function ltb_add_top_bar()
{
    $options = get_option('ltb_settings');
    $background_color = $options['background_color'] ?? '#333';
    $text_color = $options['text_color'] ?? '#ffffff';
    $text = $options['text'] ?? 'This is a simple top bar!';

    $text = esc_html($text);

    $icon_url = plugin_dir_url(__FILE__) . 'icons/';
    $pattern = '/\{icon:(.*?)\}/i';
    $text = preg_replace_callback($pattern, function($matches) use ($icon_url) {
        $icon_name = $matches[1];
        $icon_path = $icon_url . $icon_name;
        if(file_exists(plugin_dir_path(__FILE__) . 'icons/' . $icon_name)) {
            return '<img src="' . esc_url($icon_path) . '" alt="' . esc_attr($icon_name) . '" style="vertical-align: middle;">';
        } else {
            return $matches[0];
        }
    }, $text);

    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var topBarText = document.querySelector('#le-top-bar div');
            if (topBarText) {
                var speed = 250; // Pixels por segundo

                var textWidth = topBarText.offsetWidth / 2; 

                var containerWidth = topBarText.parentElement.offsetWidth;
                var totalWidth = textWidth + containerWidth;
                var duration = totalWidth / speed;

                topBarText.style.animationDuration = duration + 's';
            }
        });

    </script>
    <?php

    $duplicatedText = $text . ' &nbsp;&nbsp;&nbsp; ' . $text;

    echo '<div id="le-top-bar" style="background-color: ' . esc_attr($background_color) . ';"><div>' . $duplicatedText . '</div></div>';
}

register_setting('le-top-bar-settings', 'ltb_settings', 'ltb_sanitize_settings');

add_action('wp_body_open', 'ltb_add_top_bar');


function ltb_sanitize_settings($input)
{
    $new_input = array();
    if (isset ($input['background_color']))
        $new_input['background_color'] = sanitize_hex_color($input['background_color']);
    if (isset ($input['text_color']))
        $new_input['text_color'] = sanitize_hex_color($input['text_color']);
    if (isset ($input['text'])) {
        $new_input['text'] = sanitize_textarea_field($input['text']);
    }

    return $new_input;
}
