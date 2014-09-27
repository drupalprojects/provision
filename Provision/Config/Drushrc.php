<?php
/**
 * @file
 * Provides the Provision_Config_Drushrc class.
 */

/**
 * Specialized class to handle the creation of drushrc.php files.
 *
 * This is based on the drush_save_config code, but has been abstracted
 * for our purposes.
 */
class Provision_Config_Drushrc extends Provision_Config {
  public $template = 'provision_drushrc.tpl.php';
  public $description = 'Drush configuration file';
  protected $mode = 0440;
  protected $entity_name = 'drush';

  function filename() {
    return _drush_config_file($this->entity_name);
  }

  function __construct($entity, $data = array()) {
    parent::__construct($entity, $data);
    $this->load_data();
  }

  function load_data() {
    // we fetch the entity to pass into the template based on the entity name
    $this->data = array_merge(drush_get_context($this->entity_name), $this->data);
  }

  function process() {
    unset($this->data['entity-path']);
    unset($this->data['config-file']);
    $this->data['option_keys'] = array_keys($this->data);
  }
}
