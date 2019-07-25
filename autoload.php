<?php

  // Set namespace
  namespace Mailer;

  // =============
  // This class uses array to object conversion of ACF fields
  // Please leave this function included
  // =============
  require __DIR__ . "/includes/_functions.php";
  require __DIR__ . "/includes/_actions.php";
  require "Mailer.php";

  // Declare new instance of class
  $mailer = new Mailer;

  // Run initialize function to start
  $mailer->initialize();
