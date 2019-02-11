<?php

namespace Shriker\Faq\Entity;

use XF\BbCode\RenderableContentInterface;

use XF\Entity\User;
use XF\Mvc\Entity\Entity;
use XF\Entity\ReactionTrait;
use XF\Mvc\Entity\Structure;

class Question extends \XF\Mvc\Entity\Entity implements RenderableContentInterface
{
    use ReactionTrait;

    public function hasPermission()
    {
        return true;
    }

	public function canView(&$error = null)
	{
		return true;
    }

    public function canViewAttachments(&$error = null)
    {
        // @TODO permissions
        $visitor = \XF::visitor();
        return true;

       // return ($visitor->hasPermission('faq', 'viewAttachments'));
    }

    public function canUploadAndManageAttachments()
    {
        // @TODO permissions
        $visitor = \XF::visitor();
        return true;
    }

    public function canApproveUnapprove(&$error = null)
	{
		return (
			\XF::visitor()->user_id
			&& $this->hasPermission('approveUnapprove')
		);
    }

    public function canUseInlineModeration(&$error = null)
	{
		$visitor = \XF::visitor();
		return ($visitor->user_id && $this->hasPermission('inlineMod'));
	}

    public function isAttachmentEmbedded($attachmentId)
	{
		if (!$this->embed_metadata)
		{
			return false;
		}

		if ($attachmentId instanceof \XF\Entity\Attachment)
		{
			$attachmentId = $attachmentId->attachment_id;
		}

		return isset($this->embed_metadata['attachments'][$attachmentId]);
	}

	public function isVisible()
	{
		return ($this->question_state == 'visible');
    }

    public function canEdit(&$error = null)
	{
		$visitor = \XF::visitor();
		if (!$visitor->user_id)
		{
			return false;
		}

		return true;
    }

    public function isIgnored()
	{
		return \XF::visitor()->isIgnoring($this->user_id);
	}

	public function canReact(&$error = null)
	{
		$visitor = \XF::visitor();
		if (!$visitor->user_id)
		{
			return false;
        }

        if ($this->question_state != 'visible')
		{
			return false;
        }

        if ($this->user_id == $visitor->user_id)
		{
			//$error = \XF::phraseDeferred('reacting_to_your_own_content_is_considered_cheating');
			//return false;
		}

		return true;
    }

    public function canSendModeratorActionAlert()
	{
		$visitor = \XF::visitor();

		if (!$visitor->user_id || $visitor->user_id == $this->user_id)
		{
			return false;
		}

		if ($this->question_state != 'visible')
		{
			return false;
		}

		return true;
	}

	public function getBbCodeRenderOptions($context, $type)
	{
		return [
            'entity' => $this,
            'attachments' => $this->attach_count ? $this->Attachments : [],
            'viewAttachments' => true,
		];
	}

    public static function getStructure(Structure $structure)
	{
        $structure->table = 'xf_faq_question';
		$structure->shortName = 'Shriker/Faq:Question';
		$structure->primaryKey = 'faq_id';
		$structure->contentType = 'question';

        $structure->columns = [
			'faq_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
			'question' => ['type' => self::STR, 'maxLength' => 150,
				'required' => 'faq_please_enter_a_question',
				'censor' => true
			],
			'answer' => ['type' => self::STR,
				'censor' => true
			],
			// User who asks question
			'user_id' => ['type' => self::UINT, 'required' => true],
			'username' => ['type' => self::STR, 'maxLength' => 50,
				'required' => 'please_enter_valid_name'
            ],
            'attach_count' => ['type' => self::UINT, 'max' => 65535, 'forced' => true, 'default' => 0],
            'ip_id' => ['type' => self::UINT, 'default' => 0],
            // User who answers question
			'answer_user_id' => ['type' => self::UINT],
			'answer_username' => ['type' => self::STR, 'maxLength' => 50],
            'question_state' => ['type' => self::STR, 'default' => 'visible',
				'allowedValues' => ['visible', 'moderated', 'deleted'], 'api' => true
			],
			'submit_date' => ['type' => self::UINT, 'default' => \XF::$time],
            'answer_date' => ['type' => self::UINT, 'default' => \XF::$time],
            'embed_metadata' => ['type' => self::JSON_ARRAY, 'nullable' => true, 'default' => null],
			'tags' => ['type' => self::JSON_ARRAY, 'default' => []]
        ];
        $structure->getters = [
			'question_ids' => true,
		];

        $structure->behaviors = [
			'XF:Reactable' => ['stateField' => 'question_state'],
			//'XF:Taggable' => ['stateField' => 'question_state'],
		];

        $structure->relations = [
            'Question' => [
				'entity' => 'Shriker/Faq:Question',
				'type' => Entity::TO_ONE,
				'conditions' => 'faq_id',
				'primary' => true
            ],
			'User' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => 'user_id',
				'primary' => true
            ],
            'Attachments' => [
                'entity' => 'XF:Attachment',
                'type' => self::TO_MANY,
                'conditions' => [
                    ['content_type', '=', 'question'],
                    ['content_id', '=', '$faq_id']
                ],
                'with' => 'Data',
                'order' => 'attach_date'
            ],
            'DeletionLog' => [
				'entity' => 'XF:DeletionLog',
				'type' => self::TO_ONE,
				'conditions' => [
					['content_type', '=', 'question'],
					['content_id', '=', '$faq_id']
				],
				'primary' => true
			],
            'ApprovalQueue' => [
				'entity' => 'XF:ApprovalQueue',
				'type' => self::TO_ONE,
				'conditions' => [
					['content_type', '=', 'question'],
					['content_id', '=', '$faq_id']
				],
				'primary' => true
			]
        ];

