<?php

namespace FireWallCIDR\class\Firewalls;

use FireWallCIDR\CIDR_Lookup;
use FireWallCIDR\class\CIDRLookup;

class Mikrotik
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
        $CIDR_data = CIDR_Lookup::getCIDRData();
        $this->witheList_ISP = CIDRLookup::filter_CIDR($CIDR_data, $this->config['isp']);
        $this->make_address_list();
    }

    private function make_address_list(): void
    {
        $comment = '';
        if (!empty($this->config['comment'])) {
            $comment = " comment=\"" . $this->config['comment'] . "\"";
        }
        if (empty($this->config['label'])) {
            $label = 'CIDR_' . CIDR_Lookup::getCountryCode();
            if (!empty(CIDRLookup::$ISP)) {
                $label .= '_custom';
            }
        } else {
            $label = $this->config['label'];
        }
        foreach ($this->witheList_ISP as $IP) {
            $this->output .= "/ip firewall address-list add list=\"" . $label . "\" address=" . $IP['i'] . $comment . PHP_EOL;
        }
    }


}