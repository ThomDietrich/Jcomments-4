<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2013 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

class JCommentsControllerSmilies extends JCommentsControllerList
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('unpublish', 'publish');
	}

	public function display($cachable = false, $urlparams = array())
	{
		$this->input->set('view', 'smilies');

		parent::display($cachable, $urlparams);
	}

	public function publish()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$cid   = $this->input->get('cid', array(), 'array');
		$data  = array('publish' => 1, 'unpublish' => 0);
		$task  = $this->getTask();
		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		if (!empty($cid))
		{
			$model = $this->getModel();
			$model->publish($cid, $value);

			$this->getModel('smiley')->saveLegacy();
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	public function delete()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$cid = $this->input->get('cid', array(), 'array');

		if (!empty($cid))
		{
			$model = $this->getModel();
			$model->delete($cid);

			$this->getModel('smiley')->saveLegacy();
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	public function reorder()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$ids = $this->input->post->get('cid', array(), 'array');
		$inc = ($this->getTask() == 'orderup') ? -1 : +1;

		$model  = $this->getModel();
		$return = $model->reorder($ids, $inc);

		if ($return === false)
		{
			$message = Text::sprintf('JLIB_APPLICATION_ERROR_REORDER_FAILED', $model->getError());
			$this->setRedirect(
				Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false),
				$message, 'error'
			);

			return false;
		}
		else
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_SUCCESS_ITEM_REORDERED'));
			$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));

			return true;
		}
	}

	public function saveorder()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$pks   = $this->input->post->get('cid', array(), 'array');
		$order = $this->input->post->get('order', array(), 'array');

		ArrayHelper::toInteger($pks);
		ArrayHelper::toInteger($order);

		$model = $this->getModel();

		$return = $model->saveorder($pks, $order);

		if ($return === false)
		{
			$message = Text::sprintf('JLIB_APPLICATION_ERROR_REORDER_FAILED', $model->getError());
			$this->setRedirect(
				Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false),
				$message, 'error'
			);

			return false;
		}
		else
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_SUCCESS_ORDERING_SAVED'));
			$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));

			return true;
		}
	}

	public function saveOrderAjax()
	{
		$pks   = $this->input->post->get('cid', array(), 'array');
		$order = $this->input->post->get('order', array(), 'array');

		ArrayHelper::toInteger($pks);
		ArrayHelper::toInteger($order);

		$model = $this->getModel();

		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			$this->getModel('smiley')->saveLegacy();
			echo "1";
		}

		Factory::getApplication()->close();
	}
}
