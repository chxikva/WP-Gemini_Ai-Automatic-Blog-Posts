<?php
/*
Plugin Name: Gemini Auto Blogger
Description: Automatically generates and publishes blog posts using Google Gemini API and fetches relevant images using Unsplash API.
Version: 1.0
Author: Kanaleto
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define Plugin Constants
define('GEMINI_AUTO_BLOGGER_PATH', plugin_dir_path(__FILE__));
define('GEMINI_API_KEY_OPTION', 'gemini_api_key');
define('UNSPLASH_API_KEY_OPTION', 'unsplash_api_key');

// Include Required Files
require_once GEMINI_AUTO_BLOGGER_PATH . 'includes/admin-settings.php';
require_once GEMINI_AUTO_BLOGGER_PATH . 'includes/gemini-functions.php';

// Plugin Activation
function gemini_auto_blogger_activate() {
    add_option(GEMINI_API_KEY_OPTION, '');
    add_option(UNSPLASH_API_KEY_OPTION, '');
}
register_activation_hook(__FILE__, 'gemini_auto_blogger_activate');

// Plugin Deactivation
function gemini_auto_blogger_deactivate() {
    delete_option(GEMINI_API_KEY_OPTION);
    delete_option(UNSPLASH_API_KEY_OPTION);
}
register_deactivation_hook(__FILE__, 'gemini_auto_blogger_deactivate');
