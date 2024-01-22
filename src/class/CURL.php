<?php

namespace FireWallCIDR\class;

class CURL
{
    private object $curl_handler;
    private static int $timeout = 10;
    private static string $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/109.0';
    private string $proxy;
    private string $referer = '';

    public function __construct($proxy = '')
    {
        $this->curl_handler = curl_init();
        $this->proxy = $proxy;
        curl_setopt($this->curl_handler, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        if ($this->proxy != '') {
            curl_setopt($this->curl_handler, CURLOPT_PROXY, $this->proxy);
            curl_setopt($this->curl_handler, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }

        curl_setopt($this->curl_handler, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($this->curl_handler, CURLOPT_TIMEOUT, self::$timeout);
        curl_setopt($this->curl_handler, CURLOPT_ENCODING, "gzip");
        $header[] = "User-Agent: " . self::$user_agent;
        $header[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8";
        $header[] = "Accept-Language: en-US,en;q=0.9";
        curl_setopt($this->curl_handler, CURLOPT_HTTPHEADER, $header);
        curl_setopt($this->curl_handler, CURLOPT_RETURNTRANSFER, 1);

    }

    public static function getTimeout(): int
    {
        return self::$timeout;
    }

    public static function setTimeout(int $timeout): void
    {
        self::$timeout = $timeout;
    }

    public static function getUserAgent(): string
    {
        return self::$user_agent;
    }

    public static function setUserAgent(string $user_agent): void
    {
        self::$user_agent = $user_agent;
    }

    public function run($url, $referer = ''): string
    {
        if (!empty($referer) && empty($this->referer)) {
            curl_setopt($this->curl_handler, CURLOPT_REFERER, $referer);
        }
        curl_setopt($this->curl_handler, CURLOPT_URL, $url);
        return curl_exec($this->curl_handler);
    }

    public function __destruct()
    {
        curl_close($this->curl_handler);
    }
}