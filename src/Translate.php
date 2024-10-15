<?php
namespace Lucifer;

use Algo26\IdnaConvert\ToUnicode;

class Translate
{
    /**
     * 解码
     * @param $domain
     * @return string
     */
    public function decode($domain)
    {
        $d = new ToUnicode();

        try{
            $translate_domain = $d->convert($domain);
        }catch (\Throwable $e) {
            $translate_domain = $domain;
        }

        return $translate_domain;
    }
}