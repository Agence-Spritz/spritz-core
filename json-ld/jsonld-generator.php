<?php

/**
 * Générateur JSON-LD compatible Yoast, pour le socle Spritz.
 * Génère LocalBusiness, OfferCatalog (services), et memberOf (adhésions).
 * L'entreprise (LocalBusiness) renvoie à la société #organization : celle de Yoast quand Yoast la décrit
 * (Réglages > Représentation du site : société), sinon un nœud Organization produit ici (la référence ne
 * pointe jamais dans le vide).
 */

if (!defined('ABSPATH')) exit;

/**
 * Téléphone au format international pour schema.org : « +32 (0) 67 88 36 32 » → « +32 67 88 36 32 » ;
 * un numéro national (« 067 88 36 32 ») prend l'indicatif du pays (option « pays », code ISO).
 */
if (!function_exists('spritz_jsonld_telephone')) :
function spritz_jsonld_telephone(string $tel, string $pays = ''): string
{
    $tel = trim(preg_replace('/\s+/', ' ', $tel));
    if ($tel === '') return '';
    $tel = trim(preg_replace('/\s*\(\s*0\s*\)\s*/', ' ', $tel)); // « (0) » : ne se compose pas depuis l'étranger
    if (str_starts_with($tel, '00')) {
        $tel = '+' . substr($tel, 2);
    }
    if (!str_starts_with($tel, '+')) {
        $indicatifs = ['BE' => '32', 'FR' => '33', 'LU' => '352', 'CH' => '41', 'NL' => '31', 'DE' => '49'];
        $code = $indicatifs[strtoupper(trim($pays))] ?? '';
        if ($code && str_starts_with($tel, '0')) {
            $tel = '+' . $code . ' ' . ltrim(substr($tel, 1));
        }
    }
    return trim(preg_replace('/\s+/', ' ', $tel));
}
endif;

