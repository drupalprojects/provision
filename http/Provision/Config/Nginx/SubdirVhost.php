<?php

/**
 * Base class for subdir support.
 *
 * This class will publish the config files to remote
 * servers automatically.
 */
class Provision_Config_Nginx_SubdirVhost extends Provision_Config_Http {
  public $template = 'subdir_vhost.tpl.php';
  public $description = 'subdirectory vhost support';

  // hack: because the parent class doesn't support multiple config
  // files, we need to keep track of the alias we're working on.
  protected $current_alias;

  /**
   * Guess the URI this subdir alias is related too.
   */
  function uri() {
    $e = explode('/', $this->current_alias, 2);
    return $e[0];
  }

  /**
   * Guess the subdir part of the subdir alias.
   */
  function subdir() {
    $e = explode('/', $this->current_alias, 2);
    return $e[1];
  }

  /**
   * Check if the parent (domain) site (vhost) exists.
   */
  function parent_site() {
    $vhost_parent_site = $this->data['http_vhostd_path'] . '/' . $this->uri();
    if (provision_file()->exists($vhost_parent_site)->status()) {
      $e = TRUE;
    }
    return $e;
  }

  function write() {
    foreach (d()->aliases as $alias) {
      if (strpos($alias, '/')) {
        $this->current_alias = $alias;
        if ($this->parent_site()) {
          $site_parent = TRUE;
          drush_log(dt('Parent site %vhost already exists for alias %alias, skipping', array('%vhost' => $this->uri(), '%alias' => $alias)), 'notice');
          $site_name = '@' . $this->uri();
          provision_backend_invoke($site_name, 'provision-verify');
        }
        else {
          drush_log("Subdirectory alias `$alias` found. Creating vhost configuration file.", 'notice');
          parent::write();
        }
      }
    }
  }

  function process() {
    parent::process();
    $this->data['uri'] = $this->uri();
    $this->data['subdir'] = $this->subdir();
    $this->data['subdirs_path'] = $this->data['http_subdird_path'];
  }

  function filename() {
    if (!$site_parent) {
      return $this->data['http_vhostd_path'] . '/' . $this->uri();
    }
  }
}