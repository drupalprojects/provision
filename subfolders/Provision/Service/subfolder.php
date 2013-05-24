<?php

/**
 * @file
 *   The subfolder provision service.
 */
include_once('/var/aegir/.drush/provision/Provision/ChainedState.php');
include_once('/var/aegir/.drush/provision/Provision/Service.php');

/**
 * The subfolder service base class.
 */
class Provision_Service_subfolder extends Provision_Service {
  public $service = 'subfolder';

  # Add the subfolder_path property to the site context.
  static function subscribe_site($context) {
    $context->setProperty('subfolder_path');
  }
}

