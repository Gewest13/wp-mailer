<?php

  // Check if mailer exists
  if (empty($mailer) === false) :

    // Set id
    $id = $component->form;

    // Check if form exists
    if (empty($id) === false && is_numeric($id) === true) :

      // Get the form
      $form = $mailer->getForm($id);

      // Shift array for ease
      $fields = $form->fields;
?>
<form
  action="<?= $form->action; ?>"
  data-mailer
  data-mailer-site="<?= $form->recaptcha["key_site"]; ?>"
  method="post"
  class="js-form"
  enctype="multipart/form-data">

  <!-- Add the action -->
  <?= $mailer->parseField($fields["action"]) ?>

  <div class="component component--dark form">
    <div class="container form__container">

      <div class="form__group">
        <label for=""><?= $fields["firstName"]->label; ?></label>
        <?= $mailer->parseField($fields["firstName"]) ?>
      </div>

      <div class="form__group form__group--half">
        <label for=""><?= $fields["lastName"]->label; ?></label>
        <?= $mailer->parseField($fields["lastName"]) ?>
      </div>

      <div class="form__group form__group--half">
        <label for=""><?= $fields["email"]->label; ?></label>
        <?= $mailer->parseField($fields["email"]) ?>
      </div>

      <div class="form__group form__group--half">
        <label for=""><?= $fields["interests"]->label; ?></label>
        <?= $mailer->parseField($fields["interests"], "js-select select__hidden") ?>
      </div>

      <div class="form__group">
        <label for=""><?= $fields["phone"]->label; ?></label>
        <?= $mailer->parseField($fields["phone"]) ?>
      </div>

      <div class="form__group">
        <label for=""><?= $fields["company"]->label; ?></label>
        <?= $mailer->parseField($fields["company"]) ?>
      </div>

      <div class="form__group">
        <label for=""><?= $fields["message"]->label; ?></label>
        <?= $mailer->parseField($fields["message"]) ?>
      </div>

      <div class="form__group">
        <?= $mailer->parseField($fields["button"], "button form__submit") ?>
      </div>
    </div>
  </div>

  <!-- Last fields: Form ID and Honeypot -->
  <?= $mailer->parseField($fields["form_id"]) ?>
  <?= $mailer->parseField($fields["honeypot"]) ?>
</form>
<?php endif; endif;
