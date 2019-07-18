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

    // Set constructor function for class
    function __construct () {

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
        "capability_type"     => "page",
        "show_in_rest"        => false,
      ];

      // Options page options
      $this->options = [
        "page_title"      => "Settings",
        "menu_title"      => "Settings",
        "menu_slug"       => "forms_settings",
        "capability"      => "edit_posts",
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
        "settings" => __DIR__ . "/fields/settings.json",
        "fields"   => __DIR__ . "/fields/fields.json"
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
    public function checkRequired (array $fields) {
      if (empty($fields) === false) {
        // Set empty array
        $required = [];
        // Loop through all fields given and check if there are fields that are required
        foreach ($fields as $key => $field) {
          // Check if field is required, if so, parse to required field
          if ($field->required === true) {
            $required[] = $fields[$key];
          }
        }
        // Return
        return $required;
      } else {
        return false;
      }
    }

    // Get post function
    public function getFormPost (string $id) {
      if (empty($id) === false) {
        // Get the id
        $id = sanitize($id);
        // Shoot the query
        $form = get_post(intval($id));
        return $form;
      } else {
        return false;
      }
    }

    // Validate fields function
    public function validateFields (array $request, array $required) {

      $errors = false;

      if (empty($required) === false && empty($request) === false) {
        // Loop through the required array
        foreach ($required as $r) {
          // Check if corresponding index in $request is given
          if (isset($request[$r->name]) === true) {

            $field = $request[$r->name];

            // Index is given
            // Now check for min or max
            $min = (empty($r->min) === true) ? 0 : $r->min;
            $max = (empty($r->max) === true) ? 0 : $r->max;

            // Check for min and max
            if (strlen($field) >= $min && strlen($field) >= $max) {
              // Do nothing
            } else {
              $errors[$r->name] = "Please check the amount of characters you have entered";
            }

          } else {
            $errors[$r->name] = "Field is not given";
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
          $request[$key] = sanitize($req);
        } else {
          foreach ($req as $k => $r) {
            $request[$key][$k] = sanitize($r);
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
        $data["fields"][] = (object) [
          "field" => "<input type='hidden' name='action' value='sendMail' />"
        ];

        // Get fields
        $fields = get_fields($form_id);
        if (empty($fields) === false) {
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
                $required = (empty($field->required) === false) ? 'required' : null;
                $min      = (empty($field->min) === false) ? "min='{$field->min}'" : null;
                $max      = (empty($field->max) === false) ? "max='{$field->max}'" : null;
                $id       = (empty($field->id) === false) ? "id='{$field->id}'" : null;

                // Set field
                $format = "<input type='{$field->acf_fc_layout}' name='$field->name' class='$field->classes' placeholder='$field->placeholder' $required $min $max $id />";
                $format = $this->cleanField($format);

                // Setup field array to be placed in $data array
                $f = (object) [
                  "name"  => $field->name,
                  "label" => $field->label,
                  "field" => $format
                ];

                break;

              // Set up text-field
              case "button" :

                // Set field
                $format = "<button type='{$field->type}' class='{$field->classes}'>{$field->text}</button>";
                $format = $this->cleanField($format);

                // Setup field array to be placed in $data array
                $f = (object) [
                  "field" => $format
                ];

                break;

              case "checkbox" :
              case "radio" :

                // Set variables
                $required = (empty($field->required) === false) ? 'required' : null;

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
                  $o["field"] = $this->cleanField("<label for='{$for}'>{$option->label}</label><input id='{$for}' type='{$field->acf_fc_layout}' name='{$field->name}[]' class='{$field->classes}' value='{$option->value}' $required />");
                  // Parse to data
                  $boxes[] = (object) $o;
                }

                // Setup field array to be placed in $data array
                $f = (object) [
                  "name"  => $field->name,
                  "label" => $field->label,
                  "field" => $boxes
                ];

                break;

              case "textarea" :

                // Set some variables
                $id       = (empty($field->id) === false) ? "id='{$field->id}'" : null;
                $required = (empty($field->required) === false) ? ' required' : null;
                $format   = "<textarea name='{$field->name}' class='{$field->classes}' placeholder='{$field->placeholder}' $id $required></textarea>";

                // Setup field array to be placed in $data array
                $f = (object) [
                  "name"  => $field->name,
                  "label" => $field->label,
                  "field" => $format
                ];

                break;

              case "select" :

                // Set some variables
                $id       = (empty($field->id) === false) ? "id='{$field->id}'" : null;
                $required = (empty($field->required) === false) ? ' required' : null;
                $format   = "<select name='{$field->name}' class='{$field->classes}' $id $required><option value=''>{$field->label}</option>";

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
                  "label" => $field->label,
                  "field" => $format
                ];

                break;

              case "file" :

                // Set variables
                $id       = (empty($field->id) === false) ? "id='{$field->id}'" : null;
                $required = (empty($field->required) === false) ? ' required' : null;
                $multiple = (empty($field->multiple) === false) ? ' multiple' : null;
                $format   = "<input name='{$field->name}' type='{$field->acf_fc_layout}' class='{$field->classes}' accept='{$field->filetypes}' $id $required $multiple />";

                // Setup field array to be placed in $data array
                $f = (object) [
                  "name"  => $field->name,
                  "label" => $field->label,
                  "field" => $format
                ];

                break;

            endswitch;

            // Parse to array
            $data["fields"][] = $f;
          }

          // Add the form id
          $data["fields"][] = (object) [
            "field" => "<input type='hidden' name='form_id' value='{$form_id}' />"
          ];

          // Add the form id
          $data["fields"][] = (object) [
            "field" => "<input type='hidden' name='credit_nr' value='' />"
          ];

          // Set some setting values
          $data["settings"] = get_fields("forms_settings");
          $data["action"]   = admin_url("admin-ajax.php");

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
    public function parseField ($field) {
      if (isset($field->field) === true) {
        if (is_array($field->field) === true) {
          $return = $this->implodeField($field->field, "field", " ");
        } else {
          $return = $field->field;
        }
        return $return;
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
