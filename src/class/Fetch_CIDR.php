<?php

namespace FireWallCIDR\class;

use FireWallCIDR\CIDR_Lookup;

class Fetch_CIDR
{
    private static string $server1 = 'https://www.ipdeny.com/ipblocks/data/countries/';
    private static string $server2 = 'https://raw.githubusercontent.com/ipverse/country-ip-blocks/refs/heads/master/country/';
    private static mixed $file;
    private static array $CIDR;
    public static string $country;

    public static function run($country_code): void
    {
        self::$country = $country_code;
        self::$server1 .= self::$country . ".zone";
        self::$server2 .= self::$country . "/ipv4-aggregated.txt";
        self::fetch();
    }

    public static function fetch(): void
    {
        $CIDR_File = CIDR_Lookup::getCIDRFile();
        if (is_file($CIDR_File)) {
            self::$file = json_decode(file_get_contents($CIDR_File), 1);
            if (isset(self::$file['t']) && self::$file['t'] > time() - CIDR_Lookup::getDataValidDays() * 86400 && !empty(self::$file['d'])) {
                self::$CIDR = self::$file['d'];
                echo "CIDR Already Downloaded" . PHP_EOL;
                return;
            }
        }
        $file1 = file_get_contents(self::$server1);
        $file2 = file_get_contents(self::$server2);
        $file2 = preg_replace('/^\s*#.*\R/m', '', $file2);
        self::$file = $file1 . "\n" . $file2;
        self::extract_CIDR();
        self::save();
    }

    public static function extract_CIDR(): void
    {
        preg_match_all('`\n(?<cidr>\d+\.\d+\.\d+\.\d+/\d+)`', self::$file, $m);
        self::$CIDR = [];
        foreach ($m['cidr'] as $CIDR) {
            if (!in_array($CIDR, self::$CIDR)) {
                self::$CIDR[] = $CIDR;
            }
        }
    }

    private static function save(): void
    {
        file_put_contents(CIDR_Lookup::getCIDRFile(),
            json_encode(['c' => strtoupper(CIDR_Lookup::getCountryCode()), 't' => time(), 'd' => self::$CIDR]));
    }

    public static function getCIDR(): array
    {
        return self::$CIDR;
    }
}
