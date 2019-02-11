<?php

namespace Shriker\Faq\Notifier\Question;

use XF\Notifier\AbstractNotifier;

class Mention extends AbstractNotifier
{
	protected $update;

	public function __construct(\XF\App $app, \Shriker\Faq\Entity\Question $update)
	{
		parent::__construct($app);

		$this->update = $update;
	}

	public function canNotify(\XF\Entity\User $user)
	{
		return ($this->update->isVisible() && $user->user_id != $this->update->Question->user_id);
	}

	public function sendAlert(\XF\Entity\User $user)
	{
		$update = $this->update;
		$question = $update->Question;

		return $this->basicAlert(
			$user, $question->user_id, $question->username, 'question', $update->faq_id, 'mention'
		);
	}
}
