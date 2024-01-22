<?php

namespace FireWallCIDR\class;

use FireWallCIDR\CIDR_Lookup;

class Fetch_CIDR
{
    private static string $url = 'https://www.ipdeny.com/ipblocks/data/countries/';
    private static mixed $file;
    private static array $CIDR;
    private static string $country;

    public static function run($country_code): void
    {
        self::$country = $country_code;
        self::$url .= self::$country . ".zone";
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
        self::$file = file_get_contents(self::$url);
        self::extract_CIDR();
        self::save();
    }

    public static function extract_CIDR(): void
    {
        preg_match_all('`\n(?<cidr>\d+\.\d+\.\d+\.\d+/\d+)`', self::$file, $m);
        foreach ($m['cidr'] as $CIDR) {
            self::$CIDR[] = $CIDR;
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
