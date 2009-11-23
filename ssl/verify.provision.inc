<?php

// $Id$

function drush_provision_ssl_post_provision_verify($url = NULL) {
  if (PROVISION_CONTEXT_SITE) {
    drush_set_option('ssl', drush_get_option('ssl'), 'site');
    drush_set_option('ssl_wildcard', drush_get_option('ssl_wildcard'), 'site');
  }
}
