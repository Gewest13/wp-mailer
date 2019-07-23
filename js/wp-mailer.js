// WP Mailer class
class WPMailer {

  // Constructor class
  constructor () {
    // Dom elements
    this.dom = {
      forms: document.querySelectorAll('form[data-mailer]')
    }
  }

  // Submit form function
  submitForm = (event) => {

    // Prevent posting the form
    event.preventDefault();

    // Format the string in order to post correctly
    // Get the action url
    let action = event.srcElement.getAttribute('action');

    // Format the url
    let string = this.formatString(event.srcElement);

    // Setup the url for the ajax request
    let url = `${action}${string}`;

    // Set up the url
    if (url !== null || url !== "") {
      
      this.makeRequest(url);
      // console.log(url);
    }
  }

  // Ajax request function
  makeRequest = (url) => {

    // Check if given
    if (url !== null) {

      // DO request
      fetch(url)
      .then(
        function(response) {
          console.log(response);
        }
      )
      .catch(function(err) {
        console.log(err);
      });
    }
  }

  // formatString for ajax request
  formatString = (form) => {

    // Begin the variable
    let string = "";

    // Check if not null
    if (form !== null) {

      // Loop through the form elements
      [...form.elements].forEach((input) => {
        // Add name and value
        if (string !== "") {
          string += `&${input.name}=${input.value}`;
        } else {
          string += `?${input.name}=${input.value}`;
        }
      });
    }

    // Check and return
    if (string !== "") {
      return string;
    } else {
      return false;
    }
  }

  // Init
  init = () => {
    // Check if there are any forms within the dom
    if (this.dom.forms.length > 0) {

      // There are forms
      this.dom.forms.forEach((form) => {

        // Get the site key
        let site = form.getAttribute('data-mailer-site');

        // Get the last field that's been placed within the field
        let last = form.querySelector('.js-last-field');

        // Add the recaptcha to the form
        grecaptcha.ready(function() {
          grecaptcha.execute(site, {action: 'homepage'}).then(function(token) {
            // Add the recaptcha field to the form
            let field = `<input type='hidden' name='recaptcha' value='${token}' />`;
            last.insertAdjacentHTML('beforebegin', field);
          });
        });

        // Add event listener for form post
        form.addEventListener('submit', this.submitForm);

      });
    }
  }
}

// Export
export default WPMailer;

// Initialize
let Mailer = new WPMailer;
Mailer.init();
