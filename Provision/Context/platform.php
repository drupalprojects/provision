<?php

/**
 * @file Provision named entity platform class.
 */


/**
 * Class for the platform entity.
 */
class Provision_Entity_platform extends Provision_Entity {
  public $parent_key = 'server';

  static function option_documentation() {
    return array(
      'root' => 'platform: path to a Drupal installation',
      'server' => 'platform: drush backend server; default @server_master',
      'web_server' => 'platform: web server hosting the platform; default @server_master',
      'makefile' => 'platform: drush makefile to use for building the platform if it doesn\'t already exist',
      'make_working_copy' => 'platform: Specifiy TRUE to build the platform with the Drush make --working-copy option.',
    );
  }

  function init_platform() {
    $this->setProperty('root');
    $this->setProperty('makefile', '');
    $this->setProperty('make_working_copy', FALSE);
  }
}
