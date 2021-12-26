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
namespace JcommentsTeam\Component\Jcomments\Site\classes;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

/**
 * JComments common text functions
 *
 * @since  3.0
 */
class JCommentsText
{
	/**
	 * Replaces newlines with HTML line breaks
	 *
	 * @param   string  $text  The input string.
	 *
	 * @return  string  Returns the altered string.
	 *
	 * @since   3.0
	 */
	public static function nl2br($text)
	{
		$text = preg_replace(array('/\r/u', '/^\n+/u', '/\n+$/u'), '', $text);

		return str_replace("\n", '<br />', $text);
	}

	/**
	 * Replaces HTML line breaks with newlines
	 *
	 * @param   string  $text  The input string.
	 *
	 * @return  string  Returns the altered string.
	 *
	 * @since   3.0
	 */
	public static function br2nl($text)
	{
		return str_replace('<br />', "\n", $text);
	}

	/**
	 * Escapes input string with slashes to use it in JavaScript
	 *
	 * @param   string  $text  The input string.
	 *
	 * @return  string  Returns the altered string.
	 *
	 * @since   3.0
	 */
	public static function jsEscape($text)
	{
		return addcslashes($text, "\\\\&\"\n\r<>'");
	}

	public static function url($s)
	{
		if (isset($s)
			&& preg_match('/^((http|https|ftp):\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,6}((:[0-9]{1,5})?\/.*)?$/i', $s))
		{
			$url = preg_replace('|[^a-z0-9-~+_.?#=&;,/:]|i', '', $s);
			$url = str_replace(';//', '://', $url);

			if ($url != '')
			{
				$url = (!strstr($url, '://')) ? 'http://' . $url : $url;

				return preg_replace('/&([^#])(?![a-z]{2,8};)/', '&#038;$1', $url);
			}
		}

		return '';
	}

	public static function censor($text)
	{
		if (!empty($text))
		{
			ob_start();

			$config      = ComponentHelper::getParams('com_jcomments');
			$lang        = Factory::getApplication()->getLanguage();
			$words       = $config->get('badwords');
			$replaceWord = self::getCensorReplace($config->get('censor_replace_fields'), $lang->getTag());

			if (!empty($words))
			{
				$words = preg_replace("#,+#", ',', preg_replace("#[\n|\r]+#", ',', $words));
				$words = explode(",", $words);

				if (is_array($words))
				{
					for ($i = 0, $n = count($words); $i < $n; $i++)
					{
						$word = trim($words[$i]);

						if ($word != '')
						{
							$word = str_replace('#', '\#', str_replace('\#', '#', $word));
							$txt  = trim(preg_replace('#' . $word . '#ismu', $replaceWord, $text));

							// Make safe from dummy bad words list
							if ($txt != '')
							{
								$text = $txt;
							}
						}
					}
				}
			}

			ob_end_clean();
		}

		return $text;
	}

	/**
	 * Cleans text of all formatting and scripting code
	 *
	 * @param   string  $text  The input string.
	 *
	 * @return  string  Returns the altered string.
	 *
	 * @since  3.0
	 */
	public static function cleanText($text)
	{
		$text = JCommentsFactory::getBBCode()->filter($text, true);

		if ((int) ComponentHelper::getParams('com_jcomments')->get('enable_custom_bbcode'))
		{
			$text = JCommentsFactory::getCustomBBCode()->filter($text, true);
		}

		$text = str_replace('<br />', ' ', $text);
		$text = preg_replace('#(\s){2,}#ismu', '\\1', $text);
		$text = preg_replace('#<script[^>]*>.*?</script>#ismu', '', $text);
		$text = preg_replace('#<a\s+.*?href="([^"]+)"[^>]*>([^<]+)<\/a>#ismu', '\2 (\1)', $text);
		$text = preg_replace('#<!--.+?-->#ismu', '', $text);
		$text = preg_replace('#&nbsp;|&amp;|&quot;#ismu', ' ', $text);

		$text = strip_tags($text);
		$text = htmlspecialchars($text);

		return html_entity_decode($text);
	}

	/**
	 * Get language aware message strings for comment rules, no access rights for comment, comments closed, user banned.
	 *
	 * @param   array   $messages  Array in subform format. E.g. array(subform => array(form => value, ...))
	 * @param   string  $field     Field name with parameter.
	 * @param   string  $lang      Language tag.
	 *
	 * @return  string  Returns the string according to current frontend language.
	 *
	 * @since   4.0
	 */
	public static function getMessagesBasedOnLanguage($messages, $field, $lang = '')
	{
		$data = array();

		foreach ($messages as $_message)
		{
			$data[$_message->lang] = $_message;
		}

		if (empty($lang) || $lang == '*')
		{
			$message = $data['*']->$field;
		}
		else
		{
			$message = in_array($field, $data) ? $data[$lang]->$field : $data['*']->$field;
		}

		return $message;
	}

	/**
	 * Get replacement string for current language.
	 *
	 * @param   array   $replaces  Array in subform format. E.g. array(subform => array(form => value, ...))
	 * @param   string  $lang      Language tag.
	 *
	 * @return  string  Returns the string according to current frontend language.
	 *
	 * @since   4.0
	 */
	private static function getCensorReplace($replaces, $lang)
	{
		$data = array();

		foreach ($replaces as $replacement)
		{
			$data[$replacement->lang] = $replacement->censor_replace_word;
		}

		return (empty($lang) || $lang == '*' || !array_key_exists($lang, $data)) ? $data['*'] : $data[$lang];
	}
}
