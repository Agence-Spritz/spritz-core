<?php

/**
 * Helpers : Fonctions de formatage et utilitaires universels
 */

if (!defined('ABSPATH')) exit;

/**
 * Insère une valeur après une clé spécifique dans un tableau associatif
 */
if (!function_exists('array_insert_after')) {
    function array_insert_after(array $array, $key, array $new)
    {
        $keys = array_keys($array);
        $index = array_search($key, $keys);
        $pos = false === $index ? count($array) : $index + 1;
        return array_merge(array_slice($array, 0, $pos), $new, array_slice($array, $pos));
    }
}

/**
 * Log sécurisé pour le debug
 */
if (!function_exists('suptlog')) {
    function suptlog($content)
    {
        if (defined('WP_DEBUG') && true === WP_DEBUG) {
            if (is_array($content) || is_object($content)) {
                error_log(print_r($content, true));
            } else {
                error_log($content);
            }
        }
    }
}

/**
 * Convertit une chaîne en slug
 */
if (!function_exists('slugify')) {
    function slugify($text)
    {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        return strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $text));
    }
}

/**
 * Coupe une chaîne proprement au mot près
 */
if (!function_exists('cleanCut')) {
    function cleanCut($string, $length, $cutString = ' [..]')
    {
        if (strlen($string) <= $length) return $string;
        $str = substr($string, 0, $length - strlen($cutString) + 1);
        return substr($str, 0, strrpos($str, ' ')) . $cutString;
    }
}

/**
 * Convertit HEX en RGB
 */
if (!function_exists('hex2rgb')) {
    function hex2rgb($hex)
    {
        $hex = str_replace("#", "", $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return array($r, $g, $b);
    }
}

/**
 * Récupère une option avec cache statique
 */
if (!function_exists('spritz_get_cached_option')) {
    function spritz_get_cached_option($option_name, $default = '')
    {
        static $cache = [];
        if (!isset($cache[$option_name])) {
            $cache[$option_name] = get_option($option_name, $default);
        }
        return $cache[$option_name];
    }
}

/**
 * Récupère un mot clé par sa position (mots séparés par des virgules)
 */
if (!function_exists('get_mots_cles')) {
    function get_mots_cles($position)
    {
        $string = get_option('mots_cles');
        if ($string) {
            $str_arr = explode(",", $string);
            return isset($str_arr[$position]) ? trim($str_arr[$position]) : null;
        }
        return null;
    }
}

/**
 * Lien vers une page par URL compatible Polylang
 */
if (!function_exists('custom_get_page_link')) {
    function custom_get_page_link($nameorid)
    {
        if (!function_exists('pll_get_post')) return site_url($nameorid);

        $post_id = false;
        if (is_numeric($nameorid)) {
            $post_id = $nameorid;
        } else {
            $post = get_page_by_path($nameorid, OBJECT, array('post', 'page'));
            if ($post) $post_id = $post->ID;
        }

        if ($post_id) {
            $post_id_lang = pll_get_post($post_id);
            return get_permalink($post_id_lang ?: $post_id);
        }
        return site_url($nameorid);
    }
}

/**
 * Extrait automatique intelligent
 */
if (!function_exists('spritz_auto_excerpt')) {
    function spritz_auto_excerpt($post_id = null, $words = 28, $more = '…')
    {
        $post_id = $post_id ?: get_the_ID();
        if (!$post_id) return '';

        if (has_excerpt($post_id)) {
            return wp_trim_words(wp_strip_all_tags(get_the_excerpt($post_id)), $words, $more);
        }

        $cache_key = 'spritz_excerpt_' . $post_id . '_' . (int)$words;
        $cached = wp_cache_get($cache_key, 'spritz_excerpt');
        if ($cached !== false) return $cached;

        $raw = get_post_field('post_content', $post_id);
        $text = trim(wp_strip_all_tags(preg_replace('/\[[^\]]*\]/', '', $raw)));

        if (mb_strlen($text) < 20) {
            $rendered = apply_filters('the_content', $raw);
            $rendered = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#si', '', $rendered);
            if (preg_match('#<p[^>]*>(.*?)</p>#si', $rendered, $m) && !empty($m[1])) {
                $text = wp_strip_all_tags($m[1]);
            } else {
                $text = wp_strip_all_tags($rendered);
            }
            $text = trim(preg_replace('/\s+/', ' ', $text));
        }

        $excerpt = $text === '' ? '' : wp_trim_words($text, $words, $more);
        wp_cache_set($cache_key, $excerpt, 'spritz_excerpt');
        return $excerpt;
    }
}

/**
 * Récupère les sous-champs ACF d'un groupe
 */
if (!function_exists('get_acf_subfields_from_group')) {
    function get_acf_subfields_from_group($field_group)
    {
        if (!function_exists('acf_get_fields')) return [];
        $acf_fields = acf_get_fields($field_group);
        $array_of_field_values = [];
        if ($acf_fields) {
            foreach ($acf_fields as $acf_field) {
                $sub_fields = get_field($acf_field['name']);
                if ($sub_fields && is_array($sub_fields)) {
                    foreach ($sub_fields as $spec => $value) {
                        if (!empty($value)) {
                            $array_of_field_values[] = ['field_name' => $spec, 'value' => $value];
                        }
                    }
                }
            }
        }
        return $array_of_field_values;
    }
}

/**
 * Charge un template avec support de surcharge dans le thème
 */
if (!function_exists('spritz_get_template')) {
    function spritz_get_template($plugin_slug, $template_name, $args = [])
    {
        $theme_template = locate_template('spritz-plugins/' . $plugin_slug . '/' . $template_name);
        $file = $theme_template ?: WP_PLUGIN_DIR . '/' . $plugin_slug . '/templates/' . $template_name;

        if (file_exists($file)) {
            if (!empty($args)) extract($args);
            include($file);
        }
    }
}
