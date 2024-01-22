<?php

namespace FireWallCIDR\class\CIDR_Lookup_Drivers;

use FireWallCIDR\class\Base_CIDR_Lookup;

class ipinfo extends Base_CIDR_Lookup implements CIDR_Lookup_Driver
{
    public static string $class_name;
    public static string $url = 'https://ipinfo.io/widget/demo/%s';
    public static string $referer = 'https://ipinfo.io/';

    public static function fetch(&$ip_data): array
    {
        if (empty($ip_data['data']['asn']['name']) && !empty($ip_data['data']['company']['name'])) {
            $ip_data['data']['asn']['name'] = $ip_data['data']['company']['name'];
        }
        if (!empty($ip_data['data']['country']) && !empty($ip_data['data']['asn']['name'])) {
            return ['c' => strtolower($ip_data['data']['country']), 'o' => $ip_data['data']['asn']['name']];
        }
        return [];
    }
}