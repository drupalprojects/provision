<?php
// $Id$

/**
 * @file Provision named context site class.
 */

class Provision_Context_site extends Provision_Context {
  public $parent_key = 'platform';

  static function option_documentation() {
    return array(
      'platform' => 'site: the platform the site is run on',
      'db_server' => 'site: the db server the site is run on',
      'uri' => 'site: example.com URI, no http:// or trailing /',
      'language' => 'site: site language; default en',
      'aliases' => 'site: comma-separated URIs',
      'redirection' => 'site: boolean for whether --aliases should redirect; default false',
      'client_name' => 'site: machine name of the client that owns this site',
      'profile' => 'site: Drupal profile to use; default default',
    );
  }

  function init_site() {
    $this->setProperty('uri');

     // we need to set the alias root to the platform root, otherwise drush will cause problems.
    $this->root = $this->platform->root;

    $this->setProperty('site_enabled', true);
    $this->setProperty('language', 'en');
    $this->setProperty('client_name');
    $this->setProperty('aliases', array(), TRUE);
    $this->setProperty('redirection', FALSE);
    $this->setProperty('cron_key', '');

    // set this because this path is accessed a lot in the code, especially in config files.
    if (isset($this->server->http_data_dir)) {
      $data_dir = d()->server->http_data_dir;
      // We'll need an alter hook here
      $this->site_path = _provision_drupal_expand_data_dir_tokens($data_dir);
    }
    else {
      // Default platform path for sites
      $this->site_path = $this->root . '/sites/' . $this->uri;
    }

    // this can potentially be handled by a Drupal sub class
    $this->setProperty('profile', 'default');
  }
}
