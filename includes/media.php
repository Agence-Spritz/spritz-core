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

        // 1. Tentative via la bibliothèque de médias
        $attachment_id = attachment_url_to_postid($url);
        if ($attachment_id) {
            $image_src = wp_get_attachment_image_src($attachment_id, 'full');
            if ($image_src && !empty($image_src[1]) && !empty($image_src[2])) {
                return sprintf('width="%d" height="%d"', $image_src[1], $image_src[2]);
            }
        }

        // 2. Fallback manuel pour les fichiers locaux ou SVGs (ex: assets du thème)
        $path = str_replace(home_url('/'), ABSPATH, $url);

        // Si home_url n'était pas dans l'URL (cas des assets relatifs)
        if (!file_exists($path) && strpos($url, get_template_directory_uri()) !== false) {
            $path = str_replace(get_template_directory_uri(), get_template_directory(), $url);
        }

        if (file_exists($path)) {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if ($extension === 'svg') {
                // Lecture simple du SVG (regex pour éviter les soucis de namespace XML)
                $svg_content = @file_get_contents($path);
                if ($svg_content) {
                    // Try to find width/height
                    if (preg_match('/<svg[^>]*\bwidth=["\']([^"\']+)["\'][^>]*\bheight=["\']([^"\']+)["\']/', $svg_content, $matches)) {
                        return sprintf('width="%d" height="%d"', (int)$matches[1], (int)$matches[2]);
                    }
                    // Try to find viewBox
                    if (preg_match('/<svg[^>]*\bviewBox=["\']([^"\']+)["\']/', $svg_content, $matches)) {
                        $viewBox = explode(' ', $matches[1]);
                        if (count($viewBox) === 4) {
                            return sprintf('width="%d" height="%d"', (int)$viewBox[2], (int)$viewBox[3]);
                        }
                    }
                }
            } else {
                $size = @getimagesize($path);
                if ($size) {
                    return sprintf('width="%d" height="%d"', $size[0], $size[1]);
                }
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
 * Récupère l'URL d'une image à partir d'un ID de post ou d'un ID d'attachement (ACF)
 */
if (!function_exists('spritz_get_thumbnail_url')) {
    function spritz_get_thumbnail_url($id = null, $size = 'vignette', $default = null)
    {
        $id = $id ?: get_the_ID();
        if (!$id) return $default ?: get_option('vignette');

        // Cas 1 : L'ID est un attachement (ex: champ ACF image renvoyant l'ID)
        if (wp_attachment_is_image($id)) {
            $img = wp_get_attachment_image_src($id, $size);
            if ($img) return $img[0];
        }

        // Cas 2 : L'ID est un post, on cherche sa mise à la une
        if (has_post_thumbnail($id)) {
            $thumb = wp_get_attachment_image_src(get_post_thumbnail_id($id), $size);
            if ($thumb) return $thumb[0];
        }

        return $default ?: get_option('vignette');
    }
}
