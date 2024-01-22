<?php

namespace FireWallCIDR\class\Firewalls;

use FireWallCIDR\CIDR_Lookup;
use FireWallCIDR\class\CIDRLookup;

class Raw
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
        foreach ($this->witheList_ISP as $IP) {
            $this->output .= $IP['i'] . PHP_EOL;
        }
    }


}