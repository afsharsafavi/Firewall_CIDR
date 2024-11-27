<?php

namespace FireWallCIDR\class\CIDR_Lookup_Drivers;

use FireWallCIDR\CIDR_Lookup;
use FireWallCIDR\class\Base_CIDR_Lookup;

class ipinfo extends Base_CIDR_Lookup implements CIDR_Lookup_Driver
{
    public static string $class_name;
    public static string $url = 'ipinfo.io/%s?token=';
    public static string $referer = 'https://ipinfo.io/';
    public static bool $shouldInit = true;

    public static function init()
    {
        if (CIDR_Lookup::$CIDR_Lookup_Driver_Key['ipinfo'] == 'api_key') {
            echo "Please signup at ipinfo.io and add your api key into json configuration\n";
            exit;
        }
        self::$url .= CIDR_Lookup::$CIDR_Lookup_Driver_Key['ipinfo'];
    }

    public static function fetch(&$ip_data): array
    {
        if (!empty($ip_data['country']) && !empty($ip_data['org'])) {
            return ['c' => strtolower($ip_data['country']), 'o' => $ip_data['org']];
        }
        return [];
    }

    public static function fetch2(&$ip_data): array
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