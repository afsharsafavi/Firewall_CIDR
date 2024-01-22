<?php

namespace FireWallCIDR\class\CIDR_Lookup_Drivers;

use FireWallCIDR\class\Base_CIDR_Lookup;

class ipapi_co extends Base_CIDR_Lookup implements CIDR_Lookup_Driver
{
    public static string $class_name;
    public static string $url = 'https://ipapi.co/%s/json';
    public static string $referer = 'https://ipapi.co/';

    public static function fetch(&$ip_data): array
    {
        if (!(empty($ip_data['country']) || empty($ip_data['org']))) {
            return ['c' => $ip_data['country'], 'o' => $ip_data['org']];
        }
        return [];
    }
}