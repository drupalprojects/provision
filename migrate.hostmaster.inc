<?php

/**
 * @file manual site migrate system
 */
function drush_provision_hostmaster_migrate_validate($site, $platform) {
  // XXX: this doesn't actually document the pre alpha9 upgrade path
  drush_print('
This command will operate the following changes in your system:

1. verify the server and the hostmaster site (provision-verify)
2. verify the new platform (which will download it if missing)
3. stop the queue (hosting-pause)
4. attempt a migration to the new platform (provision-migrate)
5. start the queue (hosting-resume)

We are making the following assumptions:
 * you have read UPGRADE.txt
 * you are executing this script as your "aegir" user
');

  if (!drush_confirm("Do you really want to proceed with the upgrade?")) {
    return drush_set_error('PROVISION_CANCEL_MIGRATE', 'Upgrade canceled by user');
  }

  $data = drush_get_merged_options();

  $alias_file = drush_server_home() . '/.drush/server_master.alias.drushrc.php';

  if (!file_exists($platform)) {
    $dir = realpath(dirname($platform));
    if (is_dir($dir)) {
      $platform = $dir . '/' . basename($platform);
      drush_log("changed relative platform path to absolute");
    }
  }

  provision_backend_invoke('@server_master', 'provision-verify');
  provision_backend_invoke('@hostmaster', 'provision-verify');

  drush_set_option('site_name', '@hostmaster');
  drush_set_option('old_platform', d('@hostmaster')->platform->name);

  $new_platform = '@platform_' . preg_replace("/[!\W]/", "", basename(rtrim($platform, '/')));
  drush_invoke_process('@none', "provision-save", array($new_platform), array(
    'context_type' => 'platform',
    'server' => '@server_master',
    'web_server' => '@server_master',
    'root' => $platform,
    'makefile' => drush_get_option('makefile', dirname(__FILE__) . '/aegir.make'),
  ));
  // propagate working-copy args downward
  $options = array();
  if (drush_get_option('working-copy')) {
    $options['working-copy'] = 1;
  }
  provision_backend_invoke($new_platform, 'provision-verify', array(), $options);
  drush_set_option('new_platform', $new_platform);
  // exit if an error has occured.
  if (drush_get_error()) {
    return false;
  }
}

/**
 * Prepare the hostmaster site for migration
 */
function drush_provision_pre_hostmaster_migrate($site, $platform) {
  // we ignore errors here through integrate = FALSE
  provision_backend_invoke(drush_get_option('site_name'), 'hosting-pause', array(), array('#integrate' => FALSE));
}

function drush_provision_hostmaster_migrate($site, $platform) {
  $options = array();
  if ($db_server = drush_get_option('new_db_server', FALSE)) {
    $options['new_db_server'] = $db_server;
  }
  provision_backend_invoke(drush_get_option('site_name'), 'provision-migrate', array(drush_get_option('new_platform')), $options);
}

function drush_provision_post_hostmaster_migrate($site, $platform) {
  provision_backend_invoke(drush_get_option('site_name'), 'cache-clear', array('drush'));
  // we pass the context names we generated to the task so we can enforce that the names
  // stay the same.
  $options = array(
    'old_platform_name' => drush_get_option('old_platform'),
    'new_platform_name' => drush_get_option('new_platform'),
  );
  // We pass the new database server to the front-end, to update the hostmaster
  // site node.
  if ($db_server = drush_get_option('new_db_server', FALSE)) {
    $options['new_db_server'] = $db_server;
  }
  provision_backend_invoke(drush_get_option('site_name'), 'hosting-resume', array(), $options);
}