        $structure->options = [
			'log_moderator' => true
		];

		static::addReactableStructureElements($structure);

		return $structure;
    }

	public function _preSave()
	{
        //$this->user_id = \XF::visitor()->user_id;
        //$this->username = \XF::visitor()->username;
        //$this->submit_date = \XF::$time;
	}

	public function _postSave()
	{
        $visibilityChange = $this->isStateChanged('question_state', 'visible');
		$approvalChange = $this->isStateChanged('question_state', 'moderated');
        $deletionChange = $this->isStateChanged('question_state', 'deleted');

        if ($this->isUpdate())
		{
            if ($visibilityChange == 'enter')
			{
                if ($approvalChange)
				{
					/** @var \XF\Spam\ContentChecker $submitter */
                    $submitter = $this->app()->container('spam.contentHamSubmitter');
                    $submitter->submitHam('question', $this->faq_id);
				}
            }
            if ($approvalChange == 'leave' && $this->ApprovalQueue)
			{
				$this->ApprovalQueue->delete();
			}
        }
        else
        {
            // insert
            if ($this->question_state == 'visible')
            {
			}
        }

        // Put into Approval Queue
        if ($approvalChange == 'enter')
		{
			$approvalQueue = $this->getRelationOrDefault('ApprovalQueue', false);
			$approvalQueue->content_date = $this->submit_date;
			$approvalQueue->save();
        }
        else if ($deletionChange == 'enter' && !$this->DeletionLog)
		{
			$delLog = $this->getRelationOrDefault('DeletionLog', false);
			$delLog->setFromVisitor();
			$delLog->save();
		}

    }

	public function _preDelete()
	{

    }

    public function softDelete($reason = '', \XF\Entity\User $byUser = null)
	{
		$byUser = $byUser ?: \XF::visitor();
		if ($this->question_state == 'deleted')
		{
			return false;
		}
		$this->question_state = 'deleted';
		/** @var \XF\Entity\DeletionLog $deletionLog */
		$deletionLog = $this->getRelationOrDefault('DeletionLog');
		$deletionLog->setFromUser($byUser);
		$deletionLog->delete_reason = $reason;
		$this->save();
		return true;
	}

	public function _postDelete()
	{
        if ($this->question_state == 'moderated' && $this->ApprovalQueue)
		{
			$this->ApprovalQueue->delete();
        }
        if ($this->question_state == 'deleted' && $this->DeletionLog)
		{
			$this->DeletionLog->delete();
        }
        if ($this->question_state == 'moderated' && $this->ApprovalQueue)
		{
			$this->ApprovalQueue->delete();
        }
        if ($this->getOption('log_moderator'))
		{
			$this->app()->logger()->logModeratorAction('question', $this, 'delete_hard');
        }
        $this->db()->delete('xf_edit_history', 'content_type = ? AND content_id = ?', ['question', $this->faq_id]);
        /** @var \XF\Repository\Attachment $attachRepo */
		$attachRepo = $this->repository('XF:Attachment');
		$attachRepo->fastDeleteContentAttachments('question', $this->faq_id);
    }

    public function _postDeleteUpdates(array $updateIds)
    {
        $db = $this->db();

        /** @var \XF\Repository\Attachment $attachRepo */
		$attachRepo = $this->repository('XF:Attachment');
		$attachRepo->fastDeleteContentAttachments('question', $updateIds);

		/** @var \XF\Repository\Reaction $reactionRepo */
		$reactionRepo = $this->repository('XF:Reaction');
		$reactionRepo->recalculateReactionIsCounted('question', $updateIds);

		$db->delete('xf_approval_queue', 'content_id IN (' . $db->quote($updateIds) . ') AND content_type = ?', 'question');
		$db->delete('xf_deletion_log', 'content_id IN (' . $db->quote($updateIds) . ') AND content_type = ?', 'question');
    }
}
