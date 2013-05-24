<?php

/**
 * @file
 *   The subfolder provision service.
 */

/**
 * Expose the service type this extension defines to provision.
 *
 * @return
 *   An array with the service type the key, and the default implementation the value.
 */
function subfolder_provision_services() {
  return array('subfolder' => NULL);
}

/**
 * The subfolder service base class.
 */
class provisionService_subfolder extends provisionService {
  public $service = 'subfolder';

  # Add the subfolder_path property to the site context.
  static function subscribe_site($context) {
    $context->setProperty('subfolder_path');
  }
}

/**
 * Wrapper function which is called by several drush hooks
 */
function drush_subfolder_create_all() {
  drush_subfolder_create_stub_dir();
  drush_subfolder_create_subfolder_symlink();
  drush_subfolder_create_alias_file();
  drush_subfolder_create_stub_file();
}

/**
 * Implementation of drush_hook_pre_provision_install()
 */
function drush_subfolder_provision_install() {
  drush_subfolder_create_all();
}

/**
 * Implementation of drush_hook_pre_provision_verify()
 */
function drush_subfolder_provision_verify() {
  drush_subfolder_create_all();
}

/**
 * Implementation of drush_hook_post_provision_deploy()
 */
function drush_subfolder_provision_deploy() {
  drush_subfolder_create_all();
}

/**
 * Implementation of drush_hook_post_provision_migrate()
 */
function drush_subfolder_post_provision_migrate() {
  drush_subfolder_delete_subfolder_symlink();
}

/**
 * Implementation of drush_hook_provision_disable()
 */
function drush_subfolder_provision_disable() {
  drush_subfolder_disable_stub_file();
}

/**
 * Implementation of drush_hook_provision_enable()
 */
function drush_subfolder_provision_enable() {
  drush_subfolder_create_stub_file();
}

/**
 * Implementation of drush_hook_provision_delete()
 */
function drush_subfolder_provision_delete() {
  drush_subfolder_delete_subfolder_symlink();
  drush_subfolder_delete_alias_file();
  drush_subfolder_delete_stub_file();
}

/**
 * Create a stub.d directory in the /var/aegir/config area that is *not*
 * pre-loaded/included in the server-wide context, but is included by
 * our platform's Apache config at the right time.
 *
 * The stub content would otherwise be injected directly through
 * subfolder_provision_apache_dir_config(), however we want to separate
 * these per subsite for clarity, and we only want to call RewriteEngine
 * once within an <IfModule> wrapper. Why? I don't know, but it works,
 * and it means people can continue to make other changes to their
 * .htaccess if necessary.
 */
function drush_subfolder_create_stub_dir() {
  $stubdir = d()->server->config_path . '/apache/stub.d';
  if (!provision_file()->exists($stubdir)->status()) {
    provision_file()->create_dir($stubdir, dt('Stub file'), 0700);
  }
}

/**
 * Create the symlink necessary for multisite subfolders to work
 *
 * Symlinks the subfolder name into the top-level of the Drupal root
 * itself.
 */
function drush_subfolder_create_subfolder_symlink() {
  if (d()->type == 'site') {
    if (!empty(d()->subfolder_path)) {
      provision_file()->symlink(d()->root, d()->subfolder_path)
        ->succeed('Created symlink @path to @target')
        ->fail('Could not create symlink @path to @target: @reason');
    }
  }
}

/**
 * Delete a subfolder symlink in a platform root.
 *
 * Typically would be used for post migrations, where the old
 * platform should no longer contain the subfolder symlink, 
 * and also when a site is deleted altogether.
 */
function drush_subfolder_delete_subfolder_symlink() {
  if (d()->type == 'site') {
    if (!empty(d()->subfolder_path)) {
      provision_file()->unlink(d()->root . '/' . d()->subfolder_path)
        ->succeed('Deleted symlink @path')
        ->fail('Could not delete symlink @path: @reason');
    }
  }
}

/**
 * Inject a snippet that instructs the platform-level Apache config to
 * include our special RewriteRules per server across this platform,
 * *before* the .htaccess is included.
 *
 * Order seems to be important here - the only other option is to hack
 * the .htaccess directly otherwise.
 */
function subfolder_provision_apache_dir_config($data = null) {
  $stubdir = d()->server->config_path . '/apache/stub.d';
  $lines = array();
  $lines[] = '<IfModule mod_rewrite.c>';
  $lines[] = '  RewriteEngine On';
  $lines[] = '  Include ' . $stubdir . '/*.stub';
  $lines[] = '</IfModule>';
  return implode($lines, "\n");
}

/**
 * Nasty: stick an Alias one-liner for this site in a server-wide config.
 *
 * This can't go in the site's .stub file along with its Rewrite rules,
 * because that .stub file gets Included within a <Directory> directive,
 * which can't contain Alias directives.
 *
 * I think it also needs to be included *before* the pre.d etc. Otherwise,
 * I would've put it in the server-wide apache config via the API using
 * hook_provision_apache_server_config(), but that only gets called on
 * Server verification, and we need it to happen in the site context.
 */
