<?php
/**
 * Plugin Name: GWS Woo Filter Builder (Elementor)
 * Description: Admin-configurable WooCommerce filter sets + Elementor widget + AJAX filtering.
 * Version: 0.1.1
 * Author: PeterGPT
 * Author URI: https://www.menslaveai.com/
 */

if (!defined('ABSPATH')) exit;

define('GWSFB_VER', '0.1.1');
define('GWSFB_PATH', plugin_dir_path(__FILE__));
define('GWSFB_URL', plugin_dir_url(__FILE__));

require_once GWSFB_PATH . 'includes/helpers.php';
require_once GWSFB_PATH . 'includes/class-admin.php';
require_once GWSFB_PATH . 'includes/class-query.php';
require_once GWSFB_PATH . 'includes/class-render.php';
require_once GWSFB_PATH . 'includes/class-ajax.php';
require_once GWSFB_PATH . 'includes/class-elementor.php';

add_action('plugins_loaded', function () {
  if (!class_exists('WooCommerce')) return;

  GWSFB_Admin::init();
  GWSFB_Ajax::init();
  GWSFB_Elementor::init();
});

add_action('wp_enqueue_scripts', function () {
  $css_rel = 'assets/front.css';
  $css_abs = GWSFB_PATH . $css_rel;

  if (file_exists($css_abs)) {
    wp_register_style(
      'gwsfb-front',
      GWSFB_URL . $css_rel,
      [],
      filemtime($css_abs)
    );
    wp_enqueue_style('gwsfb-front');
  } else {
    error_log('[GWSFB] Missing CSS file: ' . $css_abs);
  }

  $files = [
    'gwsfb-config'  => 'assets/render/front-config.js',
    'gwsfb-filter'  => 'assets/render/front-filter.js',
    'gwsfb-sort'    => 'assets/render/front-sort.js',
    'gwsfb-results' => 'assets/render/front-result.js',
  ];

  foreach ($files as $handle => $rel) {
    $abs = GWSFB_PATH . $rel;
    $url = GWSFB_URL . $rel;

    if (!file_exists($abs)) {
      error_log('[GWSFB] Missing JS file: ' . $abs);
      continue;
    }

    $deps = ['jquery'];

    if ($handle === 'gwsfb-filter') {
      $deps = ['jquery', 'gwsfb-config'];
    } elseif ($handle === 'gwsfb-sort') {
      $deps = ['jquery', 'gwsfb-config', 'gwsfb-filter'];
    } elseif ($handle === 'gwsfb-results') {
      $deps = ['jquery', 'gwsfb-config', 'gwsfb-filter', 'gwsfb-sort'];
    }

    wp_register_script(
      $handle,
      $url,
      $deps,
      filemtime($abs),
      true
    );
  }	

  if (wp_script_is('gwsfb-config', 'registered')) {
    wp_enqueue_script('gwsfb-config');

    wp_localize_script('gwsfb-config', 'GWSFB', [
      'ajaxurl' => admin_url('admin-ajax.php'),
      'nonce'   => wp_create_nonce('gwsfb_filter'),
    ]);
  }

  if (wp_script_is('gwsfb-filter', 'registered')) {
    wp_enqueue_script('gwsfb-filter');
  }

  if (wp_script_is('gwsfb-sort', 'registered')) {
    wp_enqueue_script('gwsfb-sort');
  }

  if (wp_script_is('gwsfb-results', 'registered')) {
    wp_enqueue_script('gwsfb-results');
  }
}, 20);