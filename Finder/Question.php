<?php

namespace Shriker\Faq\Finder;

use XF\Mvc\Entity\Finder;

class Question extends Finder
{
    public function useDefaultOrder()
	{
		$defaultOrder = $this->app()->options()->faqListDefaultOrder ?: 'last_update';
		$defaultDir = $defaultOrder == 'title' ? 'asc' : 'desc';

		$this->setDefaultOrder($defaultOrder, $defaultDir);

		return $this;
	}
}