<?php

namespace FireWallCIDR\class\CIDR_Lookup_Drivers;

interface CIDR_Lookup_Driver
{
    public static function fetch(&$ip_data): array;

}