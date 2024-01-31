<?php

namespace FireWallCIDR\class;

use FireWallCIDR\CIDR_Lookup;

class Merge_CIDR
{
    public static array $Organizations = [];

    public static function filter_CIDR(array $needed_ISPs): array
    {

        $my_ISP = CIDR_Lookup::getISPs();
        $ISP = [];
        foreach ($needed_ISPs as $needed_ISP) {
            $ISP = array_merge($ISP, $my_ISP[strtolower($needed_ISP)]);
        }
        $whiteList = [];
        foreach (self::$Organizations as $organization => $value) {
            if (in_array($organization, $ISP) || empty($ISP)) {
                foreach ($value['s'] as $CIDR) {
                    $whiteList[] = ['i' => $CIDR, 'o' => $organization];
                }
            }
        }
        return $whiteList;
    }

    public static function merge()
    {
        $CIDRData = CIDR_Lookup::getCIDRData();
        $country_code = CIDR_Lookup::getCountryCode();
        foreach ($CIDRData as $subnet => $value) {
            if (strtolower($value['c']) != $country_code) {
                continue;
            }
            if (empty(self::$Organizations[$value['o']])) {
                self::$Organizations[$value['o']] = ['s' => [$subnet], 't' => $value['t']];
            } else {
                self::$Organizations[$value['o']]['s'][] = $subnet;
            }
        }
        foreach (self::$Organizations as $Organization => $value) {
            self::$Organizations[$Organization]['s'] = self::mergeCIDR($value['s']);
        }
        file_put_contents(CIDR_Lookup::getIPMergedDataFile(), json_encode(self::$Organizations));
    }

    private static function mergeCIDR($cidrArray): array
    {
        $ranges = [];
        foreach ($cidrArray as $cidr) {
            list($ip, $mask) = explode('/', $cidr);
            $start = ip2long($ip) & ~((1 << (32 - $mask)) - 1);
            $end = $start + pow(2, (32 - $mask)) - 1;
            $ranges[] = ['start' => $start, 'end' => $end];
        }
        usort($ranges, function ($a, $b) {
            return $a['start'] - $b['start'];
        });

        $mergedRanges = [];

        foreach ($ranges as $range) {
            if (empty($mergedRanges) || $range['start'] > $mergedRanges[count($mergedRanges) - 1]['end'] + 1) {
                $mergedRanges[] = $range;
            } else {
                $mergedRanges[count($mergedRanges) - 1]['end'] = max($mergedRanges[count($mergedRanges) - 1]['end'],
                    $range['end']);
            }
        }
        return array_map(function ($range) {
            $startIP = long2ip($range['start']);
            $mask = 32 - floor(log($range['end'] - $range['start'] + 1, 2));
            return $startIP . '/' . $mask;
        }, $mergedRanges);
    }

}