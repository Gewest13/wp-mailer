<?php

  // This file holds all the actions that are used
  // In order to send the e-mails
  function sendMail () {

    // Check if request is not empty
    if (empty($_REQUEST) === false) {
      // Check if the form_id was set
      if (isset($_REQUEST["form_id"]) === true && is_numeric($_REQUEST["form_id"]) === true) {

        // Get the form from a query
        $arguments = [
          "post_type"      => "forms",
          "ID"             => $id,
          "posts_per_page" => 1,
          "fields"         => "ids"
        ];

        // Shoot the query
        $form = new WP_Query($arguments);
        $form = $form->posts;

        // Check if not empty
        // If not, the form is valid
        if (empty($form) === false && is_array($form)) {

          // Shift the index
          $form = $form[0];

          // Get all the general options
          $settings = get_fields("forms_settings");

          // Hook into PHPMailer
          // So we change the settings over to secure SMTP mail
        }

      }
    }

    // Exit and/or die the script
    wp_die();
  }

  add_action('wp_ajax_sendMail', 'sendMail');
  add_action('wp_ajax_nopriv_sendMail', 'sendMail');
