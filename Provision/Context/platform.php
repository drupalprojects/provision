<?php
// $Id$

/**
 * @file Provision named context platform class.
 */


/**
 * Class for the platform context.
 */
class Provision_Context_platform extends Provision_Context {
  public $parent_key = 'server';

  static function option_documentation() {
    return array(
      '--root' => 'platform: path to a Drupal installation',
      '--server' => 'platform: drush backend server; default @server_master',
      '--web_server' => 'platform: web server hosting the platform; default @server_master',
      '--makefile' => 'platform: drush makefile to use for building the platform if it doesn\'t already exist',
    );
  }

  function init_platform() {
    $this->setProperty('root');
    $this->setProperty('makefile', '');
  }
}
