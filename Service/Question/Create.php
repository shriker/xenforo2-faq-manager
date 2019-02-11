<?php

namespace Shriker\Faq\Service\Question;

use Shriker\Faq\Entity\Question;

class Create extends \XF\Service\AbstractService
{
    use \XF\Service\ValidateAndSavableTrait;

    protected $question;
    protected $answer;
    protected $updatePreparer;

    public function __construct(\XF\App $app, Question $question)
	{
		parent::__construct($app);
		$this->question = $question;
		$this->updatePreparer = $this->service('Shriker\Faq:Question\Preparer', $this->question);
		$this->setupDefaults();
    }

    public function getQuestion()
	{
		return $this->question;
    }

    public function getAnswer()
	{
		return $this->answer;
    }

    public function getUpdatePreparer()
	{
		return $this->updatePreparer;
    }

    public function logIp($logIp)
	{
		$this->updatePreparer->logIp($logIp);
    }

    public function setAttachmentHash($hash)
	{
		$this->updatePreparer->setAttachmentHash($hash);
	}

    protected function setupDefaults()
	{
        $visitor = \XF::visitor();
		$this->question->user_id = $visitor->user_id;
        $this->question->username = $visitor->username;
        $this->question->answer = '';
        $this->question->answer_user_id = '';
        $this->question->answer_username = '';
    }

    public function sendNotifications()
	{
		if ($this->question->isVisible())
		{
			/** @var \Shriker\Faq\Service\Question\Notify $notifier */
			$notifier = $this->service('Shriker\Faq:Question\Notify', $this->question, 'question');
			$notifier->setMentionedUserIds($this->updatePreparer->getMentionedUserIds());
			$notifier->notifyAndEnqueue(3);
		}
    }

    protected function finalSetup()
	{
    }

    protected function _validate()
	{
        $this->finalSetup();
        $question = $this->question;
        $question->preSave();
        $errors = $question->getErrors();
        return $errors;
    }

    protected function _save()
	{
        $question = $this->question;
        $db = $this->db();
        $db->beginTransaction();

        $question->save(true, false);
        $this->updatePreparer->afterInsert(); // associate attachments, etc, via preparer

        $db->commit();

        return $question;
    }

    public function afterInsert()
    {
    }

}
