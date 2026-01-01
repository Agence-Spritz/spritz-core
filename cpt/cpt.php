<?php /*
Description: Fichier permettant la création des Custom Post Types.
Version: 1.0
Author: Agence Spritz
Author URI: http://www.agence-spritz.com/
License: GPLv2
*/

if (! defined('ABSPATH')) exit; // Exit if accessed directly

/**
 *	Produits
 *  Activation dans le thème : add_theme_support('spritz-cpt-produits');
 */
function cpt_produits()
{
    // On n'enregistre le CPT que si le thème le demande explicitement
    if (!current_theme_supports('spritz-cpt-produits')) {
        return;
    }

    register_post_type(
        'produits',
        array(
            'labels' => array(
                'name' => 'Produits',
                'singular_name' => 'Produit',
                'menu_name' => 'Produits',
                'add_new' => 'Ajouter produit',
                'add_new_item' => 'Ajouter produit',
                'edit' => 'Editer',
                'edit_item' => 'Editer produit',
                'new_item' => 'Nouveau produit',
                'view' => 'Voir',
                'view_item' => 'Voir produit',
                'search_items' => 'Rechercher produit',
                'not_found' => 'Aucun produit trouvé',
                'not_found_in_trash' => 'Aucun produit trouvé'
            ),

            'public' => true,
            'capability_type' => 'post',
            'menu_position' => 3,
            'supports' => array('title', 'thumbnail', 'editor', 'excerpt'),
            'menu_icon' => 'dashicons-cart',
            'has_archive' => true,
            'rewrite' => array('slug' => 'produits', 'with_front' => true),
            'show_in_rest' => true
        )
    );

    register_taxonomy(
        'categories-produits',   // le nom de la taxonomie
        'produits',   // l’élément auquel il s’applique
        array(
            'label' => 'Catégories de produits',
            'show_admin_column' => true,
            'labels' => array(
                'name' => 'Catégories de produits',
                'singular_name' => 'Catégorie de produit',
                'all_items' => 'Toutes les catégories de produits',
                'edit_item' => 'Éditer la gamme',
                'view_item' => 'Voir la gamme',
                'update_item' => 'Mettre à jour la gamme',
                'add_new_item' => 'Ajouter une gamme',
                'new_item_name' => 'Nouvelle gamme',
                'search_items' => 'Rechercher parmi les gammes',
                'popular_items' => 'Gammes les plus utilisées'
            ),
            'hierarchical' => true,
            'public'  => true,
            'show_ui'           => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'gamme', 'with_front' => true),
            'show_in_rest' => false,
        )
    );
    register_taxonomy_for_object_type('categories-produits', 'produits');
}

add_action('init', 'cpt_produits');
