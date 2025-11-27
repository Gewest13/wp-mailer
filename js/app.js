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

  submit = async (e) => {
    // prevent from posting the form
    e.preventDefault();

    let recaptchaToken;

    try {
      recaptchaToken = await this.getRecaptchaToken();
    } catch (err) {
      console.error('Recaptcha error', err);
      return;
    }

    this.upsertRecaptchaField(recaptchaToken);

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

  getRecaptchaToken() {
    return new Promise((resolve, reject) => {
      if (!window.grecaptcha || !this.state.siteKey) {
        reject(new Error('Missing recaptcha configuration'));
        return;
      }

      window.grecaptcha.ready(() => {
        window.grecaptcha
          .execute(this.state.siteKey, { action: 'homepage' })
          .then(resolve)
          .catch(reject);
      });
    });
  }

  upsertRecaptchaField(token) {
    if (!token) return;

    let recaptchaInput = this.DOM.form.querySelector('input[name="recaptcha"]');

    if (!recaptchaInput) {
      recaptchaInput = document.createElement('input');
      recaptchaInput.type = 'hidden';
      recaptchaInput.name = 'recaptcha';
      this.DOM.form.insertBefore(recaptchaInput, this.DOM.form.lastElementChild);
    }

    recaptchaInput.value = token;
  }

  init() {
    this.DOM.form.addEventListener('submit', this.submit);
  }
}

export default Form;
