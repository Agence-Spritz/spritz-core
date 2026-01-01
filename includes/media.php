<?php

/**
 * Media Helpers : SVG, Dimensions et Images
 */

if (!defined('ABSPATH')) exit;

/**
 * Récupère les attributs width et height d'une image à partir de son URL
 */
if (!function_exists('spritz_get_img_dimensions')) {
    function spritz_get_img_dimensions($url)
    {
        if (empty($url)) return '';
        $attachment_id = attachment_url_to_postid($url);
        if ($attachment_id) {
            $image_src = wp_get_attachment_image_src($attachment_id, 'full');
            if ($image_src && !empty($image_src[1]) && !empty($image_src[2])) {
                return sprintf('width="%d" height="%d"', $image_src[1], $image_src[2]);
            }
        }
        return '';
    }
}

/**
 * Activation du support des fichiers SVG
 */
add_filter('upload_mimes', function ($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
});

/**
 * Correction de l'affichage des miniatures SVG dans le back-office
 */
add_action('admin_head', function () {
    echo '<style>
        .attachment-266x266, .thumbnail img[src$=".svg"], .acf-image-uploader img[src$=".svg"] { 
            width: 100% !important; 
            height: auto !important; 
        }
    </style>';
});

/**
 * Récupère l'URL du thumbnail d'un post avec fallback
 */
if (!function_exists('spritz_get_thumbnail_url')) {
    function spritz_get_thumbnail_url($post_id = null, $size = 'vignette', $default = null)
    {
        $post_id = $post_id ?: get_the_ID();
        if (has_post_thumbnail($post_id)) {
            $thumb = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), $size);
            if ($thumb) return $thumb[0];
        }
        return $default ?: get_option('vignette');
    }
}
