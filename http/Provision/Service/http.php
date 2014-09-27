<?php

// Base http service class. 
class Provision_Service_http extends Provision_Service {
  public $service = 'http';
  protected $ssl_enabled = FALSE;


  /**
   * Support the ability to cloak the database credentials using environment variables.
   */
  function cloaked_db_creds() {
    return FALSE;
  }


  function verify_server_cmd() {
    $this->create_config($this->entity->type);
    $this->parse_configs();
  }

  function verify_platform_cmd() {
    $this->create_config($this->entity->type);
    $this->parse_configs();
  }

  function verify_site_cmd() {
    $this->create_config($this->entity->type);
    $this->parse_configs();
  }


  /**
   * Register the http handler for platforms, based on the web_server option.
   */
  static function subscribe_platform($entity) {
    $entity->setProperty('web_server', '@server_master');
    $entity->is_oid('web_server');
    $entity->service_subscribe('http', $entity->web_server->name);
  }

}
