<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
/*
 Plugin Name: wg compareo
 Description: Create attractive image compare boxes. Place them anywhere with easy to use shortcode.
 Version: 1.0
 Author: Webgensis
 Author URI: http://www.webgensis.com
 */
/*  Copyright 2017-2018 webgensis  (email : info@webgensis.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/* require CMB2 */
require_once  __DIR__ . '/inc/cmb2/init.php';

/* Version of the plugin */
define('WG_CMPR_VERSION', '1.0' );

/* our plugin name to be used at multiple places */
define( 'WG_CMPR_PLUGIN_NAME', "wg compareo" );

/* We'll key on the slug, set it here so it can be used in various places */
define( 'WG_CMPR_PLUGIN_SLUG', plugin_basename( __FILE__ ) );

/* We'll define post type here so it can be used in various places */
define( 'WG_CMPR_POST_TYPE', 'compare' );
define( 'WG_CMPR_POST_TYPE_NAME', 'Image Compare' );
define( 'WG_CMPR_POST_TYPE_META_PREFIX', '_wg_cmpr_' );

/* registering scripts and style */
function wg_cmpr_scripts() {
    if ( ! wp_script_is( 'jquery', 'enqueued' )) {
        wp_enqueue_script( 'jquery' );
    }
    wp_enqueue_style( 'wg_cmpr_style', plugin_dir_url( __FILE__ ) . 'inc/wg_cmpr.css',1.0,true );
    wp_enqueue_script( 'wg_cmpr_jquery_hoverdir', plugin_dir_url( __FILE__ ) . 'inc/wg_cmpr.js',1.0, true );
}
add_action( 'wp_footer', 'wg_cmpr_scripts'); 

/* flush rewrite rules on activation */
register_activation_hook( WG_CMPR_PLUGIN_SLUG, 'wg_cmpr_activation' );
function wg_cmpr_activation() {
  if ( ! current_user_can( 'activate_plugins' ) ) {
    return;
  }
    flush_rewrite_rules();
}

/* Delete flush rewrite rules once activated properly*/
add_action( 'admin_init','wg_cmpr_initialize' );
function wg_cmpr_initialize() {
    if( is_admin() && get_option( 'wg_cmpr_activation' ) == 'just-activated' ) {
      delete_option( 'wg_cmpr_activation' );
        flush_rewrite_rules();
    }
}

/* deactivate plugin */
function wg_cmpr_deactivate() {
  flush_rewrite_rules();
}
register_deactivation_hook( WG_CMPR_PLUGIN_SLUG, 'wg_cmpr_deactivate' );

/* uninstall plugin */
function  wg_cmpr_uninstall() {
  if ( ! current_user_can( 'activate_plugins' ) ) {
    return;
  }
    $args = array (
      'post_type' => WG_CMPR_POST_TYPE,
      'nopaging' => true
    );
    $query = new WP_Query ($args);
    while ($query->have_posts ()) {
      $query->the_post ();
      $id = get_the_ID ();
      wp_delete_post ($id, true);
    }
    wp_reset_postdata ();
    flush_rewrite_rules();
}
register_uninstall_hook( __FILE__, 'wg_cmpr_uninstall' );

/* Add post type */
add_action( 'init', 'wg_cmpr_post_type' );
function wg_cmpr_post_type() {
  $labels = array(
    'name'               => WG_CMPR_POST_TYPE,
    'singular_name'      => WG_CMPR_POST_TYPE_NAME,
    'menu_name'          => WG_CMPR_POST_TYPE_NAME,
    'name_admin_bar'     => 'New ' . WG_CMPR_POST_TYPE_NAME,
    'add_new'            => 'Add New ' . WG_CMPR_POST_TYPE_NAME,
    'add_new_item'       => 'Add New ' . WG_CMPR_POST_TYPE_NAME,
    'new_item'           => 'New ' . WG_CMPR_POST_TYPE_NAME,
    'edit_item'          => 'Edit ' . WG_CMPR_POST_TYPE_NAME,
    'view_item'          => 'View ' .WG_CMPR_POST_TYPE_NAME,
    'all_items'          => 'All ' . WG_CMPR_POST_TYPE_NAME,
    'search_items'       => 'Search ' . WG_CMPR_POST_TYPE_NAME,
    'parent_item_colon'  => 'Parent ' . WG_CMPR_POST_TYPE_NAME . ':',
    'not_found'          => 'No ' . WG_CMPR_POST_TYPE_NAME . ' found.',
    'not_found_in_trash' => 'No ' . WG_CMPR_POST_TYPE_NAME . ' found in Trash.',
  );
  $args = array(
    'labels'             => $labels,
    'public'             => true,
    'publicly_queryable' => true,
    'show_ui'            => true,
    'show_in_menu'       => true,
    'query_var'          => true,
    'menu_icon'          => 'dashicons-grid-view',
    'has_archive'        => false,
    'hierarchical'       => false,
    'menu_position'      => null,
    'supports'           => array( 'title' ),
  );
    register_post_type(WG_CMPR_POST_TYPE, $args);
}

