<?php
///////////////////////////////////////////////////////////////////////////

class HD
{
    public static function is_map($a)
    {
        return is_array($a) &&
            array_diff_key($a, array_keys(array_keys($a)));
    }

    ///////////////////////////////////////////////////////////////////////

    public static function has_attribute($obj, $n)
    {
        $arr = (array) $obj;
        return isset($arr[$n]);
    }
    ///////////////////////////////////////////////////////////////////////

    public static function get_map_element($map, $key)
    {
        return isset($map[$key]) ? $map[$key] : null;
    }

    ///////////////////////////////////////////////////////////////////////

    public static function starts_with($str, $pattern)
    {
        return strpos($str, $pattern) === 0;
    }

    ///////////////////////////////////////////////////////////////////////

    public static function format_timestamp($ts, $fmt = null)
    {
        // NOTE: for some reason, explicit timezone is required for PHP
        // on Dune (no builtin timezone info?).

        if (is_null($fmt))
            $fmt = 'Y:m:d H:i:s';

        $dt = new DateTime('@' . $ts);
        return $dt->format($fmt);
    }

    ///////////////////////////////////////////////////////////////////////

    public static function format_duration($msecs)
    {
        $n = intval($msecs);

        if (strlen($msecs) <= 0 || $n <= 0)
            return "--:--";

        $n = $n / 1000;
        $hours = $n / 3600;
        $remainder = $n % 3600;
        $minutes = $remainder / 60;
        $seconds = $remainder % 60;

        if (intval($hours) > 0)
        {
            return sprintf("%d:%02d:%02d", $hours, $minutes, $seconds);
        }
        else
        {
            return sprintf("%02d:%02d", $minutes, $seconds);
        }
    }

    ///////////////////////////////////////////////////////////////////////

    public static function encode_user_data($a, $b = null)
    {
        $media_url = null;
        $user_data = null;

        if (is_array($a) && is_null($b))
        {
            $media_url = '';
            $user_data = $a;
        }
        else
        {
            $media_url = $a;
            $user_data = $b;
        }

        if (!is_null($user_data))
            $media_url .= '||' . json_encode($user_data);

        return $media_url;
    }

    ///////////////////////////////////////////////////////////////////////

    public static function decode_user_data($media_url_str, &$media_url, &$user_data)
    {
        $idx = strpos($media_url_str, '||');

        if ($idx === false)
        {
            $media_url = $media_url_str;
            $user_data = null;
            return;
        }

        $media_url = substr($media_url_str, 0, $idx);
        $user_data = json_decode(substr($media_url_str, $idx + 2));
    }

    ///////////////////////////////////////////////////////////////////////

    public static function create_regular_folder_range($items,
        $from_ndx = 0, $total = -1, $more_items_available = false)
    {
        if ($total === -1)
            $total = $from_ndx + count($items);

        if ($from_ndx >= $total)
        {
            $from_ndx = $total;
            $items = array();
        }
        else if ($from_ndx + count($items) > $total)
        {
            array_splice($items, $total - $from_ndx);
        }

        return array
        (
            PluginRegularFolderRange::total => intval($total),
            PluginRegularFolderRange::more_items_available => $more_items_available,
            PluginRegularFolderRange::from_ndx => intval($from_ndx),
            PluginRegularFolderRange::count => count($items),
            PluginRegularFolderRange::items => $items
        );
    }

    ///////////////////////////////////////////////////////////////////////

