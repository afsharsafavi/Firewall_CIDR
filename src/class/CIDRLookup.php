<?php

namespace FireWallCIDR\class;

use FireWallCIDR\CIDR_Lookup;

$MY_ISPs = <<<EOD
Parvaresh Dadeha Co. Private Joint Stock
Iran Telecommunication Company PJS
Rightel Communication Service Company PJS
Iran Cell Service and Communication Company
Mobin Net Communication Company (Private Joint Stock)
Mobile Communication Company of Iran PLC
EOD;
global $MY_ISP;

class CIDRLookup
{
    private static array $CIDR;
    public static array $CIDR_data;
    public static array $WhiteList = [];
    public static string $IP_data_File;
    public static array $ISP = [];

    public static function run(): void
    {
        self::$CIDR = Fetch_CIDR::getCIDR();
        if (empty(self::$CIDR)) {
            echo "There is no CIDR" . PHP_EOL;
            return;
        }
        self::read_previous_CIDR_data();
        self::lookup();
    }


    private static function read_previous_CIDR_data(): void
    {
        self::$IP_data_File = CIDR_Lookup::getCIDRDataFile();
        if (is_file(self::$IP_data_File)) {
            self::$CIDR_data = json_decode(file_get_contents(self::$IP_data_File), 1);
        } else {
            self::$CIDR_data = [];
        }
        $custom_CIDR = CIDR_Lookup::getCustomCIDRData();
        if (!empty($custom_CIDR)) {
            self::$CIDR_data = array_merge($custom_CIDR, self::$CIDR_data);
        }
    }

    private static function lookup(): void
    {
        Base_CIDR_Lookup::prepare_connections_for_CIDR_lookup();
        $CIDRLookupDriver = CIDR_Lookup::getCIDRLookupDriver();
        $CIDR_Lookup_valid_days = CIDR_Lookup::getDataValidDays() * 86400;
        $time = time();
        $i = 0;
        foreach (self::$CIDR as $key => $value) {
            preg_match('/(.*)\/\d/', $value, $p);
            $ip = $p[1];
            if (isset(self::$CIDR_data[$value]) && self::$CIDR_data[$value]['t'] > $time - $CIDR_Lookup_valid_days) {
                continue;
            }
            echo $value . PHP_EOL;
            for ($i = 0; $i < count($CIDRLookupDriver); $i++) {
                if ($i > 0) {
                    echo "switch to {$CIDRLookupDriver[$i]}" . PHP_EOL;
                }
                $result = ("FireWallCIDR\class\CIDR_Lookup_Drivers\\" . $CIDRLookupDriver[$i])::run($ip);
                if (!empty($result)) {
                    $result['t'] = $time;
                    print_r($result);
                    break;
                }
            };
            if ($i == count($CIDRLookupDriver)) {
                $i = 0;
            }
            if (empty($result)) {
                echo "Lookup of IP:$ip has problem" . PHP_EOL;
                continue;
            }
            self::$CIDR_data[$value] = $result;
            file_put_contents(self::$IP_data_File, json_encode(self::$CIDR_data));
        }
        CIDR_Lookup::setCIDRData(self::$CIDR_data);
        Merge_CIDR::merge();
    }

    public static function filter_CIDR(array &$CIDR_data, array $needed_ISPs): array
    {
        $country_code = CIDR_Lookup::getCountryCode();
        $my_ISP = CIDR_Lookup::getISPs();
        self::$ISP = [];
        foreach ($needed_ISPs as $needed_ISP) {
            self::$ISP = array_merge(self::$ISP, $my_ISP[strtolower($needed_ISP)]);
        }
        $whiteList = [];
        foreach ($CIDR_data as $key => $value) {
            if (strtolower($value['c']) != $country_code) {
                continue;
            }
            if (!isset($value['o'])) {
                $value['o'] = 1;
            }
            if (in_array($value['o'], self::$ISP) || empty(self::$ISP)) {
                $whiteList[] = ['i' => $key, 'o' => $value['o']];
            }
        }
        return $whiteList;
    }

}