function drush_subfolder_create_alias_file() {
  if (d()->type == 'site') {
    if (!empty(d()->subfolder_path)) {
      $alias_stub = d()->server->http_pred_path . '/000_' . d()->subfolder_path . '_alias.conf';
      provision_file()->file_put_contents($alias_stub, 'Alias /' . d()->subfolder_path . ' ' . d()->root)
        ->succeed('Generated config ' . $alias_stub, 'success')
        ->fail('Could not generate ' . $alias_stub)->status();
    }
  }
}

/**
 * Delete an Apache mod_alias stub file
 */
function drush_subfolder_delete_alias_file() {
  if (d()->type == 'site') {
    if (!empty(d()->subfolder_path)) {
      $alias_file = d()->server->http_pred_path . '/000_' . d()->subfolder_path . '_alias.conf';
      provision_file()->unlink($alias_file)
        ->succeed('Deleted Apache alias stub file @path.')
        ->fail('Deleted Apache alias stub file @path.');
    }
  }
}

/**
 * Nasty hack that will insert our RewriteRule overrides per site into
 * a .stub file which the platform-level Apache config includes.
 */
function drush_subfolder_create_stub_file() {
  $lines = array();
  if (d()->type == 'site') {
    if (!empty(d()->subfolder_path)) {
      # Inject the database credentials based on Request URI, since we can't rely on the Host header.
      # The only alternative is to disable cloaking of db credentials altogether.
      $lines[] = 'SetEnvIf Request_Uri ^/'. d()->subfolder_path . '/ db_type=' . drush_get_option('db_type');
      $lines[] = 'SetEnvIf Request_Uri ^/'. d()->subfolder_path . '/ db_name=' . drush_get_option('db_name');
      $lines[] = 'SetEnvIf Request_Uri ^/'. d()->subfolder_path . '/ db_user=' . drush_get_option('db_user');
      $lines[] = 'SetEnvIf Request_Uri ^/'. d()->subfolder_path . '/ db_passwd=' . drush_get_option('db_passwd');
      $lines[] = 'SetEnvIf Request_Uri ^/'. d()->subfolder_path . '/ db_host=' . drush_get_option('db_host');
      $lines[] = 'SetEnvIf Request_Uri ^/'. d()->subfolder_path . '/ db_port=' . drush_get_option('db_port');

      # This is the magic that makes the subfolders work, if the symlink is also present in the doc root
      # It's an override of the default .htaccess
      $lines[] = 'RewriteCond %{REQUEST_FILENAME} !-f';
      $lines[] = 'RewriteCond %{REQUEST_FILENAME} !-d';
      $lines[] = 'RewriteCond %{REQUEST_URI} !=/favicon.ico';
      $lines[] = 'RewriteCond %{REQUEST_URI} ^/' . d()->subfolder_path;

      # Drupal 7 has a different RewriteRule than Drupal 6 and earlier.
      if (drush_drupal_major_version() <= 6) {
        $lines[] = 'RewriteRule ^(.*)$ /' . d()->subfolder_path . '/index.php?q=$1 [L,QSA]';
      }
      else {
        $lines[] = 'RewriteRule ^ /' . d()->subfolder_path . '/index.php [L]';
      }

      # Write out our stub 
      $stubfile = d()->server->config_path . '/apache/stub.d/' . d()->subfolder_path . '.stub';
      provision_file()->file_put_contents($stubfile, implode($lines, "\n"))
        ->succeed('Generated subfolder RewriteRule stub file ' . $stubfile, 'success')
        ->fail('Could not generate ' . $stubfile)->status();
    }
  }
}

/**
 * Inject the 'disabled site' rewrite rule into our stub
 * so the site is disabled under its 'subfolder' URL.
 */
function drush_subfolder_disable_stub_file() {
  $lines = array();
  if (d()->type == 'site') {
    if (!empty(d()->subfolder_path)) {
      $lines[] = 'RewriteCond %{REQUEST_URI} ^/' . d()->subfolder_path . '(.*)';
      $lines[] = 'RewriteRule ^(.*)$ ' . d()->server->web_disable_url . '/' . d()->uri . '? [L]';

      # Write out our stub 
      $stubfile = d()->server->config_path . '/apache/stub.d/' . d()->subfolder_path . '.stub';
      provision_file()->file_put_contents($stubfile, implode($lines, "\n"))
        ->succeed('Generated subfolder RewriteRule stub file ' . $stubfile, 'success')
        ->fail('Could not generate ' . $stubfile)->status();
    }
  }
}

/**
 * Delete a RewriteRule stub file
 */
function drush_subfolder_delete_stub_file() {
  if (d()->type == 'site') {
    if (!empty(d()->subfolder_path)) {
      $stubfile = d()->server->config_path . '/apache/stub.d/' . d()->subfolder_path . '.stub';
      provision_file()->unlink($stubfile)
        ->succeed('Deleted subfolder RewriteRule stub file @path.')
        ->fail('Could not delete subfolder RewriteRule stub file @path.');
    }
  }
}
