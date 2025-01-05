<?php

// Add Admin Menu
function gemini_auto_blogger_menu() {
    add_menu_page(
        'Gemini Auto Blogger',
        'Gemini Auto Blogger',
        'manage_options',
        'gemini-auto-blogger',
        'gemini_auto_blogger_page',
        'dashicons-admin-tools',
        100
    );
}
add_action('admin_menu', 'gemini_auto_blogger_menu');

// Admin Page Content
function gemini_auto_blogger_page() {
    ?>
    <h1>Gemini Auto Blogger Settings</h1>
    <form method="post" action="options.php">
        <?php
        settings_fields('gemini_auto_blogger_settings');
        do_settings_sections('gemini-auto-blogger');
        submit_button('Save API Keys');
        ?>
    </form>

    <h2>Generate Blog Post</h2>
    <form method="post">
        <label for="post_topic">Post Topic:</label><br>
        <input type="text" name="post_topic" id="post_topic" required style="width: 400px;"><br><br>
        <input type="submit" name="generate_post" value="Generate Post" class="button button-primary">
    </form>
    <?php
    if (isset($_POST['generate_post'])) {
        $topic = sanitize_text_field($_POST['post_topic']);
        gemini_generate_blog_post($topic);
    }
}

// Register Settings
function gemini_auto_blogger_settings_init() {
    register_setting('gemini_auto_blogger_settings', GEMINI_API_KEY_OPTION);
    register_setting('gemini_auto_blogger_settings', UNSPLASH_API_KEY_OPTION);

    add_settings_section(
        'gemini_auto_blogger_section',
        'API Settings',
        'gemini_auto_blogger_section_callback',
        'gemini-auto-blogger'
    );

    add_settings_field(
        'gemini_api_key',
        'Google Gemini API Key',
        'gemini_api_key_callback',
        'gemini-auto-blogger',
        'gemini_auto_blogger_section'
    );

    add_settings_field(
        'unsplash_api_key',
        'Unsplash API Key',
        'unsplash_api_key_callback',
        'gemini-auto-blogger',
        'gemini_auto_blogger_section'
    );
}
add_action('admin_init', 'gemini_auto_blogger_settings_init');

// Section Callback
function gemini_auto_blogger_section_callback() {
    echo '<p>Enter your API keys below to enable the plugin functionality.</p>';
}

// API Key Callbacks
function gemini_api_key_callback() {
    $api_key = get_option(GEMINI_API_KEY_OPTION, '');
    echo '<input type="text" name="' . GEMINI_API_KEY_OPTION . '" value="' . esc_attr($api_key) . '" style="width: 400px;">';
}

function unsplash_api_key_callback() {
    $api_key = get_option(UNSPLASH_API_KEY_OPTION, '');
    echo '<input type="text" name="' . UNSPLASH_API_KEY_OPTION . '" value="' . esc_attr($api_key) . '" style="width: 400px;">';
}
