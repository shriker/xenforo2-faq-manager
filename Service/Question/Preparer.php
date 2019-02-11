<?php

namespace Shriker\Faq\Service\Question;

use Shriker\Faq\Entity\Question;

class Preparer extends \XF\Service\AbstractService
{
    protected $question;

	protected $attachmentHash;

	protected $logIp = true;

    protected $mentionedUsers = [];

    public function __construct(\XF\App $app, Question $question)
	{
		parent::__construct($app);
        $this->question = $question;
    }

    public function getQuestion()
	{
		return $this->question;
	}

	public function logIp($logIp)
	{
		$this->logIp = $logIp;
    }

    public function getMentionedUsers($limitPermissions = true)
	{
		if ($limitPermissions)
		{
			/** @var \XF\Entity\User $user */
			$user = $this->question->Question->User ?: $this->repository('XF:User')->getGuestUser();
			return $user->getAllowedUserMentions($this->mentionedUsers);
		}
		else
		{
			return $this->mentionedUsers;
		}
    }

    public function getMentionedUserIds($limitPermissions = true)
	{
		return array_keys($this->getMentionedUsers($limitPermissions));
    }

    public function setQuestion($message, $format = true, $checkValidity = true)
	{
		$preparer = $this->getMessagePreparer($format);
		$this->question->question = $preparer->prepare($message, $checkValidity);
		$this->question->embed_metadata = $preparer->getEmbedMetadata();
		$this->mentionedUsers = $preparer->getMentionedUsers();
		return $preparer->pushEntityErrorIfInvalid($this->question);
    }

    public function setAnswer($message, $format = true, $checkValidity = true)
	{
		$preparer = $this->getMessagePreparer($format);
		$this->question->answer = $preparer->prepare($message, $checkValidity);
		$this->question->embed_metadata = $preparer->getEmbedMetadata();
		$this->mentionedUsers = $preparer->getMentionedUsers();
		return $preparer->pushEntityErrorIfInvalid($this->question);
    }

    /**
	 * @param bool $format
	 *
	 * @return \XF\Service\Message\Preparer
	 */
	protected function getMessagePreparer($format = true)
	{
        $options = $this->app->options();

		/** @var \XF\Service\Message\Preparer $preparer */
        $preparer = $this->service('XF:Message\Preparer', 'question', $this->question);
		if (!$format)
		{
			$preparer->disableAllFilters();
		}
		return $preparer;
	}

	public function setAttachmentHash($hash)
	{
		$this->attachmentHash = $hash;
	}

	public function checkForSpam()
	{
		$question = $this->question;

		/** @var \XF\Entity\User $user */
		$user = $question->User ?: $this->repository('XF:User')->getGuestUser($question->username);
		$checker = $this->app->spam()->contentChecker();
		$checker->check($user, $question->question, [
			'permalink' => $this->app->router('public')->buildLink('canonical:help/faq', $question),
			'content_type' => 'question'
		]);

		$decision = $checker->getFinalDecision();
		switch ($decision)
		{
			case 'moderated':
			    $question->question_state = 'moderated';
			    break;

			case 'denied':
				$checker->logSpamTrigger('question');
				$question->error(\XF::phrase('your_content_cannot_be_submitted_try_later'));
				break;
		}
	}

	public function afterInsert()
	{
		if ($this->attachmentHash)
		{
			$this->associateAttachments($this->attachmentHash);
		}

		if ($this->logIp)
		{
			$ip = ($this->logIp === true ? $this->app->request()->getIp() : $this->logIp);
			$this->writeIpLog($ip);
		}

        $question = $this->question;

		$checker = $this->app->spam()->contentChecker();
        $checker->logSpamTrigger('question', $question->faq_id);
	}

	public function afterUpdate()
	{
		if ($this->attachmentHash)
		{
			$this->associateAttachments($this->attachmentHash);
		}

        $question = $this->question;

		$checker = $this->app->spam()->contentChecker();
		$checker->logSpamTrigger('question', $question->faq_id);
	}

	protected function associateAttachments($hash)
	{
		$question = $this->question;

		/** @var \XF\Service\Attachment\Preparer $inserter */
		$inserter = $this->service('XF:Attachment\Preparer');
		$associated = $inserter->associateAttachmentsWithContent($hash, 'question', $question->faq_id);
		if ($associated)
		{
			$question->fastUpdate('attach_count', $question->attach_count + $associated);
		}
	}

	protected function writeIpLog($ip)
	{
		$question = $this->question;

		/** @var \XF\Repository\IP $ipRepo */
		$ipRepo = $this->repository('XF:Ip');
		$ipEnt = $ipRepo->logIp($question->user_id, $ip, 'question', $question->faq_id);
		if ($ipEnt)
		{
			$question->fastUpdate('ip_id', $ipEnt->ip_id);
		}
	}
}
