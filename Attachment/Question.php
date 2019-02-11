<?php

namespace Shriker\Faq\Attachment;

use XF\Attachment\AbstractHandler;
use XF\Entity\Attachment;
use XF\Mvc\Entity\Entity;

class Question extends AbstractHandler
{
    public function getContainerWith()
	{
		$visitor = \XF::visitor();

		return ['User'];
    }

    public function canView(Attachment $attachment, Entity $container, &$error = null)
    {
        return true;
    }

    public function canManageAttachments(array $context, &$error = null)
    {
        return true;
    }

    public function onAttachmentDelete(Attachment $attachment, Entity $container = null)
	{
		if (!$container)
		{
			return;
		}

		/** @var \Shriker\Faq\Entity\Question $container */
		$container->attach_count--;
		$container->save();
    }

    public function getConstraints(array $context)
	{
		/** @var \XF\Repository\Attachment $attachRepo */
		$attachRepo = \XF::repository('XF:Attachment');
		$constraints = $attachRepo->getDefaultAttachmentConstraints();
		$constraints['extensions'] = ['jpg', 'jpeg', 'jpe', 'png', 'gif'];
		return $constraints;
    }

    public function getContainerIdFromContext(array $context)
	{
		return isset($context['faq_id']) ? intval($context['faq_id']) : null;
    }

    public function getContainerLink(Entity $container, array $extraParams = [])
	{
		return \XF::app()->router('public')->buildLink('help/faq', $container, $extraParams);
    }

    public function getContext(Entity $entity = null, array $extraContext = [])
	{
        if ($entity instanceof \Shriker\Faq\Entity\Question)
		{
			$extraContext['faq_id'] = $entity->faq_id;
        }
        else
		{
			throw new \InvalidArgumentException("Entity must be a question");
		}

        return $extraContext;
    }

}
