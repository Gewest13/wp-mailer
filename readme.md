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
 6. Use the `get_form($id)` function in order to retrieve the object that returns all fields and it's options.
