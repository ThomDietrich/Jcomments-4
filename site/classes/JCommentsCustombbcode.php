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

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 * JComments Custom BBCode class
 */
class JCommentsCustombbcode
{
	protected $codes = array();

	protected $patterns = array();

	protected $filter_patterns = array();

	protected $html_replacements = array();

	protected $text_replacements = array();

	public function __construct()
	{
		$db  = Factory::getContainer()->get('DatabaseDriver');
		$acl = JCommentsFactory::getACL();

		ob_start();

		$query = $db->getQuery(true);

		$query->select(
			$db->quoteName(
				array(
					'id', 'name', 'simple_pattern', 'simple_replacement_html', 'simple_replacement_text', 'pattern',
					'replacement_html', 'replacement_text', 'button_acl', 'button_open_tag', 'button_close_tag',
					'button_title', 'button_prompt', 'button_image', 'button_css', 'button_enabled'
				)
			)
		)
			->from($db->quoteName('#__jcomments_custom_bbcodes'))
			->where($db->quoteName('published') . ' = 1')
			->order($db->escape('ordering') . ' ASC');

		$db->setQuery($query);
		$codes = $db->loadObjectList();

		if (count($codes))
		{
			foreach ($codes as $code)
			{
				// Fix \w pattern issue for UTF-8 encoding
				// details: http://www.phpwact.org/php/i18n/utf-8#w_w_b_b_meta_characters
				$code->pattern = preg_replace('#(\\\w)#u', '\p{L}', $code->pattern);

				// Check button permission
				if ($acl->enableCustomBBCode($code->button_acl))
				{
					if ($code->button_image != '')
					{
						if (strpos($code->button_image, Uri::base()) === false)
						{
							$code->button_image = Uri::base() . trim($code->button_image, '/');
						}
					}

					$this->codes[] = $code;
				}
				else
				{
					$this->filter_patterns[] = '#' . $code->pattern . '#ismu';
				}

				$this->patterns[]          = '#' . $code->pattern . '#ismu';
				$this->html_replacements[] = $code->replacement_html;
				$this->text_replacements[] = $code->replacement_text;
			}
		}

		ob_end_clean();
	}

	public function getList()
	{
		return $this->codes;
	}

	public function filter($str, $forceStrip = false)
	{
		if (count($this->filter_patterns))
		{
			ob_start();
			$filterReplacement = $this->text_replacements;
			$str               = preg_replace($this->filter_patterns, $filterReplacement, $str);
			ob_end_clean();
		}

		if ($forceStrip === true)
		{
			ob_start();
			$str = preg_replace($this->patterns, $this->text_replacements, $str);
			ob_end_clean();
		}

		return $str;
	}

	public function replace($str, $textReplacement = false)
	{
		if (count($this->patterns))
		{
			ob_start();
			$str = preg_replace($this->patterns, ($textReplacement ? $this->text_replacements : $this->html_replacements), $str);
			ob_end_clean();
		}

		return $str;
	}
}
