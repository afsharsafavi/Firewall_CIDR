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
            $key = strtolower($needed_ISP);
            if (isset($my_ISP[$key]) && is_array($my_ISP[$key])) {
                $ISP = array_merge($ISP, $my_ISP[$key]);
            }
        }

        $whiteList = [];
        foreach (self::$Organizations as $organization => $value) {
            if (empty($ISP)) {
                foreach ($value['s'] as $CIDR) {
                    $whiteList[] = ['i' => $CIDR, 'o' => $organization];
                }
            } else {
                foreach ($ISP as $isp) {
                    $isp = strtolower($isp);
                    $orgLower = strtolower($organization);
                    if ($isp === $orgLower || strpos($orgLower, $isp) !== false) {
                        foreach ($value['s'] as $CIDR) {
                            $whiteList[] = ['i' => $CIDR, 'o' => $orgLower];
                        }
                        break;
                    }
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

    private static function ipToUInt(string $ip): int
    {
        $v = ip2long($ip);
        if ($v === false) {
            return 0;
        }
        return (int)sprintf('%u', $v);
    }

    private static function uintToIp(int $v): string
    {
        return long2ip((int)$v);
    }

    private static function rangeToCidrs(int $start, int $end): array
    {
        $cidrs = [];

        $start = (int)sprintf('%u', $start);
        $end = (int)sprintf('%u', $end);

        while ($start <= $end) {
            $maxSize = $start & (-$start);
            if ($maxSize === 0) {
                $maxSize = 1;
            }

            $remaining = $end - $start + 1;
            while ($maxSize > $remaining) {
                $maxSize >>= 1;
            }

            $mask = 32 - (int)log($maxSize, 2);

            $cidrs[] = self::uintToIp($start) . '/' . $mask;

            $start += $maxSize;
        }

        return $cidrs;
    }

    private static function mergeCIDR($cidrArray): array
    {
        $ranges = [];

        foreach ($cidrArray as $cidr) {
            [$ip, $mask] = explode('/', $cidr, 2);
            $mask = (int)$mask;

            $ipU = self::ipToUInt($ip);
            $blockSize = 1 << (32 - $mask);
            $start = $ipU & ~($blockSize - 1);
            $end = $start + $blockSize - 1;

            $ranges[] = ['start' => $start, 'end' => $end];
        }

        usort($ranges, fn($a, $b) => $a['start'] <=> $b['start']);

        $mergedRanges = [];
        foreach ($ranges as $range) {
            if (empty($mergedRanges) || $range['start'] > $mergedRanges[count($mergedRanges) - 1]['end'] + 1) {
                $mergedRanges[] = $range;
            } else {
                $idx = count($mergedRanges) - 1;
                $mergedRanges[$idx]['end'] = max($mergedRanges[$idx]['end'], $range['end']);
            }
        }

        $out = [];
        foreach ($mergedRanges as $r) {
            $out = array_merge($out, self::rangeToCidrs($r['start'], $r['end']));
        }

        return $out;
    }
}