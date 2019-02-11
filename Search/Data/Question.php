<?php

namespace Shriker\Faq\Search\Data;

use XF\Mvc\Entity\Entity;
use XF\Search\Data\AbstractData;
use XF\Search\IndexRecord;
use XF\Search\MetadataStructure;
use XF\Search\Query\MetadataConstraint;

class Question extends AbstractData
{
	public function getEntityWith($forView = false)
	{
        $get = [];

		if ($forView)
		{
			$get[] = 'User';
		}

		return $get;
	}

	public function getIndexData(Entity $entity)
	{
		$index = IndexRecord::create('question', $entity->faq_id, [
			'title' => $entity->question,
			'message' => $entity->answer,
			'date' => $entity->answer_date,
			'user_id' => $entity->user_id,
			'discussion_id' => $entity->faq_id,
			'metadata' => [
                'question' => $entity->faq_id
            ]
		]);

		if (!$entity->isVisible())
		{
			$index->setHidden();
		}

		if ($entity->tags)
		{
			$index->indexTags($entity->tags);
		}

		return $index;
	}


	public function setupMetadataStructure(MetadataStructure $structure)
	{
	}

	public function getResultDate(Entity $entity)
	{
		return $entity->answer_date;
	}

	public function getTemplateData(Entity $entity, array $options = [])
	{
		return [
			'question' => $entity,
			'options' => $options
		];
	}

	public function canUseInlineModeration(Entity $entity, &$error = null)
	{
		/** @var \XFRM\Entity\ResourceItem $entity */
		return $entity->canUseInlineModeration($error);
	}

	public function getSearchableContentTypes()
	{
		return ['question'];
	}

	public function getSearchFormTab()
	{
		$visitor = \XF::visitor();
		if (!method_exists($visitor, 'canViewFaq') || !$visitor->canViewFaq())
		{
			return null;
		}

		return [
			'title' => \XF::phrase('faq_search_questions'),
			'order' => 300
		];
	}

	public function getSectionContext()
	{
		return 'help/faq';
	}

	public function getSearchFormData()
	{
		return [];
	}

	protected function getSearchableCategoryTree()
	{
	}

	protected function getPrefixListData()
	{
	}

	public function applyTypeConstraintsFromInput(\XF\Search\Query\Query $query, \XF\Http\Request $request, array &$urlConstraints)
	{
	}

	public function getTypePermissionConstraints(\XF\Search\Query\Query $query, $isOnlyType)
	{
		return [];
	}

	public function getGroupByType()
	{
		return 'question';
	}
}
