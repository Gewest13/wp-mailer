<?php

  // Array to object
  if (function_exists("array_to_object") === false) {
    function array_to_object ($value) {
      // run the_content filter on all textarea values
      $value = json_decode(json_encode($value));
      return $value;
    }
    // Only add the filter if WordPress functions are available
    if (function_exists('add_filter')) {
      add_filter('acf/format_value', 'array_to_object', 10, 3);
    }
  }

  // Sanitize
  if (function_exists("sanitize") === false) {
    function sanitize ($string) {
      if (empty($string) === false) {
        // Check if WordPress functions are available
        if (function_exists('esc_html')) {
          // Escape using WordPress functions
          $return = esc_html($string);
          $return = esc_sql($return);
          $return = esc_js($return);
          // Sanitize
          $return = sanitize_text_field($return);
        } else {
          // Fallback sanitization when WordPress is not available
          $return = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
          $return = addslashes($return);
        }

        return $return;
      }
    }
  }

  // Get ssl page
  if (function_exists("getSslPage") === false) {
    function getSslPage($url) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_REFERER, $url);
      // Avoid hanging requests when Google's endpoint is slow or unreachable
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
    }
  }

  // Clear wp_footer
  if (function_exists("remove_all_actions") === true) {
    if (function_exists("is_user_logged_in") && !is_user_logged_in()) {
      // Clear the wp_footer
      remove_all_actions("wp_footer");
    }
  }

  // Add script to wp_footer
  if (function_exists("add_action") === true) {
    // Add the action to add
    add_action("wp_footer", function () {
      // Only proceed if WordPress functions are available
      if (function_exists("get_template_directory")) {
        // Load ENV
        $dotenv = Dotenv\Dotenv::createImmutable(get_template_directory());
        $dotenv->load();

        if (getenv('RECAPTCHA_SITE_KEY')) {

          // Set directory
          if (function_exists("get_template_directory_uri")) {
            $directory = get_template_directory_uri();
          }
        // $file      = "{$directory}/server/wp-mailer/js/wp-mailer.js";
  
        // Optional: compiled babeljs file
        // $file      = "{$directory}/server/wp-mailer/js/app.js";
  
        // Get the field that holds the site key
        $site = getenv('RECAPTCHA_SITE_KEY');
  
          // Return the script field with the file
          echo "<script src='https://www.google.com/recaptcha/api.js?render={$site}'></script>\n";
          // echo "<script src='{$file}' type='module'></script>\n";

        }
      }

    });
  }
