<?php

  // Array to object
  if (function_exists("array_to_object") === false) {
    function array_to_object ($value) {
      // run the_content filter on all textarea values
      $value = json_decode(json_encode($value));
      return $value;
    } add_filter('acf/format_value', 'array_to_object', 10, 3);
  }

  // Sanitize
  if (function_exists("sanitize") === false) {
    function sanitize ($string) {
      if (empty($string) === false) {
        // Escape
        $return = esc_html($string);
        $return = esc_sql($return);
        $return = esc_js($return);
        // Sanitize
        $return = sanitize_text_field($return);
        // $return = sanitize_title_for_query($return);

        return $return;
      }
    }
  }