/* Add column to post type */
add_filter('manage_edit-imagegrid_columns', 'wg_cmpr_columns_head');
add_action('manage_imagegrid_posts_custom_column', 'wg_cmpr_columns_content', 10, 2);
function wg_cmpr_columns_head($defaults) {
    $defaults['shortcode'] = 'Shortcode';
    return $defaults;
}
function wg_cmpr_columns_content($column_name, $post_ID) {
    if ($column_name == 'shortcode') {
      $key=WG_CMPR_POST_TYPE_META_PREFIX.'shortcode';
      echo get_post_meta($post_ID, $key, true);
    }
}

/* our post-type fields */
add_action( 'cmb2_admin_init', 'wg_cmpr_metabox');
function  wg_cmpr_metabox() {
  /* Start with an underscore to hide fields from custom fields list */
  $prefix = WG_CMPR_POST_TYPE_META_PREFIX;
  // instantiate metabox
  $cmb = new_cmb2_box( array(
      'id'            => $prefix.'metabox',
      'title'         => 'Images to compare',
      'object_types'  => array( WG_CMPR_POST_TYPE )
  ) );
  $cmb->add_field( array(
  'name'  =>  __('Shortcode', WG_CMPR_POST_TYPE),
  'id'   => $prefix.'shortcode',
  'type' => 'text',
  'attributes'  => array(
    'readonly' => 'readonly',
  ),
  ) );
  $cmb->add_field( array(
    'name'    =>  __('Compare Box Height in px', WG_IGHE_POST_TYPE),
    'desc'    => 'Hover Delay in miliseconds, Leave blank for no delay (optional)',
    'id'      => $prefix.'height',
    'type'    => 'text',
    'default' =>  300,
    'attributes' => array(
        'type' => 'number',
      ),
    ) );
  $cmb->add_field( array(
        'id'            => $prefix.'image_before',
        'name'          => __('Before Image', WG_IGHE_POST_TYPE),
        'type'          => 'file',
        'desc'          => 'Image of before state.',
        'attributes'    => array(
      'placeholder' => 'Before Image',
      'required'    => 'required',
    ),
  ) );
  $cmb->add_field( array(
        'id'            => $prefix.'image_after',
        'name'          => __('After Image', WG_IGHE_POST_TYPE),
        'type'          => 'file',
        'desc'          => 'Image of after state.',
        'attributes'    => array(
      'placeholder' => 'After Image',
      'required'    => 'required',
    ),
  ) );
}

/* shortcode to meta */
add_filter( 'save_post', 'wg_cmpr_update_shortcode_meta',10,3);
function wg_cmpr_update_shortcode_meta( $post_id, $post ) { 
  if( WG_CMPR_POST_TYPE == $post->post_type ) {
    $prefix = WG_CMPR_POST_TYPE_META_PREFIX;
    $value="[compareo id=\"".$post_id."\"]"; 
    update_post_meta($post_id,$prefix.'shortcode', $value);
  }
}

/*Frontend view Shortcode*/
function wg_cmpr_grid_output($atts){
  extract( shortcode_atts( array(
    'id' => 0,
  ), $atts));
  if ($id==0) {
    $output='Please add capareo post ID!';
  }else{
    $meta_prefix = WG_CMPR_POST_TYPE_META_PREFIX;
    $compare['image_before'] = get_post_meta($id, $meta_prefix.'image_before',true);
    $compare['image_after'] = get_post_meta($id, $meta_prefix.'image_after',true);
    $compare['height'] = get_post_meta($id, $meta_prefix.'height',true);
    $output='<div class="baSlider" id="ba-'.$id.'">';
    $output.='<div class="frame">';
    $output.='<div baSlider-handler><img src="'.plugin_dir_url( __FILE__ ).'images/drag.svg" alt=""></div>';
    $output.='<div class="before">';
    $output.='<img src="'.$compare['image_before'].'" baSlider-image>';
    $output.='</div><div class="after"><div>';
    $output.='<img src="'.$compare['image_after'].'" baSlider-image>';
    $output.='</div></div></div></div></div>';
    $output.='<script defer>';
    $output.='jQuery("document").ready(function($){$("#ba-'.$id.'").baSlider();});';
    $output.='</script>';
    $output.='<style>#ba-'.$id.',#ba-'.$id.' .frame{ height: '.$compare['height'].'px; }</style>';
  }
  return $output;
  unset($output);
}
add_shortcode('compareo', 'wg_cmpr_grid_output');
?>