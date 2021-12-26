<?php

namespace JcommentsTeam\Component\Jcomments\Site\Libraries\Joomlatune;

defined('_JEXEC') or die;


class JoomlaTuneAjax
{
    var $aFunctions;
    var $aObjects;
    var $aFunctionRequestTypes;
    var $sRequestURI;
    var $sEncoding;

    function __construct($sRequestURI = "", $sEncoding = 'utf-8')
    {
        $this->aFunctions = array();
        $this->aFunctionRequestTypes = array();
        $this->aObjects = array();
        $this->aFunctionIncludeFiles = array();
        $this->sRequestURI = $sRequestURI;
        if ($this->sRequestURI == "") {
            $this->sRequestURI = $this->_detectURI();
        }
        $this->setCharEncoding($sEncoding);
    }

    function setCharEncoding($sEncoding)
    {
        $this->sEncoding = $sEncoding;
    }

    function registerFunction($mFunction, $sRequestType = 1)
    {
        if (is_array($mFunction)) {
            $this->aFunctions[$mFunction[0]] = 1;
            $this->aFunctionRequestTypes[$mFunction[0]] = $sRequestType;
            $this->aObjects[$mFunction[0]] = array_slice($mFunction, 1);
        } else {
            $this->aFunctions[$mFunction] = 1;
            $this->aFunctionRequestTypes[$mFunction] = $sRequestType;
        }
    }

    function processRequest()
    {
        return $this->processRequests();
    }

    function _isObjectCallback($sFunction)
    {
        if (array_key_exists($sFunction, $this->aObjects)) {
            return true;
        }
        return false;
    }

    function _callFunction($sFunction, $aArgs)
    {
        if ($this->_isObjectCallback($sFunction)) {
            $mReturn = call_user_func_array($this->aObjects[$sFunction], $aArgs);
        } else if (array_key_exists($sFunction, $this->aFunctions)) {
            $mReturn = call_user_func_array($sFunction, $aArgs);
        }
        return $mReturn;
    }

    function processRequests()
    {
        $sFunctionName = $_REQUEST["jtxf"];
        $aArgs = isset($_REQUEST["jtxa"]) ? $_REQUEST["jtxa"] : array();

        if (!array_key_exists($sFunctionName, $this->aFunctions)) {
            $oResponse = new JcommentsAjaxResponse();
            $oResponse->addAlert("Unknown Function $sFunctionName.");
        } else {
            $oResponse = $this->_callFunction($sFunctionName, $aArgs);
        }
        @header('content-type: text/plain; charset="' . $this->sEncoding . '"');
        print $oResponse->getOutput();
        exit();
    }

    function _detectURI()
    {
        $aURL = array();

        // Try to get the request URL
        if (!empty($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = str_replace(array('"', "'", '<', '>'), array('%22', '%27', '%3C', '%3E'), $_SERVER['REQUEST_URI']);
            $aURL = parse_url($_SERVER['REQUEST_URI']);
        }

        // Fill in the empty values
        if (empty($aURL['scheme'])) {
            if (!empty($_SERVER['HTTP_SCHEME'])) {
                $aURL['scheme'] = $_SERVER['HTTP_SCHEME'];
            } else {
                $aURL['scheme'] = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') ? 'https' : 'http';
            }
        }

        if (empty($aURL['host'])) {
            if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
                if (strpos($_SERVER['HTTP_X_FORWARDED_HOST'], ':') > 0) {
                    list($aURL['host'], $aURL['port']) = explode(':', $_SERVER['HTTP_X_FORWARDED_HOST']);
                } else {
                    $aURL['host'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
                }
            } else if (!empty($_SERVER['HTTP_HOST'])) {
                if (strpos($_SERVER['HTTP_HOST'], ':') > 0) {
                    list($aURL['host'], $aURL['port']) = explode(':', $_SERVER['HTTP_HOST']);
                } else {
                    $aURL['host'] = $_SERVER['HTTP_HOST'];
                }
            } else if (!empty($_SERVER['SERVER_NAME'])) {
                $aURL['host'] = $_SERVER['SERVER_NAME'];
            } else {
                print "Error: ajax failed to automatically identify your Request URI.";
                print "Please set the Request URI explicitly when you instantiate the jtajax object.";
                exit();
            }
        }

        if (empty($aURL['port']) && !empty($_SERVER['SERVER_PORT'])) {
            $aURL['port'] = $_SERVER['SERVER_PORT'];
        }

        if (empty($aURL['path'])) {
            if (!empty($_SERVER['PATH_INFO'])) {
                $sPath = parse_url($_SERVER['PATH_INFO']);
            } else {
                $sPath = parse_url($_SERVER['PHP_SELF']);
            }
            $aURL['path'] = str_replace(array('"', "'", '<', '>'), array('%22', '%27', '%3C', '%3E'), $sPath['path']);
            unset($sPath);
        }

        if (!empty($aURL['query'])) {
            $aURL['query'] = '?' . $aURL['query'];
        }

        // Build the URL: Start with scheme, user and pass
        $sURL = $aURL['scheme'] . '://';
        if (!empty($aURL['user'])) {
            $sURL .= $aURL['user'];
            if (!empty($aURL['pass'])) {
                $sURL .= ':' . $aURL['pass'];
            }
            $sURL .= '@';
        }

        // Add the host
        $sURL .= $aURL['host'];

        // Add the port if needed
        if (!empty($aURL['port']) && (($aURL['scheme'] == 'http' && $aURL['port'] != 80) || ($aURL['scheme'] == 'https' && $aURL['port'] != 443))) {
            $sURL .= ':' . $aURL['port'];
        }

        // Add the path and the query string
        $sURL .= $aURL['path'] . @$aURL['query'];

        // Clean up
        unset($aURL);
        return $sURL;
    }
}
