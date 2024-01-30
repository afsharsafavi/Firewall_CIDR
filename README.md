# Firewall CIDR

This PHP project helps system administrators to minimize attacks to their services by limiting access of public ports to
a specific country CIDR or even ISP. Currently it generates IPTables rules and Mikrotik access lists.
Also You can receive the raw format of CIDR of your proposed country of ISP to use them in unsupported firewalls or other
programs.

Consider you have a SSH server that is under attack. And you don't have a static IP address on your side to block All IP
except your whitelist (your static IP addresses). Also you need access to this server with your phone hotspot.
With this project you can generate rules for your firewall to allow access to that port 22 (or something else) only for
your country IP ranges or even your ISP and carrier IP ranges.

Another example is IP PBX like Asterisk or Issabel. Sometimes you need to allow your users to access your PBX by their
cell phone sim cards in a specific country.
You can find out your country operators IP ranges and add them to your whitelist, and block elsewhere.

## Run by Docker

```bash
docker pull afsharsafavi/cidr

docker run -v ./Output:/myapp/Output -v ./Data/:/myapp/Data --network host -it --rm afsharsafavi/cidr

```

or

```bash
git clone git@github.com:afsharsafavi/Firewall_CIDR.git

cd Firewall_CIDR

docker build -t myapp .

docker run -v ./Output:/myapp/Output -v ./Data/:/myapp/Data --network host -it --rm myapp

```

## Installation

Dependency:
You need only PHP CLI version 8.2 and php-curl extention

For Ubuntu

```bash
  apt update && apt upgrade -y

  add-apt-repository ppa:ondrej/php

  apt update

  apt install php8.2 php8.2-curl
  
```

For RHEL and CentOS:

```bash
  dnf install php8.2 php8.2-cli php8.2-curl
```

Next you need to fill `configuration.json` file by the documentation in the `Example` folder and run examples:

```bash
git clone git@github.com:afsharsafavi/Firewall_CIDR.git

```

or by composer

```bash
composer create-project afsharsafavi/firewall_cidr
```

To run

```bash
cd Firewall_CIDR

php src/Examples/example1.php

```

At first, it downloads all CIDR related to your selected country.
Then find out whois information about each CIDR. (It may take too long).
Finally it will generate rules or access lists depending on your written configuration. (take a look
at `configuration.json` in `Examples` folder)

## Documentation

