<?php

namespace Shriker\Faq\Repository;

use XF\Entity\User;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

use XF\PrintableException;

class Question extends Repository
{
    public function findQuestionsForOverview(array $viewableCategoryIds = null, array $limits = [])
    {
        $limits = array_replace([
			'visibility' => true,
			'allowOwnPending' => false
        ], $limits);

        $questionFinder = $this->finder('Shriker\Faq:Question');

        return $questionFinder;
    }

    public function sendModeratorActionAlert(\Shriker\Faq\Entity\Question $update, $action, $reason = '', array $extra = [])
	{
		$question = $update->Question;

		if (!$question || !$question->user_id || !$question->User)
		{
			return false;
		}

		$extra = array_merge([
			'title' => $question->question,
			'update' => $update->question,
			'link' => $this->app()->router('public')->buildLink('nopath:help/faq', $update),
			'resourceLink' => $this->app()->router('public')->buildLink('nopath:help/faq', $question),
			'reason' => $reason
		], $extra);

		/** @var \XF\Repository\UserAlert $alertRepo */
		$alertRepo = $this->repository('XF:UserAlert');
		$alertRepo->alert(
			$question->User,
			0, '',
			'user', $resource->user_id,
			"question_{$action}", $extra
		);

		return true;
	}
}
