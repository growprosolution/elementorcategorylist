<?php
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/render/render-filter.php';
require_once __DIR__ . '/render/render-result.php';
require_once __DIR__ . '/render/render-sort.php';

class GWSFB_Render {
  use GWSFB_Render_Shell;
  use GWSFB_Render_Filters;
  use GWSFB_Render_Results;
  use GWSFB_Render_Sort;
}

add_shortcode('gws_filters', ['GWSFB_Render', 'shortcode']);
add_shortcode('gws_filters_panel', ['GWSFB_Render', 'shortcode_filters_only']);
add_shortcode('gws_products_results', ['GWSFB_Render', 'shortcode_results_only']);