[Documentation](https://linktodocumentation)

CIDR_Lookup::setConfig() static method accept a json array with below specification.
After that it need only run CIDR_Lookup::run()

#### Main configuration

| Name                | Required | Description                                                                        |
|---------------------|----------|------------------------------------------------------------------------------------|
| Country Code        | yes      | Country Codes (Alpha-2 codes)                                                      |
| ISP Label           | optional | See section ISP Labels                                                             |
| data_valid_days     | optional | Lockup and CIDR data won't reload every time during this period (default: 14 dyas) |
| CIDR_Lookup_Drivers | yes      | See section ISP Lookup Dirvers                                                     |
| proxy               | optional | To decrease blocking by lookup drivers, see section Proxy                          |
| custom_CIDR_data    | optional | See section custom CIDR data                                                       |
| firewalls           | yes      | See section firewalls                                                              |

#### ISP Labels

For finding your needed ISP IP ranges, you should give me your ISP label.
Each ISP or orginization CIDR can have multiple label and you can give me an array for each ISP.
Forexample, ITC ISP of Iran have 2 CIDR label for ADSL and Mobile ranges.
You can pass it by this array:

```bash
"ITC": [
      "Iran Telecommunication Company PJS",
      "Mobile Communication Company of Iran PLC"
    ],

```

You can specify different ISP label at top (this section) and for each firewall config, only use their labels.
If you don't specify ISP labels, I will return whole CIDR ranges for your selected country.
You can findout this label by getting Whois of some sample of your target ISP.
For proximity, you can run project once by specify only your country code. then findout exact label from this path:

`src/Data/{Country Code}_CIDR_Data`

After running this project, above path will have all CIDR label. Depend on number of your country CIDR ranges, running
this, may takes long time.

#### ISP Lookup Dirvers

To findout label of each CIDR ranges, I use different services. They are in `src/class/CIDR_Lookup_Drivers`.
You can write your customer drivers and pass it's name to this section as an array.

System can accept multiple diver (recommended) and use them in your given order in array.

Sometime drivers return errors or null or maybe ban your IP (for rate limiting of their free services). In that cases, I
will use next drivers.

✨Giving more socks5 proxy, can help decreasing ban ratio.

#### Proxy

To find out CIDR labels, we use different free whois services. If your selected country has lots of CIDR ranges, you
will be banned for huge amounts of requests to their API endpoints.
The best solution at this time is giving me different socks5 proxy. It can leverage requests on different IP addresses
to decrease the ratio of ban.

I recommend to provide at least 3 socks5 proxy.

#### Custom CIDR data

Sometimes some CIDRs have wrong whois data or thier whois data like country code are wrong. You can add your custom CIDR
to configuration. I will consider them when I want to create firewall rules.

It can be an array of CIDRs with this format:

```
"custom_CIDR_data": {
    "1.234.56.0\/22": {
      "c": "IR",
      "o": "MY custom Organization label 1"
    },
    "2.355.56.0\/22": {
      "c": "IR",
      "o": "MY custom Organization label 2"
    }
    "3.234.56.0\/22": {
      "c": "IR",
      "o": "MY custom Organization label"
    }
  },

```

## Firewall

At this Time I support IPTables and Mikrotik.

I generate "accept rules" for iptables and address lists for Mikrotik devices.
You can use those address lists in many places in Mikrotik winbox.

You can pass as much firewall configuration as you need. I will generate their rules and put each firewall rule in a
file in the `src/Output/` folder separately.

Firewall fields accept Arrays of firewalls. Each array node structure should follow this rules:

| Name    | Required                | Description                                                     |
|---------|-------------------------|-----------------------------------------------------------------|
| Type    | yes                     | "iptables" or "mikrotik", "raw"                                 |
| ISP     | yes                     | Array of your Required ISP or empty for whole CIDR of country * |
| tcp     | optional (for IPTables) | See IPTables section                                            |
| udp     | optional (for IPTables) | See IPTables section                                            |
| comment | optional (for Mikrotik) | See Mikrotik section                                            |
| label   | optional (for Mikrotik) | See Mikrotik section                                            |

### IPTables

You can specify TCP or UDP port(s) and port ranges. I will write "accept rules" for them.
TCP and UDP ports can be a single port number (like 22 for SSH) or ranges or combination of them.
For example for PBX you can have this array:

```
 "udp": [
        "10000:20000",
        "5060"
      ]
```

### Mikrotik

For Mikrotik devices, I will write address lists depending on your configuration.

If you specify ISP, that address list will be included only your selected ISP CIDRs. Otherwise, that address list will
include your selected country CIDRs.

Also you can set `comment` to fill the comment field in the address list in Mikrotik. (optional)

If you write label field, rules will write with your custom label, otherwise, label field will be write
as `CIDR_{country code}` for when you want to whole country CIDRs and `CIDR_{country code}_custom` for when you specify
your purpose ISPs.

### Raw
In Raw type, I will write only CIDR address for you to use them in unsupported firewall or any programs that accept CIDR address.
If it's needed you can add some codes,prefix and/or postfix to CIDR list to use them depend on your needs.

## DISCLAIMER
This project collect countries CIDR and whois data from third-party services that there is no 100% guarantee on data accuracy. Before applying generated rules
from this project, you should double check data and rules and also know care about your firewall strategy and concerns.

## License

[![GPLv3 License](https://img.shields.io/badge/License-GPL%20v3-yellow.svg)](https://opensource.org/licenses/)

