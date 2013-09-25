Alias /<?php print $location; ?> <?php print $platform_path; ?>

<Location /<?php print $location; ?>>

  SetEnv db_type  <?php print urlencode($db_type); ?>

  SetEnv db_name  <?php print urlencode($db_name); ?>

  SetEnv db_user  <?php print urlencode($db_user); ?>

  SetEnv db_passwd  <?php print urlencode($db_passwd); ?>

  SetEnv db_host  <?php print urlencode($db_host); ?>

  SetEnv db_port  <?php print urlencode($db_port); ?>

</Location>

# Error handler for Drupal > 4.6.7
<Directory "<?php print $site_path; ?>/files">
  SetHandler This_is_a_Drupal_security_line_do_not_remove
</Directory>

# Prevent direct reading of files in the private dir.
# This is for Drupal7 compatibility, which would normally drop
# a .htaccess in those directories, but we explicitly ignore those
<Directory "<?php print $site_path; ?>/private/" >
   SetHandler This_is_a_Drupal_security_line_do_not_remove
   Deny from all
   Options None
   Options +FollowSymLinks
</Directory>
