<?php

  // Check if mailer exists
  if (empty($mailer) === false) :
    // Set id
    $id = $component->form;
    // Check if form exists
    if (empty($id) === false && is_numeric($id) === true) :

      // Get the form
      $form = $mailer->getForm($id);
?>
<form action="<?= $form->action; ?>" method="post" class="" enctype="multipart/form-data" style="margin: 200px">
<?php
  foreach ($form->fields as $f) :
    echo $mailer->parseField($f);
  endforeach;
?>
</form>
<?php endif; endif;
