<?php

namespace Shriker\Faq\Service\Question;

use Shriker\Faq\Entity\Question;

class Edit extends \XF\Service\AbstractService
{
    use \XF\Service\ValidateAndSavableTrait;

    protected $question;
    protected $updatePreparer;
    protected $alert = false;
    protected $alertReason = '';

    public function __construct(\XF\App $app, Question $question)
	{
		parent::__construct($app);
		$this->question = $question;
		$this->updatePreparer = $this->service('Shriker\Faq:Question\Preparer', $question);
    }

    public function getQuestion()
	{
		return $this->question;
    }

    public function getUpdatePreparer()
	{
		return $this->updatePreparer;
    }

    public function setQuestion($message, $format = true)
	{
		return $this->updatePreparer->setQuestion($message, $format);
    }

    public function setAnswer($message, $format = true)
	{
		return $this->updatePreparer->setAnswer($message, $format);
	}

    public function setAttachmentHash($hash)
	{
		$this->updatePreparer->setAttachmentHash($hash);
	}

	public function setSendAlert($alert, $reason = null)
	{
		$this->alert = (bool)$alert;
		if ($reason !== null)
		{
			$this->alertReason = $reason;
		}
	}

	public function checkForSpam()
	{
		if ($this->question->question_state == 'visible' && \XF::visitor()->isSpamCheckRequired())
		{
			$this->updatePreparer->checkForSpam();
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
		$visitor = \XF::visitor();

		$db = $this->db();
		$db->beginTransaction();

        $question->save(true, false);
        $this->updatePreparer->afterUpdate(); // associate attachments, etc, via preparer

		if ($question->question_state == 'visible' && $this->alert && $update->Question->user_id != $visitor->user_id)
		{
			/** @var \Shriker\Faq\Repository\Question $updateRepo */
			$updateRepo = $this->repository('Shriker\Faq:Question');
			$updateRepo->sendModeratorActionAlert($this->question, 'edit', $this->alertReason);
		}

		$db->commit();

		return $question;
    }

    public function afterUpdate()
    {
    }

}
