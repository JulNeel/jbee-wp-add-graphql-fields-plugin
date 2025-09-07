<?php
/**
 * Plugin Name: JBEE WP Add GraphQL Fields
 * Description: Ajoute différents champs au schéma GraphQL
 * Version: 1.0.0
 * Author: Julien Bruneel
 */


function register_graphql_media_field($field_name, $description, callable $id_callback)
{
  register_graphql_field('RootQuery', $field_name, [
    'type' => 'MediaItem',
    'description' => $description,
    'resolve' => function () use ($id_callback, $field_name) {
      $attachment_id = call_user_func($id_callback);

      if (!$attachment_id) {
        error_log("[GraphQL] Aucun média pour {$field_name}");
        return null;
      }

      $post = get_post($attachment_id);

      if (!$post || $post->post_type !== 'attachment') {
        error_log("[GraphQL] L’ID {$attachment_id} n’est pas un attachment ({$field_name})");
        return null;
      }

      return $post;
    }
  ]);
}


add_action('graphql_register_types', function () {
  // Logo
  register_graphql_media_field(
    'siteLogo',
    'Logo du site en tant que MediaItem',
    fn() => get_theme_mod('custom_logo')
  );

  // Favicon 
  register_graphql_media_field(
    'siteFavicon',
    'Favicon du site en tant que MediaItem',
    fn() => get_option('site_icon')
  );

  // Image d’en-tête
  register_graphql_media_field(
    'siteHeaderImage',
    'Image d’en-tête du site en tant que MediaItem',
    function () {
      if (!function_exists('get_custom_header')) {
        return null;
      }
      $header = get_custom_header();
      return $header && !empty($header->attachment_id) ? $header->attachment_id : null;
    }
  );
});
