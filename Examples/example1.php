<?php

namespace FireWallCIDR;

require 'vendor/autoload.php';


$json_config = json_decode(file_get_contents(__DIR__ . '/configuration.json'), 1);
CIDR_Lookup::setConfig($json_config);
CIDR_Lookup::run();