<?php

/**
 * Generate a blog post using Google Gemini API and Unsplash API for images.
 */
function gemini_generate_blog_post($topic) {
    $google_api_key = get_option(GEMINI_API_KEY_OPTION);
    $unsplash_api_key = get_option(UNSPLASH_API_KEY_OPTION);

    if (empty($google_api_key)) {
        echo '<p style="color: red;">Error: Google API Key is not set. Please configure it in the plugin settings.</p>';
        return;
    }

    if (empty($unsplash_api_key)) {
        echo '<p style="color: red;">Error: Unsplash API Key is not set. Please configure it in the plugin settings.</p>';
        return;
    }

    // 🎯 Generate Blog Content using Google Gemini API
    $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $google_api_key;

    $payload = json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => "Write a detailed blog post about: $topic"]
                ]
            ]
        ]
    ]);

    $response = wp_remote_post($api_url, [
        'headers' => [
            'Content-Type' => 'application/json'
        ],
        'body'    => $payload,
        'method'  => 'POST',
        'timeout' => 30
    ]);

    if (is_wp_error($response)) {
        echo '<p style="color: red;">Google API Error: ' . $response->get_error_message() . '</p>';
        return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $post_content = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

    if (empty($post_content)) {
        echo '<p style="color: red;">❌ Failed to generate content from Google Gemini API.</p>';
        return;
    }

    // 🖼️ Fetch an Image from Unsplash
    $image_id = gemini_fetch_image_from_unsplash($unsplash_api_key, $topic);
    $post_content_with_image = $image_id ? wp_get_attachment_image($image_id, 'full') . $post_content : $post_content;

    // 📤 Publish the Blog Post
    $post_data = [
        'post_title'   => sanitize_text_field($topic),
        'post_content' => wp_kses_post($post_content_with_image),
        'post_status'  => 'publish',
        'post_author'  => get_current_user_id()
    ];

    $post_id = wp_insert_post($post_data);

    if (!is_wp_error($post_id)) {
        echo '<p style="color: green;">✅ Post published successfully!</p>';
    } else {
        echo '<p style="color: red;">❌ Failed to create post.</p>';
    }
}

/**
 * Fetch an image from Unsplash API and add it to the Media Library.
 */
function gemini_fetch_image_from_unsplash($api_key, $query) {
    $unsplash_api_url = 'https://api.unsplash.com/search/photos?query=' . urlencode($query) . '&per_page=1&client_id=' . $api_key;

    $response = wp_remote_get($unsplash_api_url, ['timeout' => 30]);

    if (is_wp_error($response)) {
        echo '<p style="color: red;">Unsplash API Error: ' . $response->get_error_message() . '</p>';
        return null;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($body['results'][0]['urls']['regular'])) {
        $image_url = $body['results'][0]['urls']['regular'];

        // Validate file extension
        $parsed_url = parse_url($image_url);
        $path_info = pathinfo($parsed_url['path'] ?? '');
        $file_extension = strtolower($path_info['extension'] ?? '');

        if (!$file_extension) {
            $file_extension = 'jpg';
        }

        $valid_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($file_extension, $valid_extensions)) {
            echo '<p style="color: red;">Invalid image type (' . $file_extension . ') fetched from Unsplash.</p>';
            return null;
        }

        // Download the image
        $tmp_file = download_url($image_url);

        if (is_wp_error($tmp_file)) {
            echo '<p style="color: red;">Failed to download image from Unsplash: ' . $tmp_file->get_error_message() . '</p>';
            return null;
        }

        // Prepare file array
        $file_array = [
            'name'     => uniqid('unsplash_', true) . '.' . $file_extension,
            'tmp_name' => $tmp_file
        ];

        // Validate MIME type
        $wp_filetype = wp_check_filetype($file_array['name'], null);
        if (!$wp_filetype['ext']) {
            @unlink($tmp_file);
            echo '<p style="color: red;">Invalid file type detected. Upload failed.</p>';
            return null;
        }

        // Upload to Media Library
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_sideload($file_array, 0);

        if (is_wp_error($attachment_id)) {
            @unlink($tmp_file);
            echo '<p style="color: red;">Failed to add image to Media Library: ' . $attachment_id->get_error_message() . '</p>';
            return null;
        }

        return $attachment_id; // Return the attachment ID
    }

    echo '<p style="color: red;">No images found for this topic on Unsplash.</p>';
    return null;
}
