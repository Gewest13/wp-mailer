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

  // Clear wp_footer
  if (function_exists("remove_all_actions") === true) {
    // Clear the wp_footer
    remove_all_actions("wp_footer");
  }

  // Add script to wp_footer
  if (function_exists("add_action") === true) {
    // Add the action to add
    add_action("wp_footer", function () {

      // Set directory
      $directory = get_template_directory_uri();
      $file      = "{$directory}/server/wp-mailer/js/wp-mailer.js";

      // Return the script field with the file
      echo "<script src='{$file}'></script>\n";

    });
  }
