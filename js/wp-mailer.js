// WP Mailer class
class WPMailer {

  // Constructor class
  constructor (index) {
    // Dom elements
    this.DOM = {
      form: document.querySelectorAll('form[data-mailer]')[index]
    }

    this.state = {
      fields: [],
      action: this.DOM.form.getAttribute('action'),
      method: this.DOM.form.getAttribute('method'),
    }
  }

  // Submit form function
  submitForm = (e) => {

    // Prevent posting the form
    e.preventDefault();

    // Set up formData object
    this.state.fields = new FormData(this.DOM.form);

    // Get the action url
    const url = this.state.action;

    // Do the request to action url
    const request = new XMLHttpRequest();

    // Send request to ajax url
    request.open('POST', url, true);

    request.onload = () => {

      // Check if the readyState is correct
      if (request.readyState !== 4) return;

      // Get response and decode json
      const json = JSON.parse(request.responseText);

      if (request.status >= 200 && request.status < 300) {
        if (json.success === true) {
          console.log('success')
        }
      }

      if (json.success === false && json.data && json.data.length > 0) {
        let el;

        // loop through data (errors)
        [...json.data].forEach((error) => {
          el = this.DOM.form.querySelector(`[name="${error.field}"]`);

          // add error message below el

        });

      }
    }

    // Send the request to action url
    request.send(this.state.fields);
  }

  // Init
  init = () => {

    // Get the site key
    let site = this.DOM.form.getAttribute('data-mailer-site');

    // Get the last field that's been placed within the field
    let last = this.DOM.form.querySelector('.js-last-field');

    // Add the recaptcha to the form
    grecaptcha.ready(() => {
      grecaptcha.execute(site, {action: 'homepage'}).then((token) => {
        // Add the recaptcha field to the form
        let field = `<input type='hidden' name='recaptcha' value='${token}' />`;
        last.insertAdjacentHTML('beforebegin', field);
      });
    });

    // Add event listener for form post
    this.DOM.form.addEventListener('submit', this.submitForm);
  }
}

// Export
export default WPMailer;

// Get all forms
const formEls = document.querySelectorAll('form[data-mailer]');

// Loop through forms and make instance of class if form exists
if (formEls.length > 0) {
  [...formEls].forEach((el, index) => {
    const mailer = new WPMailer(index);
    mailer.init();
  });
}


// // success true
// {
//   success: true,
//   data: {
//     message: 'your success message.'
//   }
// }
//
// // success false
// {
//   success: false,
//   data: {
//     message: 'server error'
//   }
// }
//
//
// // success false
// {
//   success: false,
//   data: [
//     {
//       field: 'file',
//       error: 'File extension that you are trying to upload is not allowed'
//     },
//     {
//       field: 'name',
//       error: 'min chars 5'
//     }
//   ]
// }
