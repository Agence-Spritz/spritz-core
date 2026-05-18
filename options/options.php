<?php
/*
Description: Fichier d’options générales consolidé, repeaters fiabilisés.
Version: 6.3
Author: Agence Spritz
*/

if (!defined('ABSPATH')) exit;

/************************************************
 *  ENREGISTREMENT DES OPTIONS
 ************************************************/

add_action('admin_init', 'spritz_register_settings');
function spritz_register_settings()
{

	/* informations générales */
	register_setting('spritz_options', 'titre_general');
	register_setting('spritz_options', 'desc_general');
	register_setting('spritz_options', 'mots_cles');
	register_setting('spritz_options', 'secteur_activite');
	register_setting('spritz_options', 'secteur_activite_url');

	/* identité et coordonnées */
	register_setting('spritz_options', 'org_legal_name'); // raison sociale
	register_setting('spritz_options', 'org_siret');
	register_setting('spritz_options', 'adresse');
	register_setting('spritz_options', 'cp');
	register_setting('spritz_options', 'ville');
	register_setting('spritz_options', 'pays');
	register_setting('spritz_options', 'tel');
	register_setting('spritz_options', 'email');
	register_setting('spritz_options', 'geo_lat');
	register_setting('spritz_options', 'geo_lng');
	register_setting('spritz_options', 'opening_hours');

	/* réseaux sociaux */
	foreach (['facebook', 'linkedin', 'twitter', 'instagram', 'youtube', 'tiktok'] as $field) {
		register_setting('spritz_options', $field);
	}

	/* visuels et médias */
	register_setting('spritz_options', 'fond-pages');
	register_setting('spritz_options', 'vignette');
	register_setting('spritz_options', 'logo');
	register_setting('spritz_options', 'logo-light');

	/* contenu sémantique JSON-LD */
	register_setting('spritz_options', 'org_knows_about', ['sanitize_callback' => 'spritz_sanitize_array']);
	register_setting('spritz_options', 'org_services', ['sanitize_callback' => 'spritz_sanitize_array']);
	register_setting('spritz_options', 'org_partners', ['sanitize_callback' => 'spritz_sanitize_array']);
	register_setting('spritz_options', 'org_locations', ['sanitize_callback' => 'spritz_sanitize_array']);

	/* hook pour options spécifiques au projet */
	do_action('spritz_extra_register_settings');
}

function spritz_sanitize_array($val)
{
	if (is_array($val)) {
		return array_values(array_filter($val, fn($row) => !empty(array_filter((array)$row))));
	}
	return [];
}

/************************************************
 *  ACCÈS ÉDITEURS
 ************************************************/
if ($role = get_role('editor')) $role->add_cap('edit_theme_options');
add_filter('option_page_capability_spritz_options', fn($cap) => 'edit_theme_options');

/************************************************
 *  MENU ADMIN
 ************************************************/
add_action('admin_menu', function () {
	add_menu_page('Mes options de configuration', 'Mes réglages', 'edit_theme_options', 'my-theme-page', 'spritz_options_page');
});

/************************************************
 *  PAGE D’OPTIONS
 ************************************************/
