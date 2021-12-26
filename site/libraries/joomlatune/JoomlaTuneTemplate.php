<?php
namespace JcommentsTeam\Component\Jcomments\Site\Libraries\Joomlatune;

defined('_JEXEC') or die;

/**
 * JoomlaTune base template class
 *
 * @abstract
 *
 */
class JoomlaTuneTemplate
{
    /**
     * Class constructor
     *
     */
    function __construct()
    {
        $this->_vars = array();
    }

    /**
     * Render template into string
     *
     * @abstract Implement in child classes
     * @return string
     */
    function render()
    {
    }

    /**
     * Sets global variables
     *
     * @param array $value array list of global variables
     * @return void
     */
    function setGlobalVars(&$value)
    {
        $this->_globals =& $value;
    }

    /**
     * Fetches and returns a given variable.
     *
     * @param string $name Variable name
     * @param mixed $default Default value if the variable does not exist
     * @return mixed Requested variable
     */
    function getVar($name, $default = null)
    {
        if (isset($this->_vars[$name])) {
            // fetch variable from local variables list
            return $this->_vars[$name];
        } else {
            if (isset($this->_globals[$name])) {
                // fetch variable from global variables list
                return $this->_globals[$name];
            } else {
                // return default value
                return $default;
            }
        }
    }

    /**
     * Set a template variable, creating it if it doesn't exist
     *
     * @param string $name The name of the variable
     * @param mixed $value The value of the variable
     * @return void
     */
    function setVar($name, $value)
    {
        $this->_vars[$name] = $value;
    }
}