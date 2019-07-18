# Wordpress Mailer
This is a Wordpress module developed by Jim de Ronde ([Gewest13](https://www.gewest13.nl)).

## Prerequisites
- ACF Pro

## Installation
 1. Clone the respository in your `server/` directory
 2. Do a `componser install` inside the folder
 3. Require or include the `server/wp-mailer/autoload.php` into your `functions.php` file.
 4. Fill in all required information within the `Settings` page.
 5. Create a new form and add any field to your liking.
 6. Add the `/sample-component/` to your list of components

## Functions

  Always check if `$mailer` is a valid instance of the `Mailer` class.

  1. Use `$mailer->getForm($id)` to get all form fields.
  2. Then, use a `foreach` loop to loop through all fields. `foreach ($mailer->getForm($id) as $name => $field)`
  3. Inside the loop the `$field` variable will contain the entire html element so `echo` it. The `$name` variable will hold the field name as setup in Wordpress.

## To do

  1. Javascript integration (AJAX)
  2. Validation types for e-mail and phone
  3. Add files (attachments)
  4. Replace string in message for arrays (checkbox)
  5. Add ReCAPTCHA
