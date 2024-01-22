<?php

namespace FireWallCIDR\class\CIDR_Lookup_Drivers;

use FireWallCIDR\class\Base_CIDR_Lookup;

class hexillion extends Base_CIDR_Lookup implements CIDR_Lookup_Driver
{
    public static string $class_name;
    public static string $url = 'https://hexillion.com/samples/WhoisXML/?query=%s&_accept=application%%2Fvnd.hexillion.whois-v2%%2Bjson';
    public static string $referer = 'https://hexillion.com/';

    public static function fetch(&$ip_data): array
    {
        if (!(empty($ip_data['ServiceResult']['QueryResult']['WhoisRecord']['Registrant']['Country'][0]) || empty($ip_data['ServiceResult']['QueryResult']['WhoisRecord']['Registrant']['Name'][0]))) {
            return [
                'c' => $ip_data['ServiceResult']['QueryResult']['WhoisRecord']['Registrant']['Country'][0],
                'o' => $ip_data['ServiceResult']['QueryResult']['WhoisRecord']['Registrant']['Name'][0],
            ];
        }
        return [];
    }
}