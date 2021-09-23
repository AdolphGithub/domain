<?php
namespace Lucifer;

class Domain
{
    private static $cctld_domains = [];

    private static $gtld_domains = [];

    /**
     * 解析域名和地址.
     * @param $url
     * @return array|bool
     */
    public static function parseUrl($url)
    {
        if(!self::isDomain($url)) {
            return false;
        }

        $url = strtolower($url);

        $url = strpos($url, 'https://') === 0
            ? $url
            : (strpos($url, 'http://') === 0 ? $url : sprintf('http://%s', $url));

        $info = parse_url($url);

        if(!$info || !isset($info['host'])) {
            return false;
        }

        $info['host'] = strpos($info['host'], ':') == strlen($info['host']) - 1
            ? str_replace(':', '', $info['host'])
            : $info['host'];

        // 开始解析.
        list($host, $port) = strpos($info['host'], ':') !== false
            ? explode($info['host'], ':')
            : [$info['host'], ($info['scheme'] == 'http' ? '80' : '443')];

        $is_ip = filter_var($host, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6);

        if($is_ip) {
            return [
                'main_domain' => $host,
                'port'        => $port,
                'url'         => $url,
                'domain'      => $host,
                'scheme'      => $info['scheme'] ?? '',
            ];
        }

        if(strpos($host,'xn--') !== false) {
            // http://xn--ciqpnv5y6jjyman86w.xn--czru2d/
            $code = new Translate();
            // 尝试处理一次. 不然会有问题. 截取后缀等可能都有问题.
            try{
                $translate_host = $code->decode($host);
            }catch (\Exception $exception) {
                $translate_host = $host;
            }

            $host = $translate_host;
        }

        $main_domain = self::getMainDomain($host);

        return [
            'main_domain' => $main_domain, // 主域名
            'port'        => $port, // 端口
            'url'         => $url,  // 链接地址
            'domain'      => $host, // 域名
            'scheme'      => $info['scheme'] ?? '',
        ];
    }

    /**
     * 获取主域名信息.
     * @param $domain
     * @return bool|string
     */
    public static function getMainDomain($domain)
    {
        $domain = strtolower($domain);

        if(!self::isDomain($domain)) {
            return false;
        }

        if(strpos($domain,'xn--') !== false) {
            // http://xn--ciqpnv5y6jjyman86w.xn--czru2d/
            $code = new Translate();
            // 尝试处理一次. 不然会有问题. 截取后缀等可能都有问题.
            try{
                $translate_host = $code->decode($domain);
            }catch (\Exception $exception) {
                $translate_host = $domain;
            }

            $domain = $translate_host;
        }

        $domain_words = explode('.', $domain);

        // 开始转义.
        while(!in_array(array_shift($domain_words), ['', null])) {
            $kw = '.' . implode('.', $domain_words);
            if(count($domain_words) >= 2 && in_array($kw, self::getCctldDomains())) {
                break;
            }

            if(count($domain_words) === 1 && in_array($kw, self::getGtldDomains())) {
                break;
            }
        }

        $all = explode('.', $domain);
        // 总的减去最后的.
        $suffix = array_slice($all, -(count($domain_words) + 1));

        return implode('.', $suffix);
    }

    /**
     * 获取顶级后缀.
     * @return array
     */
    public static function getGtldDomains()
    {
        if(self::$gtld_domains) {
            return self::$gtld_domains;
        }

        $gtld_domains = file_get_contents(dirname(__DIR__) . '/deps/gtld.txt');

        $gtld_domains = explode("\r\n", $gtld_domains);

        self::$gtld_domains = $gtld_domains;

        return $gtld_domains;
    }

    /**
     * 获取地区性后缀。
     * @return array
     */
    public static function getCctldDomains()
    {
        if(self::$cctld_domains) {
            return self::$cctld_domains;
        }

        $cctld_domains = file_get_contents(dirname(__DIR__) . '/deps/cctld.txt');

        $cctld_domains = explode("\r\n", $cctld_domains);

        self::$cctld_domains = $cctld_domains;

        return $cctld_domains;
    }

    /**
     * 获取所有的域名后缀.
     * @return array
     */
    public static function getSuffixDomains()
    {
        return self::getGtldDomains() + self::getCctldDomains();
    }

    /**
     * 简单判断是否为一个域名.
     * @param $domain
     * @return bool
     */
    private static function isDomain($domain)
    {
        return strpos($domain, '.') !== false;
    }
}