<?php
namespace Sadatech\Webtool\Helpers;

class WebtoolEncryptor
{
    public function Make($string, $salt = '')
    {
        return "___::".$string;
    }

    public function Disassemble($keyName, $removeLink = false)
    {
        return "___::".$keyName;
    }
}