<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('gwsfb_debug_log_write')) {
  function gwsfb_debug_log_write($label, $data = null): void {
    if (!defined('WP_CONTENT_DIR')) return;

    $file = trailingslashit(plugin_dir_path(__FILE__)) . 'gwsfb-debug.log';

    $time = function_exists('current_time')
      ? current_time('mysql')
      : date('Y-m-d H:i:s');

    $line = '[' . $time . '] ' . (string)$label;

    if (func_num_args() > 1) {
      if (is_scalar($data) || $data === null) {
        $line .= ' => ' . var_export($data, true);
      } else {
        $line .= ' => ' . print_r($data, true);
      }
    }

    $line .= PHP_EOL;

    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
  }
}

if (!function_exists('gwsfb_debug_log_clear')) {
  function gwsfb_debug_log_clear(): void {
    $file = trailingslashit(plugin_dir_path(__FILE__)) . 'gwsfb-debug.log';
    @file_put_contents($file, '');
  }
}