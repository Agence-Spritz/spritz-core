<?php
/*
Plugin Name: Spritz Core
Plugin URI: http://www.agence-spritz.com.com/
Description: Plugin permettant la configuration générale du thème ainsi que la création des Custom Post types.
Version: 5
Author: Agence Spritz
Author URI: http://www.agence-spritz.com/
License: GPLv2
*/

if (!defined('ABSPATH')) {
    exit;
}

// Constantes globales
define('SPRITZ_PLUGIN_ABSPATH', dirname(__FILE__));
define('SPRITZ_PLUGIN_URL', plugin_dir_url(__FILE__));

$theme = wp_get_theme();
define('SP_THEMENAME', $theme['Name']);

// Panel Mes Réglages
require_once(SPRITZ_PLUGIN_ABSPATH . '/options/options.php');

// Déclaration des CPT
require_once(SPRITZ_PLUGIN_ABSPATH . '/cpt/cpt.php');

// Création du schéma Json-LD
require_once(SPRITZ_PLUGIN_ABSPATH . '/json-ld/jsonld-generator.php');

