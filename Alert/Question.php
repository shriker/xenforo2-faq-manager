<?php

namespace Shriker\Faq\Alert;

use XF\Alert\AbstractHandler;

class Question extends AbstractHandler
{
	public function getEntityWith()
	{
		$visitor = \XF::visitor();

		return [];
	}

	public function getOptOutActions()
	{
		return [
			'mention',
			'reaction'
		];
	}

	public function getOptOutDisplayOrder()
	{
		return 300;
	}
}
