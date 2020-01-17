<?php

  // =============
  // ----
  // Wordpress Mailer module
  // --
  // Author:    Jim de Ronde
  // Website:   www.gewest13.nl
  // ----
  // =============
  class Mailer {

    // Declare variables
    private $args;
    private $labels;
    private $options;
    private $fields;
    public  $honey;
    public  $recaptcha;

    // Set constructor function for class
    public function __construct () {

      // Post type labels
      $this->labels = [
        "name"           => _x( "Forms", "Post Type General Name", "text_domain" ),
        "singular_name"  => _x( "Form", "Post Type Singular Name", "text_domain" ),
        "menu_name"      => __( "Forms", "text_domain" ),
        "name_admin_bar" => __( "Form", "text_domain" ),
        "add_new_item"   => __( "Add new form", "text_domain" ),
      ];

      // Post type arguments
      $this->args = [
        "label"               => __( "Form", "text_domain" ),
        "description"         => __( "All available forms that are registered within the site", "text_domain" ),
        "labels"              => $this->labels,
        "supports"            => ["title"],
        "hierarchical"        => false,
        "public"              => false,
        "show_ui"             => true,
        "show_in_menu"        => true,
        "menu_position"       => 80,
        "menu_icon"           => "dashicons-email",
        "show_in_admin_bar"   => true,
        "show_in_nav_menus"   => true,
        "can_export"          => false,
        "has_archive"         => false,
        "exclude_from_search" => true,
        "publicly_queryable"  => false,
        "rewrite"             => false,
        "capability_type"     => "delete_site",
        "show_in_rest"        => false,
      ];

      // Options page options
      $this->options = [
        "page_title"      => "Settings",
        "menu_title"      => "Settings",
        "menu_slug"       => "forms_settings",
        "capability"      => "delete_site",
        "position"        => false,
        "parent_slug"     => "edit.php?post_type=forms",
        "redirect"        => true,
        "post_id"         => "forms_settings",
        "autoload"        => false,
        "update_button"		=> __("Save", "acf"),
        "updated_message"	=> __("Settings saved", "acf"),
      ];

      // Set the .json fields location
      // To be imported by ACF
      $this->fields = (object) [
        "settings" => __DIR__ . "/../../fields/settings.json",
        "fields"   => __DIR__ . "/../../fields/fields.json"
      ];

      // Set some random honeypot fields to be randomly parsed within the field
      $this->honey = [
        "head",
        "shoulder",
        "knees",
        "toes"
      ];
    }

    // Create post_type to make "Mail"
    // Available from the admin menu
    private function registerType () {
      // Check if not empty
      if (empty($this->args) === false) {
        // Register the post type to the back-end
        register_post_type("forms", $this->args);
      }
    }

    // Register options page
    // That holds all options that are important to our module
    private function registerOptions () {
      if (function_exists("acf_add_options_page") === true) {
         acf_add_options_page($this->options);
      }
    }

    // Register all fields that are required for the module
    private function registerFields () {
      // Check if the object exists
      if (empty($this->fields) === false && is_object($this->fields) === true) {
        // Init the function
        add_action("init", function() {
          if (function_exists("acf_add_local_field_group") === true) {
            // Loop
            foreach ($this->fields as $field) {
              if (file_exists($field) === true) {
                // Process data
                $importFields = $field ? json_decode(file_get_contents($field), true) : array();
                if (empty($importFields) === false) {
                  // Loop
                  foreach ($importFields as $importField) {
                    // Add the fields
                    acf_add_local_field_group($importField);
                  }
                }
              }
            }
          }
        });
      }
    }

    // Check required items function
    public function checkFields (array $fields) {
      if (empty($fields) === false) {
        // Set empty array
        $required = [];
        // Loop through all fields given and check if there are fields that are required
        foreach ($fields as $key => $field) {
          // Check if field is required, if so, parse to required field
          if ($field->acf_fc_layout !== "button") $required[] = $fields[$key];
        }
        // Return
        return $required;
      } else {
        return false;
      }
    }

    // Validate url function
    private function isUrl (string $url) {

      // Check and return
      if (filter_var($url, FILTER_VALIDATE_URL)) {
        return true;
      } else {
        return false;
      }

    }

    // Validate phone
    private function isPhone (string $phone) {

       // Allow +, - and . in phone number
       $filtered_phone_number = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);

       // Remove "-" from number
       $phone_to_check = str_replace("-", "", $filtered_phone_number);

       // Check the lenght of number
       // This can be customized if you want phone number from a specific country
       if (strlen($phone_to_check) < 10 || strlen($phone_to_check) > 14) {
         return false;
       } else {
         return true;
       }
    }

    // Sanitize input function
    private function sanitizeField ($string) {
      if (empty($string) === false) {
        // Escape
        $return = esc_html($string);
        // $return = esc_sql($return);
        // $return = esc_js($return);
        // Sanitize
        $return = sanitize_textarea_field($return);
        // $return = sanitize_title_for_query($return);

        return $return;
      }
    }

    // Get post function
    public function getFormPost (string $id) {
      if (empty($id) === false) {
        // Get the id
        $id = $this->sanitizeField($id);
        // Shoot the query
        $form = get_post(intval($id));
        return $form;
      } else {
        return false;
      }
    }

    // Validate switch
    private function switchValidation ($r, $field, $errors) {

      // Set errors to false by default
      $errors = false;

      // Start switch/case for validation
      switch ($r->acf_fc_layout):
        // Default validation
        case "text" :
        case "textarea" :
        case "radio" :
        case "select" :

          // Index is given
          // Now check for min or max
          $min = (empty($r->min) === true) ? 0 : intval($r->min);
          $max = (empty($r->max) === true) ? 0 : intval($r->max);

          // Check for min and max
          if (strlen($field) >= $min && strlen($field) >= $max) {
            // Do nothing
          } else {
            if (empty($fieldErrors->characterAmount) === false) $e = $fieldErrors->characterAmount; else $e = "Please check the amount of characters you have entered.";
            return $e;
          }

          break;

        // Number field
        case "number" :

          // Check if it's a number
          if (is_numeric($field) === false) {
            if (empty($fieldErrors->invalidNumber) === false) $e = $fieldErrors->invalidNumber; else $e = "The value is not a number.";
            return $e;
          } else {
            // Index is given
            // Now check for min or max
            $min = (empty($r->min) === true) ? 0 : intval($r->min);
            $max = (empty($r->max) === true) ? 0 : intval($r->max);

            // Convert to integer
            $field = intval($field);

            // Check for min and max
            if ($field >= $min && $field <= $max) {
              // Do nothing
            } else {
              if (empty($fieldErrors->characterAmount) === false) $e = $fieldErrors->characterAmount; else $e = "Please check the amount of characters you have entered.";
              return $e;
            }
          }

          break;

        // URL field
        case "url" :

          // Check if URL
          if ($this->isUrl($field) === false) {
            if (empty($fieldErrors->invalidUrl) === false) $e = $fieldErrors->invalidUrl; else $e = "The value is not a valid url.";
            return $e;
          }

          break;

        // File field
        case "file" :

          // Store the filetypes
          $types = explode(", ", $r->filetypes);

          // Shift array to variable
          $name = $field["name"];

          // Loop
          if (empty($field) === false && empty($field["name"][array_key_first($name)]) === false) {

            // Loop through errors
            foreach ($field["error"] as $e) {

              // Check if filesize is not 0
              // Check if error is 0
              if ($e === 0) {
                // Do nothing
              } else {
                // Return error
                if (empty($fieldErrors->uploadError) === false) $e = $fieldErrors->uploadError; else $e = "There error validating the uploaded file.";
                return $e;
              }
            }

            // Loop through sizes
            foreach ($field["size"] as $s) {
              // Check if filesize is not 0
              // Check if error is 0
              if ($s > 100) {
                // Do nothing
              } else {
                // Return error
                if (empty($fieldErrors->emptyFile) === false) $e = $fieldErrors->emptyFile; else $e = "You've tried to upload an (nearly) empty file.";
                return $e;
              }
            }

            // Loop through
            foreach ($field["name"] as $f) {
              // Set extension
              $extension = "." . pathinfo($f, PATHINFO_EXTENSION);
              // Check if in array, else throw an error
              if (in_array($extension, $types) === false) {
                if (empty($fieldErrors->invalidFileExtension) === false) $e = $fieldErrors->invalidFileExtension; else $e = "File extension that you are trying to upload is not allowed.";
                return $e;
              }
            }

          } else {

            // No file found, don't validate
            return false;
          }

          break;

        // E-mail field
        case "email" :

          // Check if the given variable is a valid e-mail address
          if (filter_var($field, FILTER_VALIDATE_EMAIL) === false) {
            if (empty($fieldErrors->invalidEmail) === false) $e = $fieldErrors->invalidEmail; else $e = "The e-mailaddress given was not valid.";
            return $e;
          }

          break;

        // Phone field
        case "tel" :

          // Check if field is a valid phone number
          if ($this->isPhone($field) === false) {
            if (empty($fieldErrors->invalidPhone) === false) $e = $fieldErrors->invalidPhone; else $e = "The phonenumber you have entered is not valid.";
            return $e;
          }

          break;

        // Checkbox field
        case "checkbox" :

          // Validate two things
          // If not empty
          // If it's an array
          if (is_array($field) === false && empty($field) === true) {
            if (empty($fieldErrors->checkboxError) === false) $e = $fieldErrors->checkboxError; else $e = "The checkbox field did not validate.";
            return $e;
          }

          break;

      endswitch;

      // Return errors
      return $errors;
    }

    // Validate fields function
    public function validateFields (array $request, array $required, $fieldErrors) {

      // Default to false
      $errors = false;

      // Check if loopable
      if (empty($required) === false && empty($request) === false) {

        // Loop through the required array
        foreach ($required as $r) {

          // Store field
          $field = $request[$r->name];

          // Check if required
          if ($r->required === true) {

            // Check if not empty
            if (empty($field) === true) {

              if (empty($fieldErrors->requiredField) === false) $e = $fieldErrors->requiredField; else $e = "This field is required.";

              // Return error
              $errors[] = [
                'field' => $r->name,
                'error' => $e
              ];

            } else {
              if ($this->switchValidation($r, $field, $fieldErrors) !== false) {

                // Return error
                $errors[] = [
                  'field' => $r->name,
                  'error' => $this->switchValidation($r, $field, $fieldErrors)
                ];

              }
            }

          } else {

            // Check if not empty
            if (isset($field) === true) {
              // Not required, but validate
              if ($this->switchValidation($r, $field, $fieldErrors) !== false) {

                // Return error
                $errors[] = [
                  'field' => $r->name,
                  'error' => $this->switchValidation($r, $field, $fieldErrors)
                ];

              }
            }
          }
        }
      }

      // Check if there are errors
      if ($errors === false) return true; else return $errors;
    }

    // Format e-mail addresses
    public function formatTo (array $to) {
      if (empty($to) === false) {
        return $this->implodeField($to, "email", ", ");
      } else {
        return true;
      }
    }

    // Sanitize data
    public function sanitizeData (array $data) {
      $request = false;

      foreach ($data as $key => $req) {
        if (is_array($req) === false) {
          $request[$key] = $this->sanitizeField($req);
        } else {
          foreach ($req as $k => $r) {
            $request[$key][$k] = $this->sanitizeField($r);
          }
        }
      }

      return $request;
    }

    // Replace variable function
    public function replaceVariable (string $string, array $fields) {
      if (empty($string) === false && empty($fields) === false) {

        // Replaceable string
        $replaced = false;
        $return   = false;

        // Loop through the fields
        foreach ($fields as $key => $field) {

          // Set variables
          $search  = "{{ $key }}";
          if (is_array($field) === false) $replace = $field; else $replace = implode(", ", $field);

          // Try to replace
          if ($return !== false) {
            $replaced = str_replace($search, $replace, $return);
          } else {
            $replaced = str_replace($search, $replace, $string);
          }

          // Check if it has been replaced
          if ($replaced !== $string) {
            $return = $replaced;
          }
        }
      }

      return $return;
    }

    // Get form by id
    public function getForm (int $id) {

      // Check if not empty
      if (empty($id) === false) {

        // Set array for return object
        $form_id = $id;
        $data    = [];

        // Add the form action
        $data["fields"]["action"] = (object) [
          "type"  => "hidden",
          "field" => "<input type='hidden' name='action' value='sendMail' />"
        ];

        // Get fields
        $fields = get_fields($form_id);

        // Check if it exists
        if (empty($fields) === false) {

          // Check if multi columns are supported
          if ($fields["multiColumn"] === true) $mc = true; else $mc = false;

          // Loop through the fields
          foreach ($fields["fields"] as $key => $field) {

            // Clear field object
            $f = (object) [];

            // Switch for field types
            switch ($field->acf_fc_layout) :

              // Set up text-field
              case "email" :
              case "number" :
              case "text" :
              case "tel" :
              case "url" :

                // Set variables
                $required = (empty($field->required) === false) ? '' : null;
                $min      = (empty($field->min) === false) ? "min='{$field->min}'" : null;
                $max      = (empty($field->max) === false) ? "max='{$field->max}'" : null;
                $id       = (empty($field->id) === false) ? "id='{$field->id}'" : null;

                // Set field
                $format = "<input type='{$field->acf_fc_layout}' name='{$field->name}' class='{{ classes }}' placeholder='{$field->placeholder}' $required $min $max $id />";
                $format = $this->cleanField($format);

                // Setup field array to be placed in $data array
                $f = (object) [
                  "name"  => $field->name,
                  "type"  => $field->acf_fc_layout,
                  "label" => (empty($field->required) === false) ? $field->label . " *" : $field->label,
                  "field" => $format,
                  "multi" => ($mc === true) ? $field->columns : ''
                ];

                break;

              // Set up text-field
              case "button" :

                // Set field
                $format = "<button type='{$field->type}' class='{{ classes }}'>{$field->text}</button>";
                $format = $this->cleanField($format);

                // Setup field array to be placed in $data array
                $f = (object) [
                  "field" => $format,
                  "type"  => $field->acf_fc_layout,
                  "multi" => ($mc === true) ? $field->columns : ''
                ];

                break;

              case "checkbox" :
              case "radio" :

                // Set variables
                $required = (empty($field->required) === false) ? '' : null;

                // Clean options
                $boxes = [];

                $i = 1;

                // Loop through all options
                foreach ($field->options as $option) {
                  // For id
                  $for = "{$option->value}-{$i}";
                  // Set up variables
                  $o = [];
                  $o["label"] = $option->label;
                  $o["field"] = $this->cleanField("<label for='{$for}'>{$option->label}</label><input id='{$for}' type='{$field->acf_fc_layout}' name='{$field->name}[]' class='{{ classes }}' value='{$option->value}' $required />");
                  // Parse to data
                  $boxes[] = (object) $o;
                }

                // Setup field array to be placed in $data array
                $f = (object) [
                  "name"  => $field->name,
                  "type"  => $field->acf_fc_layout,
                  "label" => (empty($field->required) === false) ? $field->label . " *" : $field->label,
                  "field" => $boxes,
                  "multi" => ($mc === true) ? $field->columns : ''
                ];

                break;

              case "textarea" :

                // Set some variables
                $id       = (empty($field->id) === false) ? "id='{$field->id}'" : null;
                $required = (empty($field->required) === false) ? ' ' : null;
                $format   = "<textarea name='{$field->name}' class='{{ classes }}' placeholder='{$field->placeholder}' $id $required></textarea>";

                // Setup field array to be placed in $data array
                $f = (object) [
                  "name"  => $field->name,
                  "type"  => $field->acf_fc_layout,
                  "label" => (empty($field->required) === false) ? $field->label . " *" : $field->label,
                  "field" => $format,
                  "multi" => ($mc === true) ? $field->columns : ''
                ];

                break;

              case "select" :

                // Set some variables
                $id       = (empty($field->id) === false) ? "id='{$field->id}'" : null;
                $required = (empty($field->required) === false) ? ' ' : null;
                $format   = "<select name='{$field->name}' class='{{ classes }}' $id $required><option value=''>{$field->label}</option>";

                // Loop
                foreach ($field->options as $option) {
                  $o = "<option value='{$option->value}'>{$option->label}</option>";
                  $format .= $o;
                }

                // Close format
                $format .= "</select>";

                // Setup field array to be placed in $data array
                $f = (object) [
                  "name"  => $field->name,
                  "type"  => $field->acf_fc_layout,
                  "label" => (empty($field->required) === false) ? $field->label . " *" : $field->label,
                  "field" => $format,
                  "multi" => ($mc === true) ? $field->columns : ''
                ];

                break;

              case "file" :

                // Set variables
                $id       = (empty($field->id) === false) ? "id='{$field->id}'" : null;
                $required = (empty($field->required) === false) ? ' ' : null;
                $multiple = (empty($field->multiple) === false) ? ' multiple' : null;
                $format   = "<input name='{$field->name}[]' type='{$field->acf_fc_layout}' class='{{ classes }}' accept='{$field->filetypes}' $id $required $multiple />";

                // Setup field array to be placed in $data array
                $f = (object) [
                  "name"  => $field->name,
                  "type"  => $field->acf_fc_layout,
                  "label" => (empty($field->required) === false) ? $field->label . " *" : $field->label,
                  "field" => $format,
                  "multi" => ($mc === true) ? $field->columns : ''
                ];

                break;

            endswitch;

            // Parse to array
            $data["fields"][$field->name] = $f;
          }

          // Add the form id
          $data["fields"]["form_id"] = (object) [
            "type"  => "hidden",
            "field" => "<!-- ID --><input type='hidden' name='form_id' value='{$form_id}' />"
          ];

          // Get random value from the honey array
          $random = $this->honey[(rand(0, (count($this->honey) - 1)))];

          // Add the form id
          $data["fields"]["honeypot"] = (object) [
            "type"  => "hidden",
            "field" => "<!-- Honey --><input type='hidden' class='js-last-field' name='{$random}' />"
          ];

          // Get settings
          $settings = get_fields("forms_settings");

          // Set some setting values
          $data["action"]                  = admin_url("admin-ajax.php");
          $data["recaptcha"]["key_site"]   = $settings["recaptcha"]->key_site;
          $data["recaptcha"]["key_secret"] = $settings["recaptcha"]->key_secret;

          // Return fields
          return (object) $data;

        } else {
          return false;
        }
      } else {
        return false;
      }
    }

    // Parse field function
    public function parseField ($field, string $classes = "") {

      if (empty($field->field) === false) {
        if (is_array($field->field) === true) {
          // Grab field
          $return = $this->implodeField($field->field, "field", " ");

          // Replace string with classes
          $return = str_replace("{{ classes }}", $classes, $return);

        } else {
          // Grab field
          $return = $field->field;

          // Replace string with classes
          $return = str_replace("{{ classes }}", $classes, $return);

        }
        return $return . "\n";
      }
    }

    // Clean field for empty spaces
    private function cleanField (string $field) {
      $search = ["", "  ", "   ", "    "];
      return str_replace($search, " ", $field);
    }

    // Implode field function
    public function implodeField (array $array, string $field, string $glue) {
      return implode("$glue", array_column($array, $field));
    }

    // Function that initializes the script
    public function initialize () {

      // Run function to register the post type
      $this->registerType();

      // Run the function to register the options page
      $this->registerOptions();

      // Run the function to register all fields
      $this->registerFields();
    }
  }

  // =============
  require __DIR__ . "/../../includes/_functions.php";
  require __DIR__ . "/../../includes/_actions.php";

  // Declare new instance of class
  $mailer = new Mailer;

  // Run initialize function to start
  $mailer->initialize();
