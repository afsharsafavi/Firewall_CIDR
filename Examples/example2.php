<?php

namespace FireWallCIDR;

require 'vendor/autoload.php';


$json_config = json_decode(file_get_contents(__DIR__ . '/configuration2.json'), 1);
//print_r($json_config);
CIDR_Lookup::setConfig($json_config);
CIDR_Lookup::run();
