<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */
namespace JcommentsTeam\Component\Jcomments\Site\helpers;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;

/**
 * JComments Content Plugin Helper
 *
 */
class JCommentsContent
{
	/**
	 *
	 * @param   object  $row           The content item object
	 * @param   array   $patterns
	 * @param   array   $replacements
	 *
	 * @return  void
	 */
	protected static function _processTags($row, $patterns = array(), $replacements = array())
	{
		if (count($patterns) > 0)
		{
			ob_start();

			$keys = array('introtext', 'fulltext', 'text');

			foreach ($keys as $key)
			{
				if (isset($row->$key))
				{
					$row->$key = preg_replace($patterns, $replacements, $row->$key);
				}
			}

			ob_end_clean();
		}
	}

	/**
	 * Searches given tag in content object
	 *
	 * @param   object  $row      The content item object
	 * @param   string  $pattern
	 *
	 * @return  boolean True if any tag found, False otherwise
	 */
	protected static function _findTag($row, $pattern)
	{
		$keys = array('introtext', 'fulltext', 'text');

		foreach ($keys as $key)
		{
			if (isset($row->$key) && preg_match($pattern, $row->$key))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Replaces or removes commenting systems tags like {moscomment}, {jomcomment} etc
	 *
	 * @param   object   $row         The content item object
	 * @param   boolean  $removeTags  Remove all 3rd party tags or replace it to JComments tags?
	 *
	 * @return  void
	 */
	public static function processForeignTags(&$row, $removeTags = false)
	{
		if (false == $removeTags)
		{
			$patterns     = array('#\{(jomcomment|easycomments|KomentoEnable)\}#is', '#\{(\!jomcomment|KomentoDisable)\}#is', '#\{KomentoLock\}#is');
			$replacements = array('{jcomments on}', '{jcomments off}', '{jcomments lock}');
		}
		else
		{
			$patterns     = array('#\{(jomcomment|easycomments|KomentoEnable|KomentoDisable|KomentoLock)\}#is');
			$replacements = array('');
		}

		self::_processTags($row, $patterns, $replacements);
	}

	/**
	 * Return true if one of text fields contains {jcomments on} tag
	 *
	 * @param   object  $row  Content object
	 *
	 * @return  boolean True if {jcomments on} found, False otherwise
	 */
	public static function isEnabled($row)
	{
		return self::_findTag($row, '/{jcomments\s+on}/is');
	}

	/**
	 * Return true if one of text fields contains {jcomments off} tag
	 *
	 * @param   object  $row  Content object
	 *
	 * @return  boolean True if {jcomments off} found, False otherwise
	 */
	public static function isDisabled($row)
	{
		return self::_findTag($row, '/{jcomments\s+off}/is');
	}

	/**
	 * Return true if one of text fields contains {jcomments lock} tag
	 *
	 * @param   object  $row  Content object
	 *
	 * @return  boolean True if {jcomments lock} found, False otherwise
	 */
	public static function isLocked($row)
	{
		return self::_findTag($row, '/{jcomments\s+lock}/is');
	}

	/**
	 * Clears all JComments tags from content item
	 *
	 * @param   object  $row  Content object
	 *
	 * @return  void
	 */
	public static function clear(&$row)
	{
		$patterns     = array('/{jcomments\s+(off|on|lock)}/is');
		$replacements = array('');

		self::_processTags($row, $patterns, $replacements);
	}

	/**
	 * Checks if comments are enabled for specified category
	 *
	 * @param   integer  $id  Category ID
	 *
	 * @return  boolean
	 */
	public static function checkCategory($id)
	{
		$config     = ComponentHelper::getParams('com_jcomments');
		$categories = (array)$config->get('enable_categories');

		return (in_array('*', $categories) || in_array($id, $categories));
	}

	/**
	 * Checks if comments are enabled for specified category
	 *
	 * @param   object  $comment  Comment object
	 *
	 * @return  string
	 */
	public static function getCommentAuthorName($comment)
	{
		$name = '';

		if ($comment != null)
		{
			$config = ComponentHelper::getParams('com_jcomments');

			if ($comment->userid && $config->get('display_author') == 'username' && $comment->username != '')
			{
				$name = $comment->username;
			}
			else
			{
				$name = $comment->name ?: 'Guest';
			}
		}

		return $name;
	}
}
