# Wordpress Mailer
This is a Wordpress module developed by Jim de Ronde ([Gewest13](https://www.gewest13.nl)).

## Prerequisites
- ACF Pro

## Installation

  Do this first.

  1. Clone the respository in your `server/` directory.
  2. Do a `componser install` inside the folder.
  3. Require or include the `server/wp-mailer/autoload.php` into your `functions.php` file.

  When this is done a first form can be added.

  4. Fill in all required information within the `Settings` page.
  5. Create a new form and add any field to your liking.
  6. Add the `/sample-component/` to your list of components/

## Functions

  Always check if `$mailer` is a valid instance of the `Mailer` class.

  1. Use `$mailer->getForm($id)` to get all form fields.
  2. Then, use a `foreach` loop to loop through all fields. `foreach ($mailer->getForm($id) as $name => $field)`.
  3. Inside the loop the `$field->field` variable will contain the entire html element so use `$mailer->parseField($field->field)` to parse it.
  4. Label can be accessed through `$field->label` and the name can be accessed through `$field->name`.

## To do

  1. Javascript integration (AJAX)
  2. Validation types for e-mail / phone / file
  3. Add files (attachments)
  5. Add ReCAPTCHA
