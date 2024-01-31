<?php

namespace FireWallCIDR\class\Firewalls;

use FireWallCIDR\CIDR_Lookup;
use FireWallCIDR\class\CIDRLookup;
use FireWallCIDR\class\Merge_CIDR;

class IPTables
{
    private array $config;
    private array $witheList_ISP = [];
    public string $output = '';

    public function __construct($config)
    {
        $this->config = $config;
        $this->run();
    }

    public function run(): void
    {

        $this->witheList_ISP = Merge_CIDR::filter_CIDR($this->config['isp']);
        if (empty($this->config['udp']) && empty($this->config['tcp'])) {
            echo "There is no IPTables protocol and ports in your configuration";
            sleep(2);
        }
        $this->accept('tcp');
        $this->accept('udp');


    }

    private function accept($protocol): void
    {
        if (empty($this->config[$protocol])) {
            return;
        }
        $ports = '';
        foreach ($this->config[$protocol] as $port) {
            if ($ports != '') {
                $ports .= ',';
            }
            $ports .= $port;
        }
        if (count($this->config[$protocol]) > 1) {
            $port_string = '--match multiport --dports';
        } else {
            $port_string = '--dport';
        }
        foreach ($this->witheList_ISP as $IP) {
            $this->output .= "iptables -I INPUT -s " . $IP['i'] . " -p " . $protocol . " " . $port_string . " " . $ports . " -j ACCEPT" . PHP_EOL;
        }
    }


}