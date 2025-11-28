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
  if (
    !empty(getenv('MAIL_SMTP_HOST')) &&
    !empty(getenv('MAIL_SMTP_PORT')) &&
    !empty(getenv('MAIL_SMTP_USERNAME')) &&
    !empty(getenv('RECAPTCHA_SITE_KEY')) &&
    !empty(getenv('RECAPTCHA_SECRET_KEY'))
  ) {

    $recaptcha_min_score = getenv('RECAPTCHA_MIN_SCORE');
    $recaptcha_min_score = is_numeric($recaptcha_min_score) ? floatval($recaptcha_min_score) : 0.5;

    // Grab all the environment variables needed for this configuration
    $env = [
      "smtp" => (object) [
        "host"     => getenv('MAIL_SMTP_HOST'),
        "port"     => getenv('MAIL_SMTP_PORT'),
        "username" => getenv('MAIL_SMTP_USERNAME'),
        "password" => getenv('MAIL_SMTP_PASSWORD'),
        "secure"   => getenv('MAIL_SMTP_SECURE') // ssl or tls
      ],
      "recaptcha" => (object) [
        "key_site"   => getenv('RECAPTCHA_SITE_KEY'),
        "key_secret" => getenv('RECAPTCHA_SECRET_KEY'),
        "min_score"  => $recaptcha_min_score
      ]
    ];

  } else {

    wp_send_json_error([
      "message" => "Please check the environment variables. An example is provided in .example.env."
    ]);
  }

  // Check if request is not empty
  if (!empty($_REQUEST)) {

    $settings = $env;

    // Check if the form_id was set
    if (isset($_REQUEST["form_id"]) && is_numeric($_REQUEST["form_id"])) {

      // Get the form
      $form = $mailer->getFormPost($_REQUEST["form_id"]);

      // Honeypot
      $honeyValue = end($_REQUEST);
      $honeyKey   = key(array_reverse($_REQUEST));

      if (!empty($form) && is_object($form) && in_array($honeyKey, $mailer->honey) && empty($honeyValue)) {

        // Sanitize data
        $request = $mailer->sanitizeData($_REQUEST);

        if (!empty($_FILES)) {
          $request[key($_FILES)] = $_FILES[key($_FILES)];
        }

        if ($request !== false) {

          $fields = get_fields($form->ID);

          if (!empty($fields["fields"])) {

            $message = $fields["message"];
            $subject = $fields["subject"];
            $from    = $fields["from"][0];
            $toArray = $fields["to"];

            $smtp      = $settings["smtp"] ?? false;
            $recaptcha = $settings["recaptcha"] ?? false;

            if ($smtp && $recaptcha) {

              $required = $mailer->checkFields($fields["fields"]);
              $validate = $mailer->validateFields($request, $required, $fields["error"]);

              if ($validate === true) {

                // Check recaptcha token
                if (!empty($_REQUEST["recaptcha"])) {

                  $token = $_REQUEST["recaptcha"];
                  $url   = "https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha->key_secret}&response={$token}";

                  $response = getSslPage($url);
                  $response = json_decode($response);

                  if ($response === null) {
                    $e = $fields["error"]->recaptchaValidationError ?? "Could not validate reCAPTCHA.";
                    wp_send_json_error(["message" => $e]);
                  }

                } else {
                  $e = $fields["error"]->noRecaptcha ?? "Recaptcha wasn't added to the script.";
                  wp_send_json_error(["message" => $e]);
                }

                $responseSuccess = $response->success ?? false;
                $recaptchaScore  = isset($response->score) ? floatval($response->score) : null;
                $minScore = $recaptcha->min_score;

                if ($responseSuccess && ($recaptchaScore === null || $recaptchaScore >= $minScore)) {

                  // ============
                  // SMTP SETUP (THE FIXED PART)
                  // ============

                  $mail = new PHPMailer(true);

                  $mail->CharSet   = 'UTF-8';
                  $mail->Encoding  = 'base64';
                  $mail->isSMTP();

                  // Debug
                  $debugLevel = getenv('MAIL_SMTP_DEBUG');
                  $mail->SMTPDebug = in_array($debugLevel, ['0','1','2','4']) ? intval($debugLevel) : 0;

                  // Load env SMTP values
                  $smtpHost   = $smtp->host;
                  $smtpPort   = intval($smtp->port);
                  $smtpUser   = $smtp->username;
                  $smtpPass   = $smtp->password;
                  $smtpSecure = strtolower($smtp->secure);

                  $mail->Host       = $smtpHost;
                  $mail->Port       = $smtpPort;
                  $mail->SMTPAuth   = true;
                  $mail->Username   = $smtpUser;
                  $mail->Password   = $smtpPass;

                  // Convert env secure → PHPMailer secure
                  if ($smtpSecure === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL (465)
                  } elseif ($smtpSecure === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS (587)
                  } else {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // fallback
                  }

                  $mail->SMTPAutoTLS = false;  
                  $mail->isHTML(true);

                  // Optional IPv4 forcing
                  if (getenv('MAIL_SMTP_FORCE_IPV4') === 'true') {
                    $mail->SMTPOptions = [
                      'socket' => [
                        'bindto' => '0.0.0.0:0'
                      ]
                    ];
                  }

                  // BCC (send copy)
                  if (!empty($fields["sendCopy"])) {
                    $rName  = $request[$fields["nameField"]];
                    $rEmail = $request[$fields["emailField"]];
                    $mail->addBCC($rEmail, $rName);
                  }

                  // From
                  $fromEmail = getenv('MAIL_FROM_EMAIL') ?: $smtpUser;
                  $mail->setFrom($fromEmail, $from->name);

                  if (!empty($from->email) && $fromEmail !== $from->email) {
                    $mail->addReplyTo($from->email, $from->name);
                  }

                  // Recipients
                  foreach ($toArray as $to) {
                    $mail->addAddress($to->email);
                  }

                  // Subject & Body
                  $mail->Subject = $mailer->replaceVariable($subject, $request);
                  $mail->Body    = $mailer->replaceVariable($message, $request) . "\n";

                  // Attach files
                  if (!empty($_FILES)) {
                    $files = $_FILES[array_key_first($_FILES)];
                    foreach ($files["tmp_name"] as $key => $tmp) {
                      $name = $files["name"][$key];
                      $tempPath = sys_get_temp_dir() . '/' . basename($name);
                      if (move_uploaded_file($tmp, $tempPath)) {
                        $mail->addAttachment($tempPath);
                      }
                    }
                  }

                  // Send mail
                  try {
                    if ($mail->send()) {
                      $e = $fields["error"]->emailSuccess ?? "E-mail successfully sent!";
                      wp_send_json_success(["message" => $e]);
                    }
                  }
                  catch (Exception $e) {
                    $e = $fields["error"]->emailError ?? "Error sending e-mail";
                    wp_send_json_error(["message" => $e]);
                  }

                } else {

                  if ($responseSuccess && $recaptchaScore !== null && $recaptchaScore < $minScore) {
                    $e = $fields["error"]->recaptchaScoreTooLow ?? "ReCAPTCHA score too low.";
                  } else {
                    $e = $fields["error"]->recaptchaValidationError ?? "ReCAPTCHA didn't pass validation.";
                  }

                  wp_send_json_error(["message" => $e]);
                }

              } else {
                wp_send_json_error($validate);
              }
            } else {
              $e = $fields["error"]->missingSettings ?? "SMTP and ReCAPTCHA must be configured.";
              wp_send_json_error(["message" => $e]);
            }
          }
        }

      } else {
        $e = $fields["error"]->honeypotFailed ?? "Honeypot validation failed.";
        wp_send_json_error(["message" => $e]);
      }
    }
  }

  wp_die();
}

// Only register AJAX actions if WordPress functions are available
if (function_exists('add_action')) {
  add_action('wp_ajax_sendMail', 'sendMail');
  add_action('wp_ajax_nopriv_sendMail', 'sendMail');
}
