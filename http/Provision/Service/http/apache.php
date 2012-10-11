<?php

class Provision_Service_http_apache extends Provision_Service_http_public {
  protected $application_name = 'apache';
  protected $has_restart_cmd = TRUE;

  function default_restart_cmd() {
    return Provision_Service_http_apache::apache_restart_cmd();
  }

  function default_site_symlinks() {
    return Provision_Service_http_apache::apache_site_symlinks();
  }

  function cloaked_db_creds() {
    return TRUE;
  }

  function init_server() {
    parent::init_server();
    $this->configs['server'][] = 'Provision_Config_Apache_Server';
    $this->configs['platform'][] = 'Provision_Config_Apache_Platform';
    $this->configs['site'][] = 'Provision_Config_Apache_Site';
  }

  /**
   * Guess at the likely value of the http_restart_cmd.
   *
   * This method is a static so that it can be re-used by the apache_ssl
   * service, even though it does not inherit this class.
   */
  public static function apache_restart_cmd() {
    $command = '/usr/sbin/apachectl'; // A proper default for most of the world
    foreach (explode(':', $_SERVER['PATH']) as $path) {
      $options[] = "$path/apache2ctl";
      $options[] = "$path/apachectl";
    }
    // Try to detect the apache restart command.
    $options[] = '/usr/local/sbin/apachectl'; // freebsd
    $options[] = '/usr/sbin/apache2ctl'; // debian + apache2
    $options[] = '/usr/apache2/2.2/bin'; // Solaris
    $options[] = $command;

    foreach ($options as $test) {
      if (is_executable($test)) {
        $command = $test;
        break;
      }
    }

    return "sudo $command graceful";
  }

  /**
   * Restart apache to pick up the new config files.
   */ 
  function parse_configs() {
    return $this->restart();
  }
}
