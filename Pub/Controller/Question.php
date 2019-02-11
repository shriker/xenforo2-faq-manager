<?php

namespace Shriker\Faq\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View;
use XF\Pub\Controller\AbstractController;

class Question extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
	{
		/** @var \Shriker\Faq\XF\Entity\User $visitor */
		$visitor = \XF::visitor();

		if (!$visitor->canViewFaq($error))
		{
			throw $this->exception($this->noPermission($error));
		}
    }

    public function actionIndex(ParameterBag $params)
    {
        $viewParams = [];

        if ($params->faq_id)
		{
			return $this->rerouteController(__CLASS__, 'Question', $params);
        }

        $page = $this->filterPage();
        $perPage = $this->options()->faqPerPage;

        $repo = $this->repository('Shriker\Faq\Repository\Question');
        $finder = $repo->findQuestionsForOverview()->limitByPage($page, $perPage);
        $questions = $finder->fetch();
        $total =  $finder->total();

        $viewParams = [
            'questions' => $questions,
            'page' => $page,
            'pages' => $this->repository('XF:HelpPage')->findActiveHelpPages(),
            'pageSelected' => 'faq',
            'perPage' => $perPage,
            'total' => $total
        ];

        $this->assertValidPage($page, $perPage, $total, 'help/faq', $questions);
        $this->assertCanonicalUrl($this->buildLink('help/faq'));
        return $this->view('', '_help_page_faq', $viewParams);
    }

    public function actionQuestion(ParameterBag $params)
    {
        $viewParams = [];

        $repo = $this->repository('Shriker\Faq\Repository\Question');
        $finder = $repo->findQuestionsForOverview()->whereId($params->faq_id);
        $question = $finder->fetchOne();

        //\XF::dump($question);

        $viewParams = [
            'question' => $question,
            'pages' => $this->repository('XF:HelpPage')->findActiveHelpPages(),
            'pageSelected' => 'faq',
        ];

        $this->assertCanonicalUrl($this->buildLink('help/faq', $question));
        return $this->view('Shriker\Faq::Question\View', 'faq_question', $viewParams);
    }

    public function actionAsk(ParameterBag $params)
    {
        $visitor = \XF::visitor();

		if (!$visitor->canAskQuestion($error))
		{
			return $this->noPermission($error);
        }

        $this->assertCanonicalUrl($this->buildLink('faq/ask'));

        if ($this->isPost())
		{
            $question = $this->em()->create('Shriker\Faq:Question');
            $question->question = $this->filter('question', 'str');
            $question->question_state = 'moderated';
            $question = $this->service('Shriker\Faq:Question\Create', $question);
            $question->save();
            return $this->redirect($this->buildLink('help/faq'), \XF::phrase('faq_your_question_has_been_submitted'));
        }

        return $this->view('Shriker\Faq:Question\Ask', 'faq_ask_question');
    }

    public function actionAdd(ParameterBag $params)
    {
        $visitor = \XF::visitor();

		if (!$visitor->canAskQuestion($error))
		{
			return $this->noPermission($error);
        }

        if ($this->isPost())
		{
            $question = $this->em()->create('Shriker\Faq:Question');
            $question->question = $this->filter('question', 'str');
            $question->save();
        }
    }

    public function actionPreview(ParameterBag $params)
	{
		$this->assertPostOnly();

		$question = $this->assertQuestionExists($params->faq_id);
		if (!$question->canEdit($error))
		{
			return $this->noPermission($error);
		}

        $editor = $this->setupUpdateEdit($question);
		if (!$editor->validate($errors))
		{
			return $this->error($errors);
		}

		$attachments = [];
		$tempHash = $this->filter('attachment_hash', 'str');

        // @TODO Attachment permissions
        /** @var \XF\Repository\Attachment $attachmentRepo */
        $attachmentRepo = $this->repository('XF:Attachment');
        $attachmentData = $attachmentRepo->getEditorData('question', $question, $tempHash);
        $attachments = $attachmentData['attachments'];

		return $this->plugin('XF:BbCodePreview')->actionPreview(
			$question->answer, 'question', $question->User, $attachments
		);
	}

    protected function setupUpdateEdit(\Shriker\Faq\Entity\Question $update)
	{
        $question = $this->plugin('XF:Editor')->fromInput('question');
        $answer = $this->plugin('XF:Editor')->fromInput('answer');

		/** @var \Shriker\Faq\Service\Question\Edit $editor */
		$editor = $this->service('Shriker\Faq:Question\Edit', $update);
        $editor->setQuestion($question);
        $editor->setAnswer($answer);
        // @TODO Attachment permissions
        $editor->setAttachmentHash($this->filter('attachment_hash', 'str'));
        return $editor;
    }

    public function actionEdit(ParameterBag $params)
    {
        $question = $this->assertQuestionExists($params->faq_id);

        if ($this->isPost())
		{
            $editor = $this->setupUpdateEdit($question);
            $editor->checkForSpam();

            if (!$editor->validate($errors))
			{
				return $this->error($errors);
			}

            $editor->save();

            $this->finalizeEdit($editor);

            return $this->redirect($this->buildLink('help/faq', $question));

        }
        else {
            /** @var \XF\Repository\Attachment $attachmentRepo */
            $attachmentRepo = $this->repository('XF:Attachment');
            $attachmentData = $attachmentRepo->getEditorData('question', $question);

            $viewParams = [
				'question' => $question,
				'attachmentData' => $attachmentData
            ];

			return $this->view('Shriker\Faq:Question\Edit', 'faq_question_edit', $viewParams);
        }

    }

    /*
    public function actionDelete(ParameterBag $params)
    {
        $question = $this->assertQuestionExists($params->faq_id);

        //** @var \XF\ControllerPlugin\Delete $plugin
        // Template public:delete_confirm: Template public:delete_confirm is unknown
        $plugin = $this->plugin('XF:Delete');

        return $plugin->actionDelete(
            $question, // The entity being deleted
            $this->buildLink('help/faq/delete', $question), // link to controller action
            $this->buildLink('help/faq/edit', $question), //  relevant edit/view link for the content
            $this->buildLink('help/faq'), // where to redirect
            $question->question
        );
    }
    */

    public function actionReact(ParameterBag $params)
	{
        $update = $this->assertQuestionExists($params->faq_id);
		/** @var \XF\ControllerPlugin\Reaction $reactionPlugin */
		$reactionPlugin = $this->plugin('XF:Reaction');
		return $reactionPlugin->actionReactSimple($update, 'help/faq');
	}

	public function actionReactions(ParameterBag $params)
	{
		$update = $this->assertQuestionExists($params->faq_id);

		/** @var \XF\ControllerPlugin\Reaction $reactionPlugin */
		$reactionPlugin = $this->plugin('XF:Reaction');
		return $reactionPlugin->actionReactions(
			$update,
			'help/faq/reactions',
			null
		);
    }

    protected function assertQuestionExists($faqId, array $extraWith = [])
	{
        $visitor = \XF::visitor();

        /** @var \Shriker\Faq\Entity\Question $update */
        $question = $this->em()->find('Shriker\Faq:Question', $faqId);
		if (!$question)
		{
			throw $this->exception($this->notFound(\XF::phrase('faq_requested_question_not_found')));
        }

        return $question;
    }

    protected function finalizeEdit(\Shriker\Faq\Service\Question\Edit $editor)
	{
    }

    public function actionApprove(ParameterBag $params)
	{
		$this->assertValidCsrfToken($this->filter('t', 'str'));
        $question = $this->em()->find('Shriker\Faq:Question', $params->faq_id);
		if (!$question->canApproveUnapprove($error))
		{
			return $this->noPermission($error);
		}
		/** @var \XF\Service\ProfilePostComment\Approver $approver */
		$approver = \XF::service('Shriker\Faq:Question\Approve', $question);
		$approver->approve();

		return $this->redirect($this->buildLink('help/faq', $question));
    }

    public function actionUnapprove(ParameterBag $params)
	{
		$this->assertValidCsrfToken($this->filter('t', 'str'));
		$question = $this->em()->find('Shriker\Faq:Question', $params->faq_id);
		if (!$question->canApproveUnapprove($error))
		{
			return $this->noPermission($error);
		}
		$question->question_state = 'moderated';
		$question->save();
		return $this->redirect($this->buildLink('help/faq', $question));
	}

    public static function getActivityDetails(array $activities)
	{
		return self::getActivityDetailsForContent(
			$activities, \XF::phrase('faq_viewing_question'), 'faq_id',
			function(array $ids)
			{
				$questions = \XF::em()->findByIds(
					'Shriker/Faq:Question',
					$ids
				);

				$router = \XF::app()->router('public');
				$data = [];

				foreach ($questions->filterViewable() AS $id => $question)
				{
					$data[$id] = [
						'title' => $question->question,
						'url' => $router->buildLink('help/faq', $question)
					];
				}

				return $data;
			},
			\XF::phrase('faq_viewing_faq')
		);
	}
}
