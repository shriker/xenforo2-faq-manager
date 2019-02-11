<?php

namespace Shriker\Faq\ApprovalQueue;

use XF\ApprovalQueue\AbstractHandler;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * approval_item_question
 */
class Question extends AbstractHandler
{
	protected function canActionContent(Entity $content, &$error = null)
	{
		/** @var $content \Shriker\Faq\Entity\Question */
		return $content->canApproveUnapprove($error);
	}

	public function getEntityWith()
	{
		$visitor = \XF::visitor();

        return [
            'User'
        ];
	}

	public function actionApprove(\Shriker\Faq\Entity\Question $question)
	{
		/** @var \Shriker\Faq\Service\Question\Approve $approver */
		$approver = \XF::service('Shriker\Faq:Question\Approve', $question);
		$approver->setNotifyRunTime(1);
		$approver->approve();
	}

	public function actionDelete(\Shriker\Faq\Entity\Question $question)
	{
		$this->quickUpdate($question, 'question_state', 'deleted');
	}
}
