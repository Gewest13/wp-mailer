<?php

  // Check if mailer exists
  if (empty($mailer) === false) :

    // Set id
    $id = $component->form;

    // Check if form exists
    if (empty($id) === false && is_numeric($id) === true && get_post($id) !== NULL) :

      // Get the form
      $form = $mailer->getForm($id);

      // Shift array for ease
      $fields = $form->fields;
?>
<div class="component component--dark form">
  <form
    action="<?= $form->action; ?>"
    data-mailer
    data-mailer-site="<?= $form->recaptcha["key_site"]; ?>"
    method="post"
    class="js-form container form__container"
    enctype="multipart/form-data">
    <?php

      // Loop through all the fields
      foreach ($fields as $field) :
        if ($field->type !== "hidden") :
    ?>
      <div class="form__group<?= (isset ($field->multi) && $field->multi === "half") ? " form__group--half" : "" ?>">
        <?php if (empty($field->label) === false) : ?>
        <label for="<?= $field->name; ?>">
          <?= $field->label; ?>
        </label>
        <?php endif; 

          // Switch between field types
          switch ($field->type) :

            // If button
            case "button" :
              echo $mailer->parseField($field, "button form__submit");
              break;

            // If select
            case "select" :
              echo $mailer->parseField($field, "js-select select__hidden");
              break;

            // Else
            default:
              echo $mailer->parseField($field);

          endswitch;
        ?>
      </div>
    <?php else : ?>
      <?= $mailer->parseField($field); ?>
    <?php endif;
        endforeach; ?>
  </form>
</div>
<?php endif; endif; ?>
