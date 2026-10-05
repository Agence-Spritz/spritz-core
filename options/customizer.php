<?php

/**
 * Réglages du site dans Apparence > Personnaliser (remplace l'ancienne page « Mes réglages »)
 *
 * Les réglages sont enregistrés comme options WordPress, sous les mêmes noms qu'avant
 * (get_option('tel'), get_option('logo')…) : aucune migration de données nécessaire.
 *
 * - Identité du site (section native) : logo, logo clair
 * - Panneau « Informations du site » : coordonnées, réseaux sociaux, visuels,
 *   référencement, données structurées (JSON-LD)
 *
 * Réglages propres à un projet : utiliser le hook customize_register dans le thème.
 */

if (!defined('ABSPATH')) exit;

/************************************************
 *  ACCÈS ÉDITEURS
 ************************************************/
if ($role = get_role('editor')) $role->add_cap('edit_theme_options');

// Icône des éléments WPBakery du thème
add_action('admin_enqueue_scripts', function () {
	wp_enqueue_style('spritz-admin-styles', plugin_dir_url(__FILE__) . 'admin-styles.css', [], SPRITZ_CORE_VERSION);
});

add_action('customize_register', function (WP_Customize_Manager $wp_customize) {
	require_once __DIR__ . '/class-spritz-customize-lines-setting.php';

	$add = function (string $id, string $section, string $label, string $type = 'text', array $args = []) use ($wp_customize) {
		$sanitize = [
			'url'      => 'esc_url_raw',
			'email'    => 'sanitize_email',
			'textarea' => 'sanitize_textarea_field',
			'image'    => 'esc_url_raw',
		][$type] ?? 'sanitize_text_field';

		$wp_customize->add_setting($id, [
			'type'              => 'option',
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => $sanitize,
		]);

		if ($type === 'image') {
			$wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, $id, [
				'label'       => $label,
				'section'     => $section,
				'description' => $args['description'] ?? '',
			]));
			return;
		}

		$wp_customize->add_control($id, array_merge([
			'label'   => $label,
			'section' => $section,
			'type'    => $type,
		], $args));
	};

	// --- Identité du site (section native) : logos ---
	$add('logo', 'title_tagline', 'Logo principal', 'image', ['description' => 'Affiché dans l\'en-tête et sur la page de connexion.']);
	$add('logo-light', 'title_tagline', 'Logo clair', 'image', ['description' => 'Version claire du logo, pour les fonds sombres (pied de page).']);

	// --- Panneau « Informations du site » ---
	$wp_customize->add_panel('spritz_site', [
		'title'    => 'Informations du site',
		'priority' => 25,
	]);

	$sections = [
		'spritz_coordonnees'  => 'Coordonnées',
		'spritz_reseaux'      => 'Réseaux sociaux',
		'spritz_visuels'      => 'Visuels par défaut',
		'spritz_referencement' => 'Référencement',
		'spritz_jsonld'       => 'Données structurées (JSON-LD)',
	];
	foreach ($sections as $id => $title) {
		$wp_customize->add_section($id, ['title' => $title, 'panel' => 'spritz_site']);
	}

	// Coordonnées
	$add('org_legal_name', 'spritz_coordonnees', 'Raison sociale');
	$add('org_siret', 'spritz_coordonnees', 'SIRET');
	$add('adresse', 'spritz_coordonnees', 'Adresse');
	$add('cp', 'spritz_coordonnees', 'Code postal');
	$add('ville', 'spritz_coordonnees', 'Ville');
	$add('pays', 'spritz_coordonnees', 'Pays');
	$add('tel', 'spritz_coordonnees', 'Téléphone');
	$add('email', 'spritz_coordonnees', 'Email', 'email');
	$add('geo_lat', 'spritz_coordonnees', 'Latitude');
	$add('geo_lng', 'spritz_coordonnees', 'Longitude');
	$add('opening_hours', 'spritz_coordonnees', 'Horaires d\'ouverture', 'textarea', [
		'description' => 'Une plage par ligne, format schema.org. Ex. : Mo-Fr 09:00-18:00',
	]);

	// Réseaux sociaux
	foreach (['facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'twitter' => 'X (Twitter)', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'tiktok' => 'TikTok'] as $id => $label) {
		$add($id, 'spritz_reseaux', $label, 'url');
	}

	// Visuels par défaut
	$add('fond-pages', 'spritz_visuels', 'Image de fond des en-têtes', 'image', ['description' => 'Utilisée quand une page n\'a pas d\'image d\'en-tête.']);
	$add('vignette', 'spritz_visuels', 'Vignette par défaut', 'image', ['description' => 'Utilisée pour les contenus sans image mise en avant.']);

	// Référencement
	$add('titre_general', 'spritz_referencement', 'Titre SEO général');
	$add('desc_general', 'spritz_referencement', 'Description générale', 'textarea');
	$add('mots_cles', 'spritz_referencement', 'Mots-clés', 'textarea', ['description' => 'Séparés par des virgules.']);
	$add('secteur_activite', 'spritz_referencement', 'Secteur d\'activité (lien Agence Spritz)', 'text', ['description' => 'Texte du lien de pied de page vers Agence Spritz (page d\'accueil).']);
	$add('secteur_activite_url', 'spritz_referencement', 'URL du lien secteur d\'activité', 'url');

	// Données structurées : listes « une ligne par élément »
	$lists = [
		'org_knows_about' => ['Savoir-faire / expertises', ['label'], [], 'Une expertise par ligne.'],
		'org_services'    => ['Services proposés', ['description'], [], 'Un service par ligne.'],
		'org_partners'    => ['Partenaires ou adhésions', ['name', 'url'], [], 'Une ligne par partenaire : Nom | https://site.fr'],
		'org_locations'   => [
			'Localisations (facultatif)',
			['streetAddress', 'postalCode', 'addressLocality', 'addressCountry', 'lat', 'lng', 'openingHours'],
			['openingHours'],
			'Une ligne par lieu : Adresse | CP | Ville | Pays | Latitude | Longitude | Horaires (séparés par « ; »)',
		],
	];
	foreach ($lists as $id => [$label, $columns, $multiline, $description]) {
		$wp_customize->add_setting(new Spritz_Customize_Lines_Setting($wp_customize, $id, [
			'capability'        => 'edit_theme_options',
			'columns'           => $columns,
			'multiline_columns' => $multiline,
		]));
		$wp_customize->add_control($id, [
			'label'       => $label,
			'section'     => 'spritz_jsonld',
			'type'        => 'textarea',
			'description' => $description,
		]);
	}
});
