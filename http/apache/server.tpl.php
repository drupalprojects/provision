# Aegir web server configuration file

NameVirtualHost *:<?php print $http_port; ?>


<VirtualHost *:<?php print $http_port; ?>>
  ServerName default
  Redirect 404 /
</VirtualHost>


<IfModule !env_module>
  LoadModule env_module modules/mod_env.so
</IfModule>

<IfModule !rewrite_module>
  LoadModule rewrite_module modules/mod_rewrite.so
</IfModule>

# other configuration, not touched by aegir
# this allows you to override aegir configuration, as it is included before
Include <?php print $http_pred_path ?>

# virtual hosts
Include <?php print $http_vhostd_path ?>

# platforms
Include <?php print $http_platformd_path ?>

# logs
## Note: Aegir has been configured to assume that these logs are rotated once every <?php print $http_log_rotation_frequency ?> day(s)
## TODO use the log directory and log format set via the frontend 
## CustomLog <?php print $http_log_format ?> <?php print $http_logd_path ?>/<?php print $http_log_name ?>

# other configuration, not touched by aegir
# this allows to have default (for example during migrations) that are eventually overriden by aegir
Include <?php print $http_postd_path ?>

<?php print $extra_config; ?>
