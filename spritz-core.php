<?php
/*
Plugin Name: Spritz Core
Plugin URI: https://github.com/Agence-Spritz/spritz-core
Description: Plugin permettant la configuration générale du thème ainsi que la création des Custom Post types.
Version: 5.2.0
Author: Agence Spritz
Author URI: https://www.agence-spritz.com/
License: GPLv2
*/

if (!defined('ABSPATH')) {
    exit;
}

// Mises à jour automatiques depuis les releases GitHub (asset spritz-core.zip).
// Désactivé sur une copie de travail git (dev local, plugin en symlink) : une mise à jour
// lancée depuis l'admin écraserait les sources du dépôt.
if (!is_dir(__DIR__ . '/.git')) {
	require_once __DIR__ . '/lib/plugin-update-checker/plugin-update-checker.php';
	\YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/Agence-Spritz/spritz-core/',
		__FILE__,
		'spritz-core'
	)->getVcsApi()->enableReleaseAssets('/spritz\-core\.zip$/');
}

// Constantes globales
define('SPRITZ_PLUGIN_ABSPATH', dirname(__FILE__));
define('SPRITZ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPRITZ_CORE_VERSION', '5.2.0');

$theme = wp_get_theme();
define('SP_THEMENAME', $theme['Name']);

// --- INCLUDES ---

// Réglages du site dans Apparence > Personnaliser
require_once(SPRITZ_PLUGIN_ABSPATH . '/options/customizer.php');

// Utilitaires et Helpers (Formatage, Tableaux, Cache...)
require_once(SPRITZ_PLUGIN_ABSPATH . '/includes/helpers.php');

// Gestion des Médias (SVG, Dimensions, Thumbnails...)
require_once(SPRITZ_PLUGIN_ABSPATH . '/includes/media.php');

// Déclaration des CPT
require_once(SPRITZ_PLUGIN_ABSPATH . '/cpt/cpt.php');

// Création du schéma Json-LD
require_once(SPRITZ_PLUGIN_ABSPATH . '/json-ld/jsonld-generator.php');
