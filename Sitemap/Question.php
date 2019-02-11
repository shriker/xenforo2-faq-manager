<?php

namespace Shriker\Faq\Sitemap;

use XF\Sitemap\AbstractHandler;
use XF\Sitemap\Entry;

class Question extends AbstractHandler
{
    public function getRecords($start)
	{
		$user = \XF::visitor();

		$ids = $this->getIds('xf_faq_question', 'faq_id', $start);

		$finder = $this->app->finder('Shriker\Faq:Question');
		$resources = $finder
			->where('faq_id', $ids)
			->order('faq_id')
			->fetch();

		return $resources;
    }
    
    public function getEntry($record)
	{
		$url = $this->app->router('public')->buildLink('canonical:help/faq', $record);
		return Entry::create($url, [
			'lastmod' => $record->answer_date
		]);
    }
    
    public function isIncluded($record)
	{
		/** @var $record \Shriker\Faq\Entity\Question */
		if (!$record->isVisible())
		{
			return false;
		}
		return $record->canView();
	}

}