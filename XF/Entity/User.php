<?php

namespace Shriker\Faq\XF\Entity;

use XF\Mvc\Entity\Structure;

class User extends XFCP_User
{
    public function canViewFaq(&$error = null)
	{
		return $this->hasPermission('faq', 'view');
	}

	public function canReactFaq(&$error = null)
	{
		return $this->hasPermission('faq', 'react');
	}

	public function canAskQuestion(&$error = null)
	{
		return ($this->user_id && $this->hasPermission('faq', 'askQuestion'));
	}

    public function hasFaqCategoryPermission($contentId, $permission)
	{
		return $this->PermissionSet->hasContentPermission('faq_category', $contentId, $permission);
    }

    public function cacheFaqCategoryPermissions(array $categoryIds = null)
	{
		if (is_array($categoryIds))
		{
			\XF::permissionCache()->cacheContentPermsByIds($this->permission_combination_id, 'faq_category', $categoryIds);
		}
		else
		{
			\XF::permissionCache()->cacheAllContentPerms($this->permission_combination_id, 'faq_category');
		}
    }

    public static function getStructure(Structure $structure)
	{
		$structure = parent::getStructure($structure);

        $structure->columns['faq_question_count'] = ['type' => self::UINT, 'default' => 0, 'forced' => true, 'changeLog' => false];
        $structure->columns['faq_answer_count'] = ['type' => self::UINT, 'default' => 0, 'forced' => true, 'changeLog' => false];

		return $structure;
	}

}
