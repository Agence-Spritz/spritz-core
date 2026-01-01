<?php

/**
 * Générateur JSON-LD compatible Yoast, pour le socle Spritz.
 * Génère LocalBusiness, OfferCatalog (services), et memberOf (adhésions).
 */

if (!defined('ABSPATH')) exit;

add_action('wp_head', function () {

    /* -------------------------------------------------------------
       DÉTECTION YOAST ET ID ORGANIZATION
    ------------------------------------------------------------- */
    if (defined('WPSEO_VERSION')) {
        $org_id = home_url('#organization'); // réutilise celui de Yoast
    } else {
        $org_id = home_url('#organization-spritz'); // fallback si Yoast absent
    }

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
                }, $list),
                'publisher' => ['@id' => home_url('#organization')]
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
                'telephone' => get_option('tel') ?: null,
                'email'     => get_option('email') ?: null,
                'openingHours' => $open ?: null,
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
            'telephone' => get_option('tel') ?: null,
            'email'     => get_option('email') ?: null,
            'openingHours' => $opening ?: null,
            'memberOf' => $memberOf ?: null,
            'parentOrganization' => ['@id' => $org_id],
            'knowsAbout' => $list_knows ?: null
        ];
    }

    /* -------------------------------------------------------------
       NETTOYAGE & SORTIE JSON-LD
    ------------------------------------------------------------- */
    $graph = array_merge($lbs, $service_nodes);

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
