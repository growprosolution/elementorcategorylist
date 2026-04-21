<?php
if (!defined('ABSPATH')) exit;

class GWSFB_Elementor {

  public static function init() {
    add_action('elementor/init', [__CLASS__, 'on_elementor_init']);
  }

  public static function on_elementor_init() {
    add_action('elementor/widgets/register', [__CLASS__, 'register_widgets']);
    add_action('elementor/widgets/widgets_registered', [__CLASS__, 'register_widgets_legacy']);
  }

  public static function register_widgets($widgets_manager) {
    require_once GWSFB_PATH . 'includes/widget-gwsfb-filters.php';
    require_once GWSFB_PATH . 'includes/widget-gwsfb-results.php';

    if (class_exists('GWSFB_Elementor_Widget_Filters')) {
      $widgets_manager->register(new \GWSFB_Elementor_Widget_Filters());
    }
    if (class_exists('GWSFB_Elementor_Widget_Results')) {
      $widgets_manager->register(new \GWSFB_Elementor_Widget_Results());
    }
  }

  public static function register_widgets_legacy() {
    if (!class_exists('\Elementor\Plugin')) return;
    $plugin = \Elementor\Plugin::instance();
    if (empty($plugin->widgets_manager)) return;
    self::register_widgets($plugin->widgets_manager);
  }
}
