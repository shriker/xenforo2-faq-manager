<?php

namespace Shriker\Faq\Service\Question;

use Shriker\Faq\Entity\Question;
use XF\Service\AbstractNotifier;

class Notify extends AbstractNotifier
{
	/**
	 * @var Question
	 */
	protected $update;

	protected $actionType;

	public function __construct(\XF\App $app, Question $update, $actionType)
	{
		parent::__construct($app);

		switch ($actionType)
		{
			case 'update':
			case 'question':
				break;
			default:
				throw new \InvalidArgumentException("Unknown action type '$actionType'");
		}

		$this->actionType = $actionType;
		$this->update = $update;
	}

	public static function createForJob(array $extraData)
	{
		$update = \XF::app()->find('Shriker\Faq:Question', $extraData['updateId'], ['Question']);
		if (!$update)
		{
			return null;
		}

		return \XF::service('Shriker\Faq:Question\Notify', $update, $extraData['actionType']);
	}

	protected function getExtraJobData()
	{
		return [
			'updateId' => $this->update->faq_id,
			'actionType' => $this->actionType
		];
	}

	protected function loadNotifiers()
	{
		return [
			'mention' => $this->app->notifier('Shriker\Faq:Question\Mention', $this->update)
		];
	}

	protected function loadExtraUserData(array $users)
	{

	}

	protected function canUserViewContent(\XF\Entity\User $user)
	{
		return \XF::asVisitor(
			$user,
			function() { return $this->update->canView(); }
		);
	}
}
