<?php
/**
 * Plugin Name: JBEE WP Add GraphQL Fields
 * Description: Ajoute différents champs au schéma GraphQL
 * Version: 1.0.0
 * Author: Julien Bruneel
 */

if (!defined('ABSPATH')) {
  exit; // direct access
}

/**
 * Register all fields when WPGraphQL is available.
 */
function jbee_register_fields()
{
  // bail early if WPGraphQL functions/classes are not available
  if (!function_exists('register_graphql_field')) {
    error_log('[WPGraphQL Site Media] register_graphql_field() non disponible — WPGraphQL probablement non activé');
    return;
  }

  // Ensure the GraphQL Post model exists
  if (!class_exists('\\WPGraphQL\\Model\\Post')) {
    error_log('[WPGraphQL Site Media] WPGraphQL\\Model\\Post non disponible — compatibilité requise');
    // we'll still register fields but resolvers will return null
  }

  // siteLogo
  register_graphql_field('RootQuery', 'siteLogo', [
    'type' => 'MediaItem',
    'description' => 'Logo du site en tant que MediaItem',
    'resolve' => function () {
      $logo_id = get_theme_mod('custom_logo');

      if (!$logo_id) {
        error_log('[WPGraphQL Site Media] Aucun logo défini');
        return null;
      }

      $post = get_post($logo_id);

      if (!$post || $post->post_type !== 'attachment') {
        error_log("[WPGraphQL Site Media] L'ID $logo_id n'est pas un attachment");
        return null;
      }

      if (class_exists('\\WPGraphQL\\Model\\Post')) {
        return new \WPGraphQL\Model\Post($post);
      }

      return null;
    },
  ]);

  // siteHeaderImage
  register_graphql_field('RootQuery', 'siteHeaderImage', [
    'type' => 'MediaItem',
    'description' => 'Image d\'en-tête du site en tant que MediaItem',
    'resolve' => function () {
      if (!function_exists('get_custom_header')) {
        error_log('[WPGraphQL Site Media] get_custom_header() non disponible');
        return null;
      }

      $header = get_custom_header();
      if (!$header || empty($header->attachment_id)) {
        error_log('[WPGraphQL Site Media] Aucun header image défini');
        return null;
      }

      $post = get_post($header->attachment_id);
      if (!$post || $post->post_type !== 'attachment') {
        error_log('[WPGraphQL Site Media] ID header invalide ou non attachment');
        return null;
      }

      if (class_exists('\\WPGraphQL\\Model\\Post')) {
        return new \WPGraphQL\Model\Post($post);
      }

      return null;
    },
  ]);

  // siteFavicon 
  $favicon_resolver = function () {
    $icon_id = get_option('site_icon');

    if (!$icon_id) {
      error_log('[WPGraphQL Site Media] Aucun icone (custom_icon) défini');
      return null;
    }

    $post = get_post($icon_id);

    if (!$post || $post->post_type !== 'attachment') {
      error_log("[WPGraphQL Site Media] L'ID $icon_id n'est pas un attachment");
      return null;
    }

    if (class_exists('\\WPGraphQL\\Model\\Post')) {
      return new \WPGraphQL\Model\Post($post);
    }

    return null;
  };

  register_graphql_field('RootQuery', 'siteFavicon', [
    'type' => 'MediaItem',
    'description' => 'Favicon du site en tant que MediaItem',
    'resolve' => $favicon_resolver,
  ]);
}
add_action('graphql_register_types', 'jbee_register_fields');

// Optional: expose a small helper in the admin to check registration (no output on frontend)
function jbee_admin_notice_check()
{
  if (!current_user_can('manage_options')) {
    return;
  }

  // Only run on admin screens — no UI, we simply log if WPGraphQL is missing
  if (!function_exists('register_graphql_field')) {
    error_log('[WPGraphQL Site Media] WPGraphQL non détecté dans l\'admin');
  }
}
add_action('admin_init', 'jbee_admin_notice_check');

// End of file
