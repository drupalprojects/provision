<?php

/**
 * The site_data service class.
 */
class Provision_Service_site_data extends Provision_Service {
  public $service = 'site_data';

  /**
   * Add the needed properties to the site entity.
   */
  static function subscribe_site($entity) {
    $entity->setProperty('site_data');
  }
}
