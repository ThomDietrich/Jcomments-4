<?php

namespace JcommentsTeam\Component\Jcomments\Site\Libraries\Joomlatune;
defined('_JEXEC') or die;

class JcommentsAjaxResponse
{
    var $aCommands;
    var $xml;
    var $sEncoding;

    function __construct($sEncoding = 'utf-8')
    {
        $this->aCommands = array();
        $this->sEncoding = $sEncoding;
    }

    function addCommand($aAttributes, $mData)
    {
        $aAttributes['d'] = $mData;
        $this->aCommands[] = $aAttributes;
    }

    function addAssign($sTarget, $sAttribute, $sData)
    {
        $scripts = array();
        // small hack to auto execute JavaScript code returned through ajax
        if (preg_match('/\<script/', $sData)) {
            $regexp = '/<script[^>]+>(.*?)<\/script>/ism';
            $matches = array();
            preg_match_all($regexp, $sData, $matches);

            for ($i = 0, $n = count($matches[0]); $i < $n; $i++) {
                if ($matches[1][$i] != '') {
                    $sData = str_replace($matches[0][$i], '', $sData);
                    $scripts[] = trim(preg_replace(array('#^<!--#ism', '#\/\/-->$#ism'), '', $matches[1][$i]));
                }
            }
        }

        $this->addCommand(array('n' => 'as', 't' => $sTarget, 'p' => $sAttribute), $sData);

        if (count($scripts)) {
            foreach ($scripts as $script) {
                $this->addCommand(array('n' => 'js'), $script);
            }
        }

        return $this;
    }

    function addScript($sJS)
    {
        $sJS = str_replace("\n", '\n', $sJS);
        $sJS = str_replace("\r", '', $sJS);
        $this->addCommand(array('n' => 'js'), $sJS);
        return $this;
    }

    function addAlert($sMsg)
    {
        $this->addCommand(array('n' => 'al'), $sMsg);
        return $this;
    }

    function getOutput()
    {
        $output = '';
        if (is_array($this->aCommands)) {
            $output = JcommentsAjaxResponse::php2js($this->aCommands);
        }
        if (trim($this->sEncoding)) {
            @header('content-type: text/plain; charset="' . $this->sEncoding . '"');
        }
        return $output;
    }

    /**
     * This function taken from JsHttpRequest library
     * JsHttpRequest: PHP backend for JavaScript DHTML loader.
     * (C) Dmitry Koterov, http://en.dklab.ru
     *
     * Convert a PHP scalar, array or hash to JS scalar/array/hash. This function is
     * an analog of json_encode(), but it can work with a non-UTF8 input and does not
     * analyze the passed data. Output format must be fully JSON compatible.
     *
     * @param mixed $a Any structure to convert to JS.
     * @return string    JavaScript equivalent structure.
     */
    function php2js($a = false)
    {
        if (is_null($a)) return 'null';
        if ($a === false) return 'false';
        if ($a === true) return 'true';
        if (is_scalar($a)) {
            if (is_float($a)) {
                $a = str_replace(",", ".", strval($a));
            }
            // All scalars are converted to strings to avoid indeterminism.
            // PHP's "1" and 1 are equal for all PHP operators, but
            // JS's "1" and 1 are not. So if we pass "1" or 1 from the PHP backend,
            // we should get the same result in the JS frontend (string).
            // Character replacements for JSON.
            static $jsonReplaces = array(
                array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'),
                array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"')
            );
            return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
        }
        $isList = true;
        for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
            if (key($a) !== $i) {
                $isList = false;
                break;
            }
        }
        $result = array();
        if ($isList) {
            foreach ($a as $v) {
                $result[] = JcommentsAjaxResponse::php2js($v);
            }
            return '[ ' . join(', ', $result) . ' ]';
        } else {
            foreach ($a as $k => $v) {
                $k = JcommentsAjaxResponse::php2js($k);
                $v = JcommentsAjaxResponse::php2js($v);
                $result[] = $k . ': ' . $v;
            }
            return '{ ' . join(', ', $result) . ' }';
        }
    }
}