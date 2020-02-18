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

      // Check if the recaptcha is valid
      // Get all the general options
      $settings = get_fields("forms_settings");

      // Check if the form_id was set
      if (isset($_REQUEST["form_id"]) === true && is_numeric($_REQUEST["form_id"]) === true) {

        // Get the form
        $form = $mailer->getFormPost($_REQUEST["form_id"]);

        // Get the last field of array
        $honeyValue = end($_REQUEST);
        $honeyKey   = key(array_reverse($_REQUEST));

        // Check if not empty
        // If not, the form is valid
        // Also, check for honeypot occurence
        if (empty($form) === false && is_object($form) === true && in_array($honeyKey, $mailer->honey) === true && empty($honeyValue) === true) {

          // Sanitize data
          $request = $mailer->sanitizeData($_REQUEST);

          // Check if there are files
          if (empty($_FILES) === false) {
            // Shift to array
            $request[key($_FILES)] = $_FILES[key($_FILES)];
          }

          // If request not false
          if ($request !== false) {

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
                $required = $mailer->checkFields($fields["fields"]);

                // Validate the fields
                $validate = $mailer->validateFields($request, $required, $fields["error"]);

                // Check if validate returned true
                if ($validate === true) {

                  // Check if recaptcha is given
                  if (empty($_REQUEST["recaptcha"]) === false) {

                    // Validate the recaptcha
                    $validate = $_REQUEST["recaptcha"];

                    // Format the url for the request
                    $url = "https://www.google.com/recaptcha/api/siteverify?secret={$settings["recaptcha"]->key_secret}&response={$validate}";

                    // Catch response for the recaptcha
                    $response = getSslPage($url);
                    $response = json_decode($response);

                  } else {

                    if (empty($fields["error"]->noRecaptcha) === false) $e = $fields["error"]->noRecaptcha; else $e = "Recaptcha wasn't added to the script.";

                    // Die!
                    wp_send_json_error(
                      ["message" => $e]
                    );

                    wp_die();

                  }

                  // Check recaptcha
                  if ($response->success === true) {

                    // We have validated all fields
                    // Now we can set up the variables and hooks in order to send the mail
                    // Declare class
                    $mail = new PHPMailer(true);

                    // Set settings
                    $mail->SMTPDebug = 0;
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

                    wp_send_json_success($smtp);

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
                    $mail->Body    = $mailer->replaceVariable($message, $request) . "\n";

                    // Add files
                    if (empty($_FILES) === false) {

                      // Set files
                      $files = $_FILES[array_key_first($_FILES)];

                      // Loop through temp names
                      foreach ($files["tmp_name"] as $key => $file) {

                        // Store real name and temp name
                        $name = $files["name"][$key];

                        // Move file to temp folder
                        if (move_uploaded_file($file, sys_get_temp_dir() . basename($name)) === true) {
                          // Add to mailer
                          $mail->addAttachment(sys_get_temp_dir() . basename($name));
                        }
                      }
                    }

                    // Try sending
                    try {

                      // Store
                      if ($mail->send()) {

                        if (empty($fields["error"]->emailSuccess) === false) $e = $fields["error"]->emailSuccess; else $e = "E-mail successfully sent!";

                        // Send succes => Mail was sent!
                        wp_send_json_success(
                          ["message" => $e]
                        );
                      }

                    } catch (Exception $e) {

                      if (empty($fields["error"]->emailError) === false) $e = $fields["error"]->emailError; else $e = "Error sending e-mail";

                      wp_send_json_error(
                        ["message" => $e]
                      );
                    }

                  } else {

                    if (empty($fields["error"]->recaptchaValidationError) === false) $e = $fields["error"]->recaptchaValidationError; else $e = "ReCAPTCHA didn't pass validation.";

                    // Recaptcha didn't validate
                      wp_send_json_error(
                        ["message" => $e]
                      );
                  }

                } else {
                  // Throw error
                  wp_send_json_error($validate);
                }

              } else {

                if (empty($fields["error"]->missingSettings) === false) $e = $fields["error"]->missingSettings; else $e = "Please configure the SMTP and ReCAPTCHA settings correctly.";

                wp_send_json_error(
                  ["message" => $e]
                );
              }
            }
          } else {
            // wp_send_json_error(
            //   ["message" => "Unable to sanitize input."]
            // );
          }
        } else {

          if (empty($fields["error"]->honeypotFailed) === false) $e = $fields["error"]->honeypotFailed; else $e = "Honeypot validation failed.";

          wp_send_json_error(
            ["message" => $e]
          );
        }
      }
    }

    // Exit and/or die the script
    wp_die();
  }

  add_action('wp_ajax_sendMail', 'sendMail');
  add_action('wp_ajax_nopriv_sendMail', 'sendMail');
