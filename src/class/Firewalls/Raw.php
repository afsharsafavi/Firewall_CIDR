<?php

namespace FireWallCIDR\class\Firewalls;

use FireWallCIDR\CIDR_Lookup;
use FireWallCIDR\class\CIDRLookup;
use FireWallCIDR\class\Merge_CIDR;

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
        $this->witheList_ISP = Merge_CIDR::filter_CIDR($this->config['isp']);
        $this->make_address_list();
    }

    private function make_address_list(): void
    {
        foreach ($this->witheList_ISP as $IP) {
            $this->output .= $IP['i'] . PHP_EOL;
        }
    }


}