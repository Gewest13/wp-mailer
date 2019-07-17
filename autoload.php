<?php

  // =============
  // This class uses array to object conversion of ACF fields
  // Please leave this function included
  // =============
  require "_functions.php";


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
      	"menu_slug"       => "",
      	"capability"      => "edit_posts",
      	"position"        => false,
      	"parent_slug"     => "edit.php?post_type=forms",
      	"redirect"        => true,
      	"post_id"         => "forms-settings",
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

  // Declare new instance of class
  $mailer = new Mailer;

  // Run initialize function to start
  $mailer->initialize();

  // exit;
