<?php

namespace Shriker\Faq\Service\Question;

use Shriker\Faq\Entity\Question;

class Approve extends \XF\Service\AbstractService
{
	/**
	 * @var Question
	 */
	protected $question;

	protected $notifyRunTime = 3;

	public function __construct(\XF\App $app, Question $question)
	{
		parent::__construct($app);
		$this->question = $question;
	}

	public function getQuestion()
	{
		return $this->question;
	}

	public function setNotifyRunTime($time)
	{
		$this->notifyRunTime = $time;
	}

	public function approve()
	{
        if ($this->question->question_state == 'moderated')
		{
			$this->question->question_state = 'visible';
			$this->question->save();

			$this->onApprove();
			return true;
		}
		else
		{
			return false;
		}
	}

	protected function onApprove()
	{
        /** @var \Shriker\Faq\Service\Question\Notify $notifier */
        $notifier = $this->service('Shriker\Faq:Question\Notify', $this->question, 'question');
        $notifier->notifyAndEnqueue($this->notifyRunTime);
	}
}
