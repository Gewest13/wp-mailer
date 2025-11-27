<?php

  // Declare namespace of PHPMailer
  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\Exception;

  // This file holds all the actions that are used
  // In order to send the e-mails
  function sendMail () {

    // Initiate mailer class
    $mailer = new Mailer();

    // Grab the dotEnv variables (only if WordPress functions are available)
    if (function_exists("get_template_directory")) {
      $dotenv = Dotenv\Dotenv::createImmutable(get_template_directory());
      $dotenv->load();
    }

    // Check if the variables are stored
    if (empty(getenv('MAIL_SMTP_HOST')) === false
        && empty(getenv('MAIL_SMTP_PORT')) === false
        && empty(getenv('MAIL_SMTP_USERNAME')) === false
        && empty(getenv('RECAPTCHA_SITE_KEY')) === false
        && empty(getenv('RECAPTCHA_SECRET_KEY')) === false) {

      $recaptcha_min_score = getenv('RECAPTCHA_MIN_SCORE');
      $recaptcha_min_score = is_numeric($recaptcha_min_score) ? floatval($recaptcha_min_score) : 0.5;

      // Grab all the environment variables needed for this configuration
      $env = [
        "smtp" => (object) [
          "host"     => getenv('MAIL_SMTP_HOST'),
          "port"     => getenv('MAIL_SMTP_PORT'),
          "username" => getenv('MAIL_SMTP_USERNAME'),
          "password" => getenv('MAIL_SMTP_PASSWORD')
        ],
        "recaptcha" => (object) [
          "key_site"   => getenv('RECAPTCHA_SITE_KEY'),
          "key_secret" => getenv('RECAPTCHA_SECRET_KEY'),
          "min_score"  => $recaptcha_min_score
        ]
      ];

    } else {

      // Die!
      wp_send_json_error(
        ["message" => "Please check the environment variables. En example is giving within the .example.env file."]
      );

    }

    // Check if request is not empty
    if (empty($_REQUEST) === false) {

      // Check if the recaptcha is valid
      // Get all the general options
      $settings = $env;

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

              // Check if given
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
                  if (is_object($response) === false || isset($response->success) === false) {
                    $recaptchaScore = null;
                    $responseSuccess = false;
                  } else {
                    $recaptchaScore = isset($response->score) ? floatval($response->score) : null;
                    $responseSuccess = $response->success;
                  }

                  $minScore = $settings["recaptcha"]->min_score;

                  if ($responseSuccess === true && ($recaptchaScore === null || $recaptchaScore >= $minScore)) {

                    // We have validated all fields
                    // Now we can set up the variables and hooks in order to send the mail
                    // Declare class
                    $mail = new PHPMailer(true);

                    // Set settings
                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'base64';
                    
                    // Set SMTP debug level from environment variable (0, 1, 2, or 4)
                    $debugLevel = getenv('MAIL_SMTP_DEBUG');
                    $mail->SMTPDebug = in_array($debugLevel, ['0', '1', '2', '4']) ? (int)$debugLevel : 0;
                    $mail->isSMTP();
                    $mail->Host        = $smtp->host;
                    
                    // Check if password is provided and not "null" or empty
                    $password = getenv('MAIL_SMTP_PASSWORD');
                    $hasPassword = !empty($password) && $password !== 'null' && $password !== '';
                    
                    $mail->SMTPAuth    = $hasPassword;
                    $mail->Username    = $smtp->username;
                    
                    // Only set password if authentication is enabled
                    if ($hasPassword) {
                        $mail->Password = $smtp->password;
                    }
                    
                    $mail->SMTPSecure  = "";
                    $mail->SMTPAutoTLS = false;
                    $mail->Port        = $smtp->port;
                    
                    // Force IPv4 if specified in environment variable
                    if (getenv('MAIL_SMTP_FORCE_IPV4') === 'true') {
                        $mail->Host = $smtp->host;
                        $mail->SMTPOptions = [
                            'socket' => [
                                'bindto' => '0.0.0.0:0'  // Force IPv4 binding
                            ]
                        ];
                    }
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

                    if ($responseSuccess === true && $recaptchaScore !== null && $recaptchaScore < $minScore) {
                      if (empty($fields["error"]->recaptchaScoreTooLow) === false) $e = $fields["error"]->recaptchaScoreTooLow; else $e = "ReCAPTCHA score did not meet the minimum threshold.";
                    } else {
                      if (empty($fields["error"]->recaptchaValidationError) === false) $e = $fields["error"]->recaptchaValidationError; else $e = "ReCAPTCHA didn't pass validation.";
                    }

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

  // Only register AJAX actions if WordPress functions are available
  if (function_exists('add_action')) {
    add_action('wp_ajax_sendMail', 'sendMail');
    add_action('wp_ajax_nopriv_sendMail', 'sendMail');
  }