function spritz_options_page()
{ ?>
	<div class="wrap spritz_options">
		<h2>Mes options de configuration</h2>
		<form method="post" action="options.php">
			<?php settings_fields('spritz_options'); ?>

			<?php do_action('spritz_options_page_before_general'); ?>

			<!-- informations générales -->
			<h2>Informations générales</h2>
			<table class="form-table">
				<tr>
					<th> Titre général du site</th>
					<td><input type="text" name="titre_general" value="<?php echo esc_attr(get_option('titre_general')); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th>Description générale</th>
					<td><textarea name="desc_general" maxlength="300" class="large-text"><?php echo esc_textarea(get_option('desc_general')); ?></textarea></td>
				</tr>
				<tr>
					<th>Mots-clés</th>
					<td><textarea name="mots_cles" maxlength="500" class="large-text"><?php echo esc_textarea(get_option('mots_cles')); ?></textarea></td>
				</tr>
				<tr>
					<th>Métier / Secteur d'activité</th>
					<td>
						<input type="text" name="secteur_activite" value="<?php echo esc_attr(get_option('secteur_activite')); ?>" class="regular-text" placeholder="ex: Avocat, Restaurant, Plombier...">
						<p class="description">Utilisé pour personnaliser le lien SEO vers l'agence (ex: "Création de site pour Avocat")</p>
					</td>
				</tr>
				<tr>
					<th>URL catégorie portfolio (optionnel)</th>
					<td>
						<input type="url" name="secteur_activite_url" value="<?php echo esc_attr(get_option('secteur_activite_url')); ?>" class="regular-text" placeholder="https://www.agence-spritz.com/portfolio/avocat/">
						<p class="description">Si renseigné, le lien SEO pointera vers cette page portfolio. Sinon, vers la page d'accueil de l'agence.</p>
					</td>
				</tr>
			</table>

			<?php do_action('spritz_options_page_after_general'); ?>

			<hr>

			<!-- identité et coordonnées -->
			<h2>Identité et coordonnées</h2>
			<table class="form-table">
				<tr>
					<th>Raison sociale</th>
					<td><input type="text" name="org_legal_name" value="<?php echo esc_attr(get_option('org_legal_name')); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th>SIRET / ID</th>
					<td><input type="text" name="org_siret" value="<?php echo esc_attr(get_option('org_siret')); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th>Adresse</th>
					<td><input type="text" name="adresse" value="<?php echo esc_attr(get_option('adresse')); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th>Code postal</th>
					<td><input type="text" name="cp" value="<?php echo esc_attr(get_option('cp')); ?>" class="small-text"></td>
				</tr>
				<tr>
					<th>Ville</th>
					<td><input type="text" name="ville" value="<?php echo esc_attr(get_option('ville')); ?>" class="small-text"></td>
				</tr>
				<tr>
					<th>Pays</th>
					<td><input type="text" name="pays" value="<?php echo esc_attr(get_option('pays')); ?>" class="small-text"></td>
				</tr>
				<tr>
					<th>Téléphone principal</th>
					<td><input type="text" name="tel" value="<?php echo esc_attr(get_option('tel')); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th>Email de contact</th>
					<td><input type="email" name="email" value="<?php echo esc_attr(get_option('email')); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th>Latitude (facultatif)</th>
					<td><input type="text" name="geo_lat" value="<?php echo esc_attr(get_option('geo_lat')); ?>" class="small-text"></td>
				</tr>
				<tr>
					<th>Longitude (facultatif)</th>
					<td><input type="text" name="geo_lng" value="<?php echo esc_attr(get_option('geo_lng')); ?>" class="small-text"></td>
				</tr>
				<tr>
					<th>Horaires d’ouverture</th>
					<td>
						<textarea name="opening_hours" rows="3" placeholder="Mo-Fr 09:00-18:00&#10;Sa 10:00-13:00" class="large-text"><?php echo esc_textarea(get_option('opening_hours')); ?></textarea>
						<p class="description">Format : <code>Jour HH:MM-HH:MM</code> par ligne (ex : Mo-Fr 09:00-18:00)</p>
					</td>
				</tr>

			</table>

			<?php do_action('spritz_options_page_after_identity'); ?>

			<hr>

			<!-- réseaux sociaux -->
			<h2>Réseaux sociaux</h2>
			<table class="form-table">
				<?php foreach (['facebook', 'linkedin', 'twitter', 'instagram', 'youtube', 'tiktok'] as $field): ?>
					<tr>
						<th><?php echo ucfirst($field); ?></th>
						<td><input type="url" name="<?php echo $field; ?>" value="<?php echo esc_attr(get_option($field)); ?>" class="regular-text"></td>
					</tr>
				<?php endforeach; ?>
			</table>

			<?php do_action('spritz_options_page_after_social'); ?>

			<hr>

			<!-- visuels -->
			<h2>Visuels et médias</h2>
			<?php wp_enqueue_media(); ?>
			<div class="container-admin">
				<?php foreach (['fond-pages' => 'Image générique de fond', 'vignette' => 'Vignette par défaut', 'logo' => 'Logo principal', 'logo-light' => 'Logo light'] as $field => $label): ?>
					<div>
						<label for="<?php echo $field; ?>"><?php echo $label; ?></label><br>
						<img id="<?php echo $field; ?>-preview" src="<?php echo esc_url(get_option($field)); ?>" style="max-width:200px;height:auto;margin:10px 0;">
						<input type="text" id="<?php echo $field; ?>" name="<?php echo $field; ?>" value="<?php echo esc_attr(get_option($field)); ?>" class="regular-text">
						<input type="button" id="<?php echo $field; ?>-button" class="button" value="Choisir ou télécharger">
					</div>
				<?php endforeach; ?>
			</div>

			<?php do_action('spritz_options_page_after_visuals'); ?>

			<hr>

			<!-- contenu sémantique -->
			<h2>Informations pour le schéma JSON-LD</h2>

			<h3>Savoir-faire / expertises</h3>
			<?php spritz_repeater_simple('org_knows_about', ['label' => 'text'], 'Ajouter une compétence'); ?>

			<h3>Services proposés</h3>
			<?php spritz_repeater_simple('org_services', ['description' => 'text'], 'Ajouter un service'); ?>


			<h3>Partenaires ou adhésions</h3>
			<?php spritz_repeater_simple('org_partners', ['name' => 'text', 'url' => 'url'], 'Ajouter un partenaire'); ?>

			<h3>Localisations (facultatif)</h3>
			<?php spritz_repeater_simple('org_locations', [
				'streetAddress' => 'text',
				'postalCode' => 'text',
				'addressLocality' => 'text',
				'addressCountry' => 'text',
				'lat' => 'text',
				'lng' => 'text',
				'openingHours' => 'textarea'
			], 'Ajouter une localisation'); ?>

			<?php do_action('spritz_options_page_after_jsonld'); ?>

			<p class="submit"><input type="submit" class="button-primary" value="Mettre à jour"></p>
		</form>
	</div>

	<script>
		/* médias wp: factorisé */
		(function() {
			function bindMedia(buttonId, inputId, imgId) {
				const $btn = document.getElementById(buttonId);
				if (!$btn) return;
				$btn.addEventListener('click', function(e) {
					e.preventDefault();
					const backup = wp.media.editor.send.attachment;
					wp.media.editor.send.attachment = function(props, attachment) {
						const url = attachment.url;
						const input = document.getElementById(inputId);
						const img = document.getElementById(imgId);
						if (input) input.value = url;
						if (img) img.src = url;
						wp.media.editor.send.attachment = backup;
					};
					wp.media.editor.open();
				});
			}
			['fond-pages', 'vignette', 'logo', 'logo-light'].forEach(function(id) {
				bindMedia(id + '-button', id, id + '-preview');
			});
		})();
	</script>
<?php }