add_action('wp_head', function () {

    /* -------------------------------------------------------------
       DÉTECTION YOAST ET ID ORGANIZATION
    ------------------------------------------------------------- */
    $org_id = home_url('#organization');
    // même condition que Yoast pour produire sa société : « société », avec un nom et un logo
    $yoast_societe = defined('WPSEO_VERSION') && class_exists('WPSEO_Options')
        && WPSEO_Options::get('company_or_person') === 'company' && WPSEO_Options::get('company_name')
        && (int) WPSEO_Options::get('company_logo_id') > 0;

    // réseaux sociaux des réglages du site (sameAs)
    // seulement des adresses complètes (https://…) : une valeur bidon (« # », « facebook.com ») est ignorée
    $reseaux = array_values(array_filter(array_map(
        fn($cle) => esc_url_raw(trim((string) get_option($cle)), ['http', 'https']),
        ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'tiktok']
    ), fn($url) => (bool) preg_match('#^https?://[^/\s]+\.[^/\s]+#i', $url)));
    $telephone = spritz_jsonld_telephone((string) get_option('tel'), (string) get_option('pays'));

    /* -------------------------------------------------------------
       COORDONNÉES PRINCIPALES
    ------------------------------------------------------------- */
    $org_name = get_option('org_legal_name') ?: get_bloginfo('name');
    $adresse = array_filter([
        '@type'           => 'PostalAddress',
        'streetAddress'   => get_option('adresse'),
        'postalCode'      => get_option('cp'),
        'addressLocality' => get_option('ville'),
        'addressCountry'  => get_option('pays')
    ]);

    $geo = (get_option('geo_lat') && get_option('geo_lng')) ? [
        '@type' => 'GeoCoordinates',
        'latitude'  => (float)get_option('geo_lat'),
        'longitude' => (float)get_option('geo_lng')
    ] : null;

    // horaires (une ligne = un item)
    $opening = [];
    if ($txt = get_option('opening_hours')) {
        foreach (preg_split('/\r\n|\r|\n/', trim($txt)) as $line) {
            if ($line !== '') $opening[] = trim($line);
        }
    }

    /* -------------------------------------------------------------
       PARTENAIRES / ADHÉSIONS (memberOf)
    ------------------------------------------------------------- */
    $partners = get_option('org_partners') ?: [];
    $memberOf = [];
    foreach ($partners as $p) {
        if (!empty($p['name'])) {
            $memberOf[] = array_filter([
                '@type' => 'Organization',
                'name'  => $p['name'],
                'url'   => !empty($p['url']) ? esc_url($p['url']) : null
            ]);
        }
    }

    /* -------------------------------------------------------------
       SERVICES (OfferCatalog simplifié)
    ------------------------------------------------------------- */
    $services = get_option('org_services') ?: [];
    $service_nodes = [];
    if (!empty($services)) {
        $list = [];
        foreach ($services as $s) {
            if (!empty($s['description'])) $list[] = trim($s['description']);
        }
        if ($list) {
            $service_nodes[] = [
                '@type' => 'OfferCatalog',
                '@id' => home_url('#services'),
                'name' => 'Services proposés',
                'itemListElement' => array_map(function ($srv) {
                    return [
                        '@type' => 'Offer',
                        'itemOffered' => [
                            '@type' => 'Service',
                            'name' => $srv
                        ]
                    ];
                }, $list)
            ];
        }
    }

    /* -------------------------------------------------------------
   SAVOIR-FAIRE / EXPERTISES (knowsAbout)
    ------------------------------------------------------------- */
    $knows_about = get_option('org_knows_about') ?: [];
    $list_knows = [];

    if (!empty($knows_about)) {
        foreach ($knows_about as $item) {
            if (!empty($item['label'])) $list_knows[] = trim($item['label']);
        }
    }


    /* -------------------------------------------------------------
       LOCALISATIONS (mono ou multi)
    ------------------------------------------------------------- */
    $locations = get_option('org_locations') ?: [];
    $lbs = [];

    if (!empty($locations)) {
        $i = 1;
        foreach ($locations as $loc) {
            if (empty($loc['streetAddress']) && empty($loc['addressLocality'])) continue;

            $addr = array_filter([
                '@type'           => 'PostalAddress',
                'streetAddress'   => $loc['streetAddress'] ?? null,
                'postalCode'      => $loc['postalCode'] ?? null,
                'addressLocality' => $loc['addressLocality'] ?? null,
                'addressCountry'  => $loc['addressCountry'] ?? null
            ]);

            $geoLoc = (!empty($loc['lat']) && !empty($loc['lng'])) ? [
                '@type' => 'GeoCoordinates',
                'latitude'  => (float)$loc['lat'],
                'longitude' => (float)$loc['lng']
            ] : null;

            $open = [];
            if (!empty($loc['openingHours'])) {
                foreach (preg_split('/\r\n|\r|\n/', trim($loc['openingHours'])) as $line) {
                    if ($line !== '') $open[] = trim($line);
                }
            }

            $lbs[] = [
                '@type' => 'LocalBusiness',
                '@id' => home_url('#localbusiness-' . $i++),
                'name' => $org_name,
                'address' => $addr ?: null,
                'geo' => $geoLoc,
                'url' => home_url('/'),
                'telephone' => $telephone ?: null,
                'email'     => get_option('email') ?: null,
                'openingHours' => $open ?: null,
                'sameAs' => $reseaux ?: null,
                'hasOfferCatalog' => $service_nodes ? ['@id' => home_url('#services')] : null,
                'memberOf' => $memberOf ?: null,
                'parentOrganization' => ['@id' => $org_id],
                'knowsAbout' => $list_knows ?: null
            ];
        }
    } else {
        // fallback : une seule adresse principale
        $lbs[] = [
            '@type' => 'LocalBusiness',
            '@id' => home_url('#localbusiness'),
            'name' => $org_name,
            'address' => $adresse ?: null,
            'geo' => $geo,
            'url' => home_url('/'),
            'telephone' => $telephone ?: null,
            'email'     => get_option('email') ?: null,
            'openingHours' => $opening ?: null,
            'sameAs' => $reseaux ?: null,
            'hasOfferCatalog' => $service_nodes ? ['@id' => home_url('#services')] : null,
            'memberOf' => $memberOf ?: null,
            'parentOrganization' => ['@id' => $org_id],
            'knowsAbout' => $list_knows ?: null
        ];
    }

    /* -------------------------------------------------------------
       NETTOYAGE & SORTIE JSON-LD
    ------------------------------------------------------------- */
    // Société : sans Yoast pour la décrire, produite ici (même @id que celle de Yoast)
    $organisation = $yoast_societe ? [] : [[
        '@type'     => 'Organization',
        '@id'       => $org_id,
        'name'      => get_bloginfo('name'),
        'legalName' => get_option('org_legal_name') ?: null,
        'url'       => home_url('/'),
        'logo'      => get_option('logo') ? esc_url_raw(get_option('logo')) : null,
        'sameAs'    => $reseaux ?: null,
    ]];

    $graph = array_merge($organisation, $lbs, $service_nodes);

    /**
     * Filtre spritz_jsonld_graph : nœuds du graphe (Organization si Yoast ne décrit pas la société, LocalBusiness,
     * catalogue de services) avant nettoyage et sortie.
     * Sans fonction branchée, la sortie est inchangée. Ex. : un site dont la raison sociale (org_legal_name) diffère de
     * la marque peut mettre la marque en « name » et la raison sociale en « legalName ».
     */
    $graph = (array) apply_filters('spritz_jsonld_graph', $graph);

    $clean = function ($node) use (&$clean) {
        if (is_array($node)) {
            $out = [];
            foreach ($node as $k => $v) {
                if (is_array($v)) {
                    $v = $clean($v);
                    if ($v === []) continue;
                }
                if ($v === null || $v === '') continue;
                $out[$k] = $v;
            }
            return $out;
        }
        return $node;
    };

    $graph = array_map($clean, $graph);
    if (empty($graph)) return;

    echo '<script type="application/ld+json">' .
        wp_json_encode([
            '@context' => 'https://schema.org',
            '@graph'   => $graph
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) .
        '</script>';
}, 25); // priorité 25 = après Yoast
