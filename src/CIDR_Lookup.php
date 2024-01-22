<?php

namespace FireWallCIDR;

use Exception, Throwable;
use FireWallCIDR\class\CIDRLookup;
use FireWallCIDR\class\Fetch_CIDR;
use FireWallCIDR\class\Firewalls\IPTables;
use FireWallCIDR\class\Firewalls\Mikrotik;

function exception_handler(Throwable $exception): void
{
    echo "Error: ", $exception->getMessage(), PHP_EOL;
}

set_exception_handler('\FireWallCIDR\exception_handler');

class CIDR_Lookup
{
    private static string $base_DIR = __DIR__;
    private static string $data_path = '/Data/';
    private static string $CIDR_data_file;
    private static string $CIDR_file;
    private static int $data_valid_days = 14;
    private static array $proxy = [];
    private static array $CIDR_Lookup_Driver = [];
    private static array $ISPs = [];
    private static array $config;
    private static string $country_code = 'ir';
    private static array $supported_firewall = ['mikrotik', 'iptables', 'raw'];
    private static array $IPTables_config = [];
    private static array $Mikrotik_config = [];
    private static array $Raw_config = [];
    private static bool $whole_country = false;
    private static array $CIDR_data = [];
    private static array $custom_CIDR_data = [];
    private static string $OutputDIR = '/Output/';

    public static function getProxy(): array
    {
        return self::$proxy;
    }

    public static function setProxy(string $proxy): void
    {
        self::$proxy[] = $proxy;
    }

    public static function getCIDRLookupDriver(): array
    {
        return self::$CIDR_Lookup_Driver;
    }

    public static function setCIDRLookupDriver(string $driver): void
    {
        self::$CIDR_Lookup_Driver[] = $driver;
    }

    public static function getDataValidDays(): int
    {
        return self::$data_valid_days;
    }

    public static function setDataValidDays(int $data_valid_days): void
    {
        self::$data_valid_days = $data_valid_days;
    }

    public static function getCIDRFile(): string
    {
        return self::$CIDR_file;
    }

    public static function setCIDRFile(string $CIDR_file): void
    {
        self::$CIDR_file = $CIDR_file;
    }

    public static function getCIDRDataFile(): string
    {
        return self::$CIDR_data_file;
    }

    public static function setCIDRDataFile(string $CIDR_data_file): void
    {
        self::$CIDR_data_file = $CIDR_data_file;
    }

    public static function getISPs(): array
    {
        return self::$ISPs;
    }

    public static function setISPs(string $key, string|array $value): void
    {
        self::$ISPs[$key] = $value;
    }

    public static function getConfig(): array
    {
        return self::$config;
    }

    public static function setConfig(array $config): void
    {
        self::$config = self::array_change_key_case_recursive($config);
        echo "Loading configurations" . PHP_EOL;
        self::apply_configurations();
    }

    private static function array_change_key_case_recursive($arr)
    {
        return array_map(function ($item) {
            if (is_array($item)) {
                $item = self::array_change_key_case_recursive($item);
            }
            return $item;
        }, array_change_key_case($arr));
    }

    public static function apply_configurations(): void
    {
        self::$base_DIR = str_replace("/src", "", self::$base_DIR);
        self::$OutputDIR = self::$base_DIR . self::$OutputDIR;
        self::$data_path = self::$base_DIR . self::$data_path;
        if (!is_dir(self::$OutputDIR)) {
            mkdir(self::$OutputDIR, 0777, true);
        }
        if (!is_dir(self::$data_path)) {
            mkdir(self::$data_path, 0777, true);
        }
        if (empty(self::$config['country_code'])) {
            throw new Exception('Country code is mandatory');
        }
        self::setCIDRFile(self::$data_path . strtoupper(self::$config['country_code']) . '_CIDR');
        self::setCIDRDataFile(self::$data_path . strtoupper(self::$config['country_code']) . '_CIDR_Data');
        self::setCountryCode(self::$config['country_code']);
        if (!empty(self::$config['data_valid_days'])) {
            if (is_integer(self::$config['data_valid_days']) && self::$config['data_valid_days'] > 0) {
                self::$data_valid_days = self::$config['data_valid_days'];
            } else {
                throw new Exception('data_valid_days should be non-zero integer');
            }
        }
        if (empty(self::$config['isp_label'])) {
            echo "There is no ISP_Label. So, I consider all CIDR of selected country" . PHP_EOL;
            self::$whole_country = true;
        } else {
            foreach (self::$config['isp_label'] as $key => $value) {
                if (!is_string($key) || !is_array($value)) {
                    throw new Exception('ISP_Label keys should be string and values should be an Array');
                }
                self::setISPs($key, $value);
            }
        }
        if (empty(self::$config['cidr_lookup_drivers'])) {
            throw new Exception("You should select at least one CIDR_Lookup_Drivers to lookup ISP Labels" . PHP_EOL . "We support this services (preferred order): 'ipapi_co', 'hexillion', 'ipwhois', 'ipinfo', 'ipip_is'");
        } else {
            foreach (self::$config['cidr_lookup_drivers'] as $driver) {
                self::setCIDRLookupDriver($driver);
            }
        }

        if (empty(self::$config['proxy']) || count(self::$config['proxy']) < 3) {
            echo "It's highly recommended to give me at least 3 Socks5 proxy servers" . PHP_EOL;
            sleep(2);
        } else {
            foreach (self::$config['proxy'] as $proxy) {
                self::setProxy($proxy);
            }
        }
        if (!empty(self::$config['custom_cidr_data'])) {
            foreach (self::$config['custom_cidr_data'] as $CIDR => $value) {
                self::setCustomCIDRData($CIDR, $value);
            }
        }
        if (empty(self::$config['firewalls'])) {
            throw new Exception('There is no firewalls fields in your configuration');
        }
        foreach (self::$config['firewalls'] as $firewall) {
            self::prepare_firewall_configuration($firewall);
        }
    }

