# Aegir web server main configuration file

#######################################################
###  nginx.conf main
#######################################################

 ## Default index files
  index         index.php index.html;

 ## FastCGI Caching
  fastcgi_cache_path /var/lib/nginx/speed
                     levels=2:2:2
                     keys_zone=speed:10m
                     inactive=15m
                     max_size=3g;

 ## General Options
  ignore_invalid_headers          on;
<?php
$nginx_is_modern = drush_get_option('nginx_is_modern');
if ($nginx_is_modern) {
  print "  limit_conn_zone \$binary_remote_addr zone=gulag:10m;\n";
}
else {
  print "  limit_zone gulag \$binary_remote_addr 10m;\n";
}
?>
  recursive_error_pages           on;
  reset_timedout_connection       on;
  fastcgi_intercept_errors        on;
  server_tokens                  off;
  fastcgi_hide_header         'Link';
  fastcgi_hide_header  'X-Generator';
  fastcgi_hide_header 'X-Powered-By';
  fastcgi_hide_header 'X-Drupal-Cache';

<?php
$nginx_has_gzip = drush_get_option('nginx_has_gzip');
if ($nginx_has_gzip) {
  print "  gzip_static       on;\n";
}
$nginx_has_upload_progress = drush_get_option('nginx_has_upload_progress');
if ($nginx_has_upload_progress) {
  print "  upload_progress uploads 1m;\n";
}
?>

 ## Log Format
  log_format        main '"$proxy_add_x_forwarded_for" $host [$time_local] '
                         '"$request" $status $body_bytes_sent '
                         '$request_length $bytes_sent "$http_referer" '
                         '"$http_user_agent" $request_time "$gzip_ratio"';

  client_body_temp_path  /var/lib/nginx/body 1 2;
  access_log             /var/log/nginx/access.log main;

<?php print $extra_config; ?>
#######################################################
###  nginx default maps
#######################################################

###
### Support separate Boost and Speed Booster caches for various mobile devices.
###
map $http_user_agent $device {
  default                                                                normal;
  ~*Nokia|BlackBerry.+MIDP|240x|320x|Palm|NetFront|Symbian|SonyEricsson  mobile-other;
  ~*iPhone|iPod|Android|BlackBerry.+AppleWebKit                          mobile-smart;
  ~*iPad|Tablet                                                          mobile-tablet;
}

###
### Set a cache_uid variable for authenticated users (by @brianmercer and @perusio, fixed by @omega8cc).
###
map $http_cookie $cache_uid {
  default                                        '';
  ~SESS[[:alnum:]]+=(?<session_id>[[:graph:]]+)  $session_id;
}

###
### Live switch of $key_uri for Speed Booster cache depending on $args.
###
map $request_uri $key_uri {
  default                                                                            $request_uri;
  ~(?<no_args_uri>[[:graph:]]+)\?(.*)(utm_|__utm|_campaign|gclid|source=|adv=|req=)  $no_args_uri;
}

###
### Deny crawlers.
###
map $http_user_agent $is_crawler {
  default                                                                                                                     '';
  ~*HTTrack|MJ12|HTMLParser|libwww|PECL|Automatic|Click|SiteBot|BuzzTrack|Sistrix|Offline|Screaming|Nutch|Mireo|SWEB|Morfeus  is_crawler;
}

###
### Deny all known bots on some URIs.
###
map $http_user_agent $is_bot {
  default                                                    '';
  ~*crawl|goog|yahoo|yandex|spider|bot|tracker|click|parser  is_bot;
}

###
### Deny listed requests for security reasons.
###
map $args $is_denied {
  default                                                                                                      '';
  ~*delete.+from|insert.+into|select.+from|union.+select|onload|\.php.+src|system\(.+|document\.cookie|\;|\.\. is_denied;
}

#######################################################
###  nginx default server
#######################################################

<?php
$ip_address = !empty($ip_address) ? $ip_address : '*';
?>
server {
  limit_conn   gulag 32; # like mod_evasive - this allows max 32 simultaneous connections from one IP address
<?php
if ($ip_address == '*') {
  print "  listen       {$ip_address}:{$http_port};\n";
}
else {
  foreach ($server->ip_addresses as $ip) {
    print "  listen       {$ip}:{$http_port};\n";
  }
}
?>
  server_name  _;
  location / {
     root   /var/www/nginx-default;
     index  index.html index.htm;
  }
}

#######################################################
###  nginx virtual domains
#######################################################

# virtual hosts
include <?php print $http_pred_path ?>/*;
include <?php print $http_platformd_path ?>/*;
include <?php print $http_vhostd_path ?>/*;
include <?php print $http_postd_path ?>/*;
