<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @filename      JCommentsCaptcha.php
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */
namespace JcommentsTeam\Component\Jcomments\Site;

defined('_JEXEC') or die;

use JcommentsTeam\Component\Jcomments\Site\Libraries\Kcaptcha\KCaptcha;
use Joomla\CMS\Component\ComponentHelper;

/**
 * CAPTCHA - Automatic test to tell computers and humans apart
 * TODO: move class to another place
 * @since  4.0
 */
class JCommentsCaptcha
{
	public static function check($code)
	{
		@session_start();

		return (($code != '') && ($code == $_SESSION['comments-captcha-code']));
	}

	public static function destroy()
	{
		unset($_SESSION['comments-captcha-code']);
	}

	public static function image()
	{
		// Small hack to allow captcha display even if any notice or warning occurred
		$length = ob_get_length();

		if ($length !== false || $length > 0)
		{
			while (@ob_end_clean()) ;

			if (function_exists('ob_clean'))
			{
				@ob_clean();
			}
		}

		@session_start();

		$config = ComponentHelper::getParams('com_jcomments');
		$captchaOptions = array();

		// Get all Kcaptcha config values
		foreach ($config as $key => $params)
		{
			if (strpos($key, 'kcaptcha_') !== false)
			{
				$_key = substr($key, (int) strpos($key, '_') + 1);
				$captchaOptions[$_key] = $params;
			}
		}

		$captcha = new KCaptcha($captchaOptions);
		$captcha->render();
		$_SESSION['comments-captcha-code'] = $captcha->getKeyString();
		exit;
	}
}
