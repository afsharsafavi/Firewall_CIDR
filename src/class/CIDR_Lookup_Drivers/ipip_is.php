<?php

namespace FireWallCIDR\class\CIDR_Lookup_Drivers;

use FireWallCIDR\class\Base_CIDR_Lookup;

class ipip_is extends Base_CIDR_Lookup implements CIDR_Lookup_Driver
{
    public static string $class_name;
    public static string $url = 'https://api.ipapi.is/?q=%s';
    public static string $referer = 'https://ipapi.is/';


    public static function fetch(&$ip_data): array
    {
        if (empty($ip_data['asn']['name']) && !empty($ip_data['company']['name'])) {
            $ip_data['asn']['name'] = $ip_data['company']['name'];
        }
        if (!empty($ip_data['location']['country']) && !empty($ip_data['asn']['name'])) {
            return ['c' => strtolower($ip_data['location']['country']), 'o' => $ip_data['asn']['name']];
        }
        return [];
    }
}