    public static function prepare_firewall_configuration($firewall)
    {
        if (!in_array($firewall['type'], self::$supported_firewall)) {
            throw new Exception('Your selected firewall(s) type is not supported. Supported Firewall:' . PHP_EOL . print_r(self::$supported_firewall,
                    true));
        }
        if (empty($firewall['isp'])) {
            $firewall['isp'] = [];
        } else {
            self::checkISP($firewall);
        }

        switch ($firewall['type']) {
            case 'iptables':
                self::setIPTablesConfig($firewall);
                break;
            case 'mikrotik':
                self::setMikrotikConfig($firewall);
                break;
            case 'raw':
                self::setRawConfig($firewall);
                break;
            default:

                throw new Exception('Your selected firewall type is not supported. Supported Firewall:' . PHP_EOL . print_r(self::$supported_firewall,
                        true));
        }

    }

    public static function getCountryCode(): string
    {
        return self::$country_code;
    }

    public static function setCountryCode(string $country_code): void
    {
        self::$country_code = strtolower($country_code);
    }

    public static function getIPTablesConfig(): array
    {
        return self::$IPTables_config;
    }

    public static function setIPTablesConfig(array $IPTables_config): void
    {
        self::$IPTables_config[] = $IPTables_config;
    }

    public static function getMikrotikConfig(): array
    {
        return self::$Mikrotik_config;
    }

    public static function setMikrotikConfig(array $Mikrotik_config): void
    {
        self::$Mikrotik_config[] = $Mikrotik_config;
    }

    public static function getRawConfig(): array
    {
        return self::$Raw_config;
    }

    public static function setRawConfig(array $Raw_config): void
    {
        self::$Raw_config[] = $Raw_config;
    }

    public static function checkISP($firewall): void
    {
        if (!empty($firewall['isp'])) {
            if (self::$whole_country) {
                throw new Exception('There is no ISP_Label while you selected some ISPs. First you should specify ISP_Label');
            }
            foreach ($firewall['isp'] as $ISP) {
                if (empty(self::$ISPs[strtolower($ISP)])) {
                    throw new Exception('There is no ISP_Label for: ' . $ISP . PHP_EOL);
                }
            }
        }
    }

    public static function run()
    {
        Fetch_CIDR::run(self::$country_code);
        CIDRLookup::run();
        if (empty(self::$config)) {
            throw new Exception('You should run setConfig function first!');
        }
        $cnt = 0;

        //self::delTree(self::$OutputDIR);
        //mkdir(self::$OutputDIR);
        $firewalls = ['IPTables', 'Mikrotik', "Raw"];
        foreach ($firewalls as $firewall_name) {
            $config = $firewall_name . '_config';
            foreach (self::$$config as $config) {
                $cnt++;
                $class_name = "\\" . __NAMESPACE__ . "\class\Firewalls\\" . $firewall_name;
                $firewall = new $class_name($config);
                if ($firewall->output == '') {
                    echo "$firewall_name NO $cnt result is empty";
                } else {
                    self::save_output($firewall, $cnt);
                }
            }
        }
    }

    public static function save_output(object $firewall, int $cnt)
    {
        $firewall_name = str_replace(__NAMESPACE__ . '\class\Firewalls\\', '', $firewall::class);
        $ext = '';
        if ($firewall_name == 'Mikrotik') {
            $ext = '.rsc';
        }
        $path = self::$OutputDIR . $firewall_name . '_' . $cnt . $ext;
        file_put_contents($path, $firewall->output);
        echo "Firewall rules has been saved at: " . $path . PHP_EOL;
    }

    public static function getCIDRData(): array
    {
        return self::$CIDR_data;
    }

    public static function setCIDRData(array $CIDR_data): void
    {
        self::$CIDR_data = $CIDR_data;
    }

    public static function delTree($dir): bool
    {

        $files = array_diff(scandir($dir), array('.', '..'));

        foreach ($files as $file) {

            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");

        }

        return rmdir($dir);
    }

    public static function getCustomCIDRData(): array
    {
        return self::$custom_CIDR_data;
    }

    public static function setCustomCIDRData(string $CIDR, array $data): void
    {
        self::$custom_CIDR_data[$CIDR] = array_merge($data, ['t' => time()]);
    }

}