/************************************************
 *  STYLE ADMIN
 ************************************************/
function spritz_admin_styles()
{
	wp_enqueue_style('spritz_options_styles', plugin_dir_url(__FILE__) . 'options-styles.css');
}
add_action('admin_print_styles', 'spritz_admin_styles');

/************************************************
 *  RENDER REPEATER + JS GLOBAL UNIQUE
 ************************************************/
function spritz_repeater_simple($option_key, $fields, $btn_label)
{
	$rows = get_option($option_key);
	if (!is_array($rows)) $rows = [];
	$rows = array_values(array_filter($rows, fn($r) => !empty(array_filter((array)$r))));
	$name = esc_attr($option_key);
?>
	<style>
		.spritz-repeater {
			table-layout: fixed;
			width: 100%;
			margin-bottom: 1em;
		}

		.spritz-repeater th,
		.spritz-repeater td {
			vertical-align: top;
		}

		.spritz-repeater th.actions-col,
		.spritz-repeater td:last-child {
			width: 110px;
			white-space: nowrap;
			text-align: center;
		}

		.spritz-repeater input.regular-text,
		.spritz-repeater textarea.large-text {
			width: 100%;
			box-sizing: border-box;
		}
	</style>
	<table class="widefat striped spritz-repeater" data-key="<?php echo $name; ?>" data-fields='<?php echo wp_json_encode($fields, JSON_UNESCAPED_UNICODE); ?>'>
		<colgroup>
			<?php foreach ($fields as $_ => $__): ?>
				<col><?php endforeach; ?>
			<col style="width:110px">
		</colgroup>
		<thead>
			<tr>
				<?php foreach ($fields as $k => $type): ?>
					<th><?php echo esc_html(ucfirst($k)); ?></th>
				<?php endforeach; ?>
				<th class="actions-col">Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($rows as $i => $row): ?>
				<tr>
					<?php foreach ($fields as $k => $type): ?>
						<td>
							<?php if ($type === 'textarea'): ?>
								<textarea name="<?php echo $name; ?>[<?php echo $i; ?>][<?php echo esc_attr($k); ?>]" rows="2" class="large-text"><?php echo esc_textarea($row[$k] ?? ''); ?></textarea>
							<?php else: ?>
								<input type="<?php echo esc_attr($type); ?>" name="<?php echo $name; ?>[<?php echo $i; ?>][<?php echo esc_attr($k); ?>]" value="<?php echo esc_attr($row[$k] ?? ''); ?>" class="regular-text">
							<?php endif; ?>
						</td>
					<?php endforeach; ?>
					<td><button type="button" class="button remove-row">Supprimer</button></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p><button type="button" class="button add-row" data-key="<?php echo $name; ?>"><?php echo esc_html($btn_label); ?></button></p>
<?php
}

/* un seul écouteur pour tous les repeaters, chargé en pied d’admin */
add_action('admin_footer', function () {
	// ne sortir le script que sur notre page
	if (empty($_GET['page']) || $_GET['page'] !== 'my-theme-page') return; ?>
	<script>
		(function() {
			if (window.__spritzRepeaterInit) return;
			window.__spritzRepeaterInit = true;

			document.addEventListener('click', function(e) {
				const addBtn = e.target.closest('.add-row');
				if (addBtn) {
					e.preventDefault();
					const key = addBtn.getAttribute('data-key');
					const form = addBtn.closest('form');
					const table = form.querySelector('.spritz-repeater[data-key="' + key + '"]');
					if (!table) return;

					const tbody = table.querySelector('tbody');
					const fields = JSON.parse(table.dataset.fields || '{}');
					const idx = tbody.querySelectorAll('tr').length;

					let tds = '';
					Object.keys(fields).forEach(function(k) {
						const type = fields[k];
						if (type === 'textarea') {
							tds += `<td><textarea name="${key}[${idx}][${k}]" rows="2" class="large-text"></textarea></td>`;
						} else {
							tds += `<td><input type="${type}" name="${key}[${idx}][${k}]" class="regular-text"></td>`;
						}
					});

					const tr = document.createElement('tr');
					tr.innerHTML = tds + '<td><button type="button" class="button remove-row">Supprimer</button></td>';
					tbody.appendChild(tr);
				}

				const delBtn = e.target.closest('.remove-row');
				if (delBtn) {
					e.preventDefault();
					const tr = delBtn.closest('tr');
					if (tr) tr.remove();
				}
			});
		})();
	</script>
<?php });
