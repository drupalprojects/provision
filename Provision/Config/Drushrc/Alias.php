<?php
/**
 * @file
 * Provides the Provision_Config_Drushrc_Alias class.
 */

/**
 * Class to write an alias records.
 */
class Provision_Config_Drushrc_Alias extends Provision_Config_Drushrc {
  public $template = 'provision_drushrc_alias.tpl.php';

  /**
   * @param $name
   *   String '\@name' for named entity.
   * @param $options
   *   Array of string option names to save.
   */
  function __construct($entity, $data = array()) {
    parent::__construct($entity, $data);
    $this->data = array(
      'aliasname' => ltrim($entity, '@'),
      'options' => $data,
    );
  }

  function filename() {
    return drush_server_home() . '/.drush/' . $this->data['aliasname'] . '.alias.drushrc.php';
  }
}
