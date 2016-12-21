<?php
// $Id$
/**
 *  @file
 *    Rebuild all the caches
 */

// Pinched from Drush 8.

use Drush\Log\LogLevel;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

chdir(DRUPAL_ROOT);
// Clear the APC cache to ensure APC class loader is reset.
if (function_exists('apc_fetch')) {
  apc_clear_cache('user');
}
// Clear user cache for all major platforms.
$user_caches = [
  'apcu_clear_cache',
  'wincache_ucache_clear',
  'xcache_clear_cache',
];
array_map('call_user_func', array_filter($user_caches, 'is_callable'));
$autoloader = drush_drupal_load_autoloader(DRUPAL_ROOT);
require_once DRUSH_DRUPAL_CORE . '/includes/utility.inc';
$request = Request::createFromGlobals();
// Ensure that the HTTP method is set, which does not happen with Request::createFromGlobals().
$request->setMethod('GET');
// Manually resemble early bootstrap of DrupalKernel::boot().
require_once DRUSH_DRUPAL_CORE . '/includes/bootstrap.inc';
DrupalKernel::bootEnvironment();
// Avoid 'Only variables should be passed by reference'
$root  = DRUPAL_ROOT;
$site_path = DrupalKernel::findSitePath($request);
Settings::initialize($root, $site_path, $autoloader);
// Use our error handler since _drupal_log_error() depends on an unavailable theme system (ugh).
set_error_handler('drush_error_handler');
// drupal_rebuild() calls drupal_flush_all_caches() itself, so we don't do it manually.
drupal_rebuild($autoloader, $request);
drush_log(dt('Cache rebuild complete.'), LogLevel::OK);

