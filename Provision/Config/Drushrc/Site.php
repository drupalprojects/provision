<?php
/**
 * @file
 * Provides the Provision_Config_Drushrc_Site class.
 */

/**
 * Class for writing $platform/sites/$url/drushrc.php files.
 */
class Provision_Config_Drushrc_Site extends Provision_Config_Drushrc {
  protected $context_name = 'site';
  public $template = 'provision_drushrc_site.tpl.php';
  public $description = 'Site Drush configuration file';

  function filename() {
    if ($this->site_data_dir != '')
      return $this->site_data_dir . '/drushrc.php';
    }
    else {
      return $this->site_path . '/drushrc.php';
    }
  }
}
