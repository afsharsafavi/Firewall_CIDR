<?php

namespace FireWallCIDR\class;

use FireWallCIDR\CIDR_Lookup;

class Base_CIDR_Lookup
{
    public static array $CURL_handlers = [];
    public static array $proxy = [''];
    public static int $connection_counter = 0;
    public static bool $shouldInit = false;

    public static function className(): void
    {
        static::$class_name = str_replace(__NAMESPACE__ . '\CIDR_Lookup_Drivers\\', '', get_called_class());
    }

    public static function prepare_connections_for_CIDR_lookup(): void
    {
        self::$proxy = array_merge(self::$proxy, CIDR_Lookup::getProxy());
        $CIDRLookupDriver = CIDR_Lookup::getCIDRLookupDriver();
        for ($j = 0; $j < count($CIDRLookupDriver); $j++) {
            ("FireWallCIDR\class\CIDR_Lookup_Drivers\\" . $CIDRLookupDriver[$j])::className();
            for ($i = 0; $i < count(self::$proxy); $i++) {
                static::$CURL_handlers[$CIDRLookupDriver[$j]][] = new CURL(self::$proxy[$i]);
            }
        }
    }

    public static function run($u_ip)
    {
        for ($i = 0; $i < 3; $i++) {
            $ip_data = static::lookup($u_ip);
            $result = static::fetch($ip_data);
            if (!empty($result)) {
                return $result;
            }
            echo "try", $i + 1, PHP_EOL;
        }
        return [];
    }

    public static function lookup($u_ip): array
    {
        if (static::$shouldInit) {
            static::init();
            static::$shouldInit = false;
        }
        $data = static::$CURL_handlers[static::$class_name][static::$connection_counter++ % count(static::$proxy)]->run(sprintf(static::$url,
            $u_ip), static::$referer);
        $json = json_decode($data, 1);
        return is_array($json) ? $json : [];
    }
}