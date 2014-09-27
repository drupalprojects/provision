<?php

class Provision_Config_Bind_slave extends Provision_Config_Dns_Server {
  public $template = 'slave.tpl.php';

  function process() {
    parent::process();
    if ($this->entity->type == 'server') {
     $ips = $this->entity->ip_addresses;
    }
    else {
     $ips = $this->entity->server->ip_addresses;
    }
    $this->data['master_ip_list'] = implode(';', $ips);
  }
}
