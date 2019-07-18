<?php

  // Declare namespace of PHPMailer
  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\Exception;

  // This file holds all the actions that are used
  // In order to send the e-mails
  function sendMail () {

    // Initiate mailer class
    $mailer = new Mailer();

    // Check if request is not empty
    if (empty($_REQUEST) === false) {

      // Check if the form_id was set
      if (isset($_REQUEST["form_id"]) === true && is_numeric($_REQUEST["form_id"]) === true) {

        // Get the form
        $form = $mailer->getFormPost($_REQUEST["form_id"]);

        // Check if not empty
        // If not, the form is valid
        if (empty($form) === false && is_object($form) === true) {

          // Sanitize data
          $request = $mailer->sanitizeData($_REQUEST);

          // If request not false
          if ($request !== false) {

            // Get all the general options
            $settings = get_fields("forms_settings");

            // Get the fields
            $fields = get_fields($form->ID);

            // Check if fields are given
            if (empty($fields["fields"]) === false) {

              // Set some variables
              $message   = $fields["message"];
              $subject   = $fields["subject"];
              $from      = $fields["from"][0];
              $toArray   = $fields["to"];

              // Chekc if given
              (empty($settings["smtp"]) === false) ? $smtp = $settings["smtp"] : $smtp = false;
              (empty($settings["recaptcha"]) === false) ? $recaptcha = $settings["recaptcha"] : $recaptcha = false;

              // Check if smtp & recaptcha settings are given
              if ($smtp !== false && $recaptcha !== false) {

                // Set required array
                $required = $mailer->checkRequired($fields["fields"]);

                // Validate the fields
                $validate = $mailer->validateFields($request, $required);

                // Check if validate returned true
                if ($validate === true) {

                  // We have validated all fields
                  // Now we can set up the variables and hooks in order to send the mail
                  // Require PHPMailer
                  require(__DIR__ . "/../vendor/autoload.php");

                  // Declare class
                  $mail = new PHPMailer(true);

                  // Set settings
                  $mail->SMTPDebug = 4;
                  $mail->isSMTP();
                  $mail->Host        = $smtp->host;
                  $mail->SMTPAuth    = true;
                  $mail->Username    = $smtp->username;
                  $mail->Password    = $smtp->password;
                  $mail->SMTPSecure  = "";
                  $mail->SMTPAutoTLS = false;
                  $mail->Port        = $smtp->port;
                  // $mail->SMTPSecure  = $smtp->secure;
                  $mail->isHTML(true);

                  // Check for BCc
                  if (isset($fields["sendCopy"]) === true && $fields["sendCopy"] === true) {
                    // Get mail and name
                    $rName  = $request[$fields["nameField"]];
                    $rEmail = $request[$fields["emailField"]];
                    // Add bcc
                    $mail->addBCC($rEmail, $rName);
                  }

                  // Mail variables
                  $mail->setFrom($from->email, $from->name);

                  // Add reciepient
                  foreach ($toArray as $to) {
                    $mail->addAddress($to->email);
                  }

                  // Set subject and message
                  $mail->Subject = $mailer->replaceVariable($subject, $request);
                  $mail->Body    = $mailer->replaceVariable($message, $request);

                  // Try sending
                  try {
                    $mail->send();
                  } catch (Exception $e) {
                    wp_send_json_error("Error white sending e-mail");
                  }

                } else {
                  // Throw error
                  wp_send_json_error($validate);
                }

              } else {
                wp_send_json_error("Please configure the SMTP and ReCAPTCHA settings correctly.");
              }
            }
          } else {
            wp_send_json_error("Unable to sanitize input.");
          }
        }
      }
    }

    // Exit and/or die the script
    wp_die();
  }

  add_action('wp_ajax_sendMail', 'sendMail');
  add_action('wp_ajax_nopriv_sendMail', 'sendMail');
