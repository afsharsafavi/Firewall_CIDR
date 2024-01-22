<?php

namespace FireWallCIDR\class\CIDR_Lookup_Drivers;

use FireWallCIDR\class\Base_CIDR_Lookup;

class ipwhois extends Base_CIDR_Lookup implements CIDR_Lookup_Driver
{
    public static string $class_name;
    public static string $url = 'https://ipwhois.app/widget.php?lang=en&ip=%s';
    public static string $referer = 'https://ipwhois.io/';

    public static function fetch(&$ip_data): array
    {
        if (!(empty($ip_data['connection']) || empty($ip_data['connection']['isp']))) {
            return [
                'c' => strtolower($ip_data['country_code']),
                'o' => $ip_data['connection']['isp'],
            ];
        }
        return [];
    }

}