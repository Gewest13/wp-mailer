# Wordpress Mailer
This is a Wordpress module developed by Jim de Ronde ([Gewest13](https://www.gewest13.nl)).

## Prerequisites
- ACF Pro

## Installation

  Do this first.

  1. Clone the repository in your `server/` directory.
  2. Do a `composer install` inside the folder.
  3. Require or include the `server/wp-mailer/autoload.php` into your `functions.php` file.

  When this is done a first form can be added.

  4. Fill in all required information within the `Settings` page.
  5. Create a new form and add any field to your liking.
  6. Add the `/sample-component/` to your list of components/. Please note that the form will require some predefined settings like an `action`, `data-wp-mailer` and `method`.
  7. At last, please include `wp_footer()` before the ending of your `</body>` tag. Please not that the entire function will be cleared and will only return the javascript file that will take care of the asynchronous requests.
  8. If desired, add the following rule to your `.css` file in order to hide the badge: `.grecaptcha-badge {display: none}`

## Functions

  Always check if `$mailer` is a valid instance of the `Mailer` class.

  1. Use `$mailer->getForm($id)` to get all form fields.
  2. Then, use a `foreach` loop to loop through all fields. `foreach ($mailer->getForm($id) as $name => $field)`.
  3. Inside the loop the `$field->field` variable will contain the entire html element so use `$mailer->parseField($field->field)` to parse it.
  4. Label can be accessed through `$field->label` and the name can be accessed through `$field->name`.

## To do

  1. Javascript integration (AJAX)
  2. Remove classes from form page (fields) and add them manually
