<?php

namespace Shriker\Faq;

use Shriker\Faq\XF\Entity\User;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;

	public function installStep1()
	{
		$sm = $this->schemaManager();

		foreach ($this->getTables() AS $tableName => $closure)
		{
			$sm->createTable($tableName, $closure);
		}
	}

	public function installStep2()
	{
		$sm = $this->schemaManager();

		$sm->alterTable('xf_user', function(Alter $table)
		{
			$table->addColumn('faq_question_count', 'int')->setDefault(0);
			$table->addColumn('faq_answer_count', 'int')->setDefault(0);
		});
	}

	public function postInstall(array &$stateChanges)
	{
		if ($this->applyDefaultPermissions())
		{
			$this->app->jobManager()->enqueueUnique(
				'permissionRebuild',
				'XF:PermissionRebuild',
				[],
				false
			);
		}

	}

	protected function getTables()
	{
		$tables = [];

		$tables['xf_faq_question'] = function(Create $table)
		{
			$table->addColumn('faq_id', 'int', 10)->autoIncrement();
			$table->addColumn('question_category_id', 'int', 10)->setDefault(0);
			$table->addColumn('moderation', 'tinyint', 1)->setDefault(0);
			$table->addColumn('sticky', 'tinyint', 1)->setDefault(0);
			$table->addColumn('display_order', 'int', 10)->setDefault(1);
			$table->addColumn('user_id', 'int');
			$table->addColumn('username', 'varchar', 100)->setDefault('');
			$table->addColumn('question_state', 'enum')->values(['visible','moderated','deleted'])->setDefault('visible');
			$table->addColumn('question', 'varchar', 150);
			$table->addColumn('answer', 'text');

			$table->addColumn('submit_date', 'int', 10)->setDefault(0);
			$table->addColumn('answer_date', 'int', 10)->setDefault(0);
			$table->addColumn('attach_count', 'int', 10);
			$table->addColumn('view_count', 'int', 10)->setDefault(0);
			$table->addColumn('last_update', 'int');

			$table->addColumn('tags', 'mediumblob');

			$table->addColumn('attach_count', 'int')->setDefault(0);
			$table->addColumn('reaction_score', 'int')->unsigned(false)->setDefault(0);
			$table->addColumn('reactions', 'blob')->nullable();
			$table->addColumn('reaction_users', 'blob');
            $table->addColumn('ip_id', 'int')->setDefault(0);
            $table->addColumn('embed_metadata', 'blob')->nullable();

			$table->addKey('last_update');
			$table->addKey(['user_id', 'last_update']);
		};

		$tables['xf_faq_category'] = function(Create $table)
		{
			$table->addColumn('question_category_id', 'int')->autoIncrement();
			$table->addColumn('title', 'varchar', 100);
			$table->addColumn('description', 'text');
			$table->addColumn('parent_category_id', 'int')->setDefault(0);
			$table->addColumn('display_order', 'int')->setDefault(0);
			$table->addColumn('lft', 'int')->setDefault(0);
			$table->addColumn('rgt', 'int')->setDefault(0);
			$table->addColumn('depth', 'smallint')->setDefault(0);
			$table->addColumn('breadcrumb_data', 'blob');
			$table->addColumn('question_count', 'int')->setDefault(0);
			$table->addColumn('featured_count', 'smallint')->setDefault(0);
			$table->addColumn('last_update', 'int')->setDefault(0);
			$table->addColumn('last_question_title', 'varchar', 100)->setDefault('');
			$table->addColumn('last_question_id', 'int')->setDefault(0);
			$table->addColumn('field_cache', 'mediumblob');
			$table->addColumn('prefix_cache', 'mediumblob');
			$table->addColumn('require_prefix', 'tinyint')->setDefault(0);
			$table->addColumn('thread_node_id', 'int')->setDefault(0);
			$table->addColumn('thread_prefix_id', 'int')->setDefault(0);
			$table->addColumn('always_moderate_create', 'tinyint')->setDefault(0);
			$table->addColumn('always_moderate_update', 'tinyint')->setDefault(0);
			$table->addColumn('min_tags', 'smallint')->setDefault(0);
			$table->addColumn('enable_versioning', 'tinyint')->setDefault(1);
			$table->addKey(['parent_category_id', 'lft']);
			$table->addKey(['lft', 'rgt']);
		};
	}

	/**
	 * Apply default addon permissions
	 */
	protected function applyDefaultPermissions($previousVersion = null)
	{
		$applied = false;

		if (!$previousVersion)
		{
			$this->applyGlobalPermission('faq', 'view');
			$this->applyGlobalPermission('faq', 'react', 'forum', 'react');

			$applied = true;
		}
	}

	protected function getDefaultWidgetSetup()
	{

	}

	/**
	 * Uninstall
	 */
	public function uninstallStep1()
	{
		$sm = $this->schemaManager();

		foreach (array_keys($this->getTables()) AS $tableName)
		{
			$sm->dropTable($tableName);
		}
	}

	public function uninstallStep2()
	{
		$sm = $this->schemaManager();
		$sm->alterTable('xf_user', function(Alter $table) {
			$table->dropColumns('faq_question_count');
			$table->dropColumns('faq_answer_count');
		});
	}

	public function uninstallStep3()
	{
        $db = $this->db();
		$contentTypes = ['question'];
		$this->uninstallContentTypeData($contentTypes);

		$db->beginTransaction();
		$db->delete('xf_permission_cache_content', "content_type = 'question'");
		$db->delete('xf_permission_entry', "permission_group_id = 'faq'");
		$db->delete('xf_permission_entry_content', "permission_group_id = 'faq'");
		$db->commit();

	}
}
