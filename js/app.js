class Form {
  constructor(index) {
    this.DOM = {
      form: document.querySelectorAll('.js-form')[index]
    };

    this.state = {
      fields: [],
      action: this.DOM.form.getAttribute('action'),
      method: this.DOM.form.getAttribute('method'),
      siteKey: this.DOM.form.getAttribute('data-mailer-site')
    };

    this.request = null;
  }

  submit = (e) => {
    // prevent from posting the form
    e.preventDefault();

    this.state.fields = new FormData(this.DOM.form);

    this.request = new XMLHttpRequest();
    this.request.open(this.state.method, this.state.action, true);

    this.request.onload = () => {
      if (this.request.readyState !== 4) return;

      // clear validation errors from previous submit
      const errorEls = this.DOM.form.querySelectorAll('.form__error')
      if (errorEls.length > 0) [...errorEls].forEach((el) => el.remove());

      const json = JSON.parse(this.request.responseText);

      if (this.request.status >= 200 && this.request.status < 300) {
        if (json.success) {
          console.log('success', json);
        }

        if (!json.success) {
          console.log('error', json);

          if (json.data && json.data.length > 0) {
            let el;

            // loop through data (errors)
            [...json.data].forEach((error) => {
              // this is where the validation errors logic happens

              // get the field that has a validation error
              el = this.DOM.form.querySelector(`[name="${error.field}"]`);

              console.log(el);
            });
          } else {
            // server error

            console.log('error', json);
          }
        }
      }
    };

    this.request.send(this.state.fields);
  }

  init() {
    // add recaptcha to the form
    window.grecaptcha.ready(() => {
      window.grecaptcha.execute(this.state.siteKey, { action: 'homepage' }).then((token) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'recaptcha';
        input.value = token;

        this.DOM.form.insertBefore(input, this.DOM.form.lastElementChild);
      });
    });

    this.DOM.form.addEventListener('submit', this.submit);
  }
}

export default Form;
