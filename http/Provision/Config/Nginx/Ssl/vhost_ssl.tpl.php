
<?php if ($this->ssl_enabled && $this->ssl_key) : ?>

<?php if ($this->redirection): ?>
<?php foreach ($this->aliases as $alias_url): ?>
server {
  limit_conn   gulag 32;
  listen       <?php print "{$ip_address}:{$http_ssl_port}"; ?>;
  server_name  <?php print $alias_url; ?>;
  ssl                        on;
  ssl_certificate            <?php print $ssl_cert; ?>;
  ssl_certificate_key        <?php print $ssl_cert_key; ?>;
  ssl_protocols              SSLv3 TLSv1;
  ssl_ciphers                HIGH:!ADH:!MD5;
  ssl_prefer_server_ciphers  on;
  keepalive_timeout          70;
  rewrite ^ $scheme://<?php print $this->uri; ?>$request_uri? permanent;
}
<?php endforeach; ?>
<?php endif ?>

server {
  include      <?php print "{$server->include_path}"; ?>/fastcgi_ssl_params.conf;
  limit_conn   gulag 32; # like mod_evasive - this allows max 32 simultaneous connections from one IP address
  listen       <?php print "{$ip_address}:{$http_ssl_port}"; ?>;
  server_name  <?php print $this->uri; ?><?php if (!$this->redirection && is_array($this->aliases)) : foreach ($this->aliases as $alias_url) : if (trim($alias_url)) : ?> <?php print $alias_url; ?><?php endif; endforeach; endif; ?>;
  root         <?php print "{$this->root}"; ?>;
  ssl                        on;
  ssl_certificate            <?php print $ssl_cert; ?>;
  ssl_certificate_key        <?php print $ssl_cert_key; ?>;
  ssl_protocols              SSLv3 TLSv1;
  ssl_ciphers                HIGH:!ADH:!MD5;
  ssl_prefer_server_ciphers  on;
  keepalive_timeout          70;
  <?php print $extra_config; ?>
  <?php print "include      " . $server->include_path . "/nginx_common.conf;\n"; ?>
###
### Send all non-static requests to php-fpm, restricted to known php file.
###
location = /index.php {
  add_header    X-Engine "Aegir";
  add_header    X-Device "$device";
  add_header    X-Speed-Cache "$upstream_cache_status";
  add_header    X-Speed-Cache-UID "$cache_uid";
  add_header    X-Speed-Cache-Key "$key_uri";
  add_header    X-NoCache "$nocache_details";
  add_header    X-This-Proto "$http_x_forwarded_proto";
  add_header    X-Server-Name "$server_name";
  ###
  ### Use Nginx cache for all visitors.
  ###
  set $nocache "";
  if ( $nocache_details ~ (?:OctopusNCookie|Args|Skip) ) {
    set $nocache "NoCache";
  }
  fastcgi_cache speed;
  fastcgi_cache_methods GET HEAD; ### Nginx default, but added for clarity
  fastcgi_cache_min_uses 1;
  fastcgi_cache_key "$is_bot$device$host$request_method$key_uri$cache_uid$http_x_forwarded_proto$cookie_respimg";
  fastcgi_cache_valid 200 10s;
  fastcgi_cache_valid 302 1m;
  fastcgi_cache_valid 301 403 404 5s;
  fastcgi_cache_valid 500 502 503 504 1s;
  fastcgi_ignore_headers Cache-Control Expires;
  fastcgi_pass_header Set-Cookie;
  fastcgi_pass_header X-Accel-Expires;
  fastcgi_pass_header X-Accel-Redirect;
  fastcgi_no_cache $cookie_OctopusNoCacheID $http_authorization $http_pragma $nocache;
  fastcgi_cache_bypass $cookie_OctopusNoCacheID $http_authorization $http_pragma $nocache;
  fastcgi_cache_use_stale error http_500 http_503 invalid_header timeout updating;
  try_files     $uri =404; ### check for existence of php file first
  fastcgi_pass  127.0.0.1:9000;
<?php
  if ($server->nginx_has_upload_progress) {
    print "    track_uploads uploads 60s; ### required for upload progress\n";
  }
?>
  expires epoch;
}
<?php
  if ($server->nginx_has_upload_progress) {
?>
###
### Upload progress support.
### http://drupal.org/project/filefield_nginx_progress
### http://github.com/masterzen/nginx-upload-progress-module
###
location ~ (?:.*)/x-progress-id:(?:\w*) {
  access_log off;
  rewrite ^(.*)/x-progress-id:(\w*)  $1?X-Progress-ID=$2;
}
location ^~ /progress {
  access_log off;
  report_uploads uploads;
}
<?php
  }
?>
}

<?php endif; ?>

<?php
  // Generate the standard virtual host too.
  include(provision_class_directory('Provision_Config_Nginx_Site') . '/vhost.tpl.php');
?>
