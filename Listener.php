<?php

namespace Shriker\Faq;

use XF\Mvc\Entity\Entity;

class Listener
{
    public static function appSetup(\XF\App $app)
	{
        $container = $app->container();
    }

    public static function criteriaUser($rule, array $data, \XF\Entity\User $user, &$returnValue)
	{

    }

    public static function userMergeCombine(\XF\Entity\User $target, \XF\Entity\User $source, \XF\Service\User\Merge $mergeService)
	{
        // Merge question & answer counts
        $target->faq_question_count += $source->faq_question_count;
        $target->faq_answer_count += $source->faq_answer_count;
	}
}
