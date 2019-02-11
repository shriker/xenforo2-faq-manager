<?php

namespace Shriker\Faq\Reaction;

use XF\Reaction\AbstractHandler;
use XF\Mvc\Entity\Entity;

/**
 * Reaction Templates:
 * 	- alert_question_reaction
 *  - news_feed_item_question_reaction
 *  - reaction_item_question
 * 
 * Reaction Phrases:
 *  - faq_x_reacted_to_answer_y
 *  - faq_x_reacted_to_ys_answer_z
 * 
 * Content Types
 *  - alert_handler_class
 *  - reaction_handler_class 
 */
class Question extends AbstractHandler
{
	public function reactionsCounted(Entity $entity)
	{
        return 'visible';
	}
}