    public static function http_get_document($url, $opts = null)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,    5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,    true);
        curl_setopt($ch, CURLOPT_TIMEOUT,           5);
        curl_setopt($ch, CURLOPT_USERAGENT,         'DuneHD/1.0');
        curl_setopt($ch, CURLOPT_URL,               $url);

        if (isset($opts))
        {
            foreach ($opts as $k => $v)
                curl_setopt($ch, $k, $v);
        }

        hd_print("HTTP fetching '$url'...");

        $content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($content === false)
        {
            $err_msg =
                "HTTP error: $http_code; " .
                "CURL errno: " . curl_errno($ch) .
                " (" . curl_error($ch) . ')';
            hd_print($err_msg);
            throw new Exception($err_msg);
        }

        if ($http_code != 200)
        {
            $err_msg = "HTTP request failed ($http_code)";
            hd_print($err_msg);
            throw new Exception($err_msg);
        }

        hd_print("HTTP OK ($http_code)");

        curl_close($ch);

        return $content;
    }

    ///////////////////////////////////////////////////////////////////////

    public static function http_post_document($url, $post_data)
    {
        return self::http_get_document($url,
            array
            (
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $post_data
            ));
    }

    ///////////////////////////////////////////////////////////////////////

    public static function parse_xml_document($doc)
    {
        $xml = simplexml_load_string($doc);

        if ($xml === false)
        {
            hd_print("Error: can not parse XML document.");
            hd_print("XML-text: $doc.");
            throw new Exception('Illegal XML document');
        }

        return $xml;
    }

    ///////////////////////////////////////////////////////////////////////

    public static function make_json_rpc_request($op_name, $params)
    {
        static $request_id = 0;

        $request = array
        (
            'jsonrpc' => '2.0',
            'id' => ++$request_id,
            'method' => $op_name,
            'params' => $params
        );

        return $request;
    }

    ///////////////////////////////////////////////////////////////////////////

    public static function get_ip_addr()
    {
        static $ip_addr = null;

        if (is_null($ip_addr))
        {
            $ip_addr = shell_exec(
                'ifconfig eth0 | head -2 | tail -1 | sed "s/^.*inet addr:\([^ ]*\).*$/\1/"');

            $ip_addr = trim($ip_addr);

            hd_print("IP Address: '$ip_addr'");
        }

        return $ip_addr;
    }

    ///////////////////////////////////////////////////////////////////////////

    public static function get_mac_addr()
    {
        static $mac_addr = null;

        if (is_null($mac_addr))
        {
            $mac_addr = shell_exec(
                'ifconfig  eth0 | head -1 | sed "s/^.*HWaddr //"');

            $mac_addr = trim($mac_addr);

            hd_print("MAC Address: '$mac_addr'");
        }

        return $mac_addr;
    }

    ///////////////////////////////////////////////////////////////////////////

    public static function get_serial_number()
    {
        static $serial_number = null;

        if (is_null($serial_number))
        {
            $serial_number = shell_exec(
                'grep "serial_number:" /tmp/sysinfo.txt | sed "s/^.*: *//"');

            $serial_number = trim($serial_number);
            hd_print("Serial number: '$serial_number'");
        }

        return $serial_number;
    }

    ///////////////////////////////////////////////////////////////////////////

    public static function get_local_time_zone()
    {
        $cmd = 'TZ=`cat /etc/TZ` date +%z';

        hd_print("Executing '$cmd'...");

        exec($cmd, $out, $rc);

        if ($rc != 0)
        {
            hd_print("Error: can not retrieve local time zone: command failed.");
            foreach ($out as $line)
                hd_print("--> $out");

            throw new Exception("Can not retrieve local time zone");
        }

        if (count($out) != 1)
        {
            hd_print("Error: can not retrieve local time zone: unexpected output.");
            foreach ($out as $line)
                hd_print("--> $out");

            throw new Exception("Can not retrieve local time zone");
        }

        $local_tz = $out[0];

        hd_print("Local time zone: '" . $local_tz . "'");

        return $local_tz;
    }

    ///////////////////////////////////////////////////////////////////////////

    public static function get_local_time_zone_offset()
    {
        $local_tz = HD::get_local_time_zone();

        $sign = 1;
        $sign_ch = substr($local_tz, 0, 1);

        if ($sign_ch == '-')
        {
            $sign = -1; //    TZ='GMT+12:00' <=> -1200 <=> GMT-12:00
        }
        else if ($sign_ch == '+')
        {
            $sign = +1; //    TZ='GMT-12:00' <=> +1200 <=> GMT+12:00
                        // or TZ='GMT+00:00' <=> +0000 <=> GMT+00:00
                        // or TZ='GMT-00:00' <=> +0000 <=> GMT+00:00
        }
        else
        {
            hd_print("Error: unknown time zone format ($local_tz).");
            throw new Exception("Unknown time zone format");
        }

        $tz_hh = intval(substr($local_tz, 1, 2));
        $tz_mm = intval(substr($local_tz, 3, 2));

        $local_tz_offset = $sign * ($tz_hh * 60 + $tz_mm) * 60;

        hd_print("Local time zone offset: " . $local_tz_offset);

        return $local_tz_offset;
    }

    ///////////////////////////////////////////////////////////////////////////

    public static function escape_xml_string($str)
    {
        $str = strval($str);
        $len = strlen($str);
        $out = '';
        for ($i = 0; $i < $len; $i++)
        {
            if ($str[$i] == '&')
                $out .= '&amp;';
            else if ($str[$i] == '<')
                $out .= '&lt;';
            else if ($str[$i] == '>')
                $out .= '&gt;';
            else if ($str[$i] == '"')
                $out .= '&quot;';
            else
                $out .= $str[$i];
        }
        return $out;
    }

    private static function get_month_key($n)
    {
        return "formatting_month_$n";
    }

    public static function format_date_time_date_ext($tm)
    {
        $lt = localtime($tm);
        $mon_key = self::get_month_key($lt[4]);
        return sprintf("%02d <key_global>%s</key_global> %04d",
            $lt[3], $mon_key, $lt[5] + 1900);
    }

    public static function format_date_time_time($tm, $with_sec = false)
    {
        $format = '%H:%M';
        if ($with_sec)
            $format .= ':%S';
        return strftime($format, $tm);
    }

    public static function print_backtrace()
    {
        hd_print('Back trace:');
        foreach (debug_backtrace() as $f)
        {
            hd_print(
                '  - ' . $f['function'] . 
                ' at ' . $f['file'] . ':' . $f['line']);
        }
    }
    
}

///////////////////////////////////////////////////////////////////////////
?>
