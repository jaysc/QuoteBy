<?php

namespace Jaysc\QuoteBy;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
	use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;

	public function installStep1()
	{
		$this->schemaManager()->createTable('xf_jaysc_quote_by', function (Create $table) {
			$table->addColumn('parent_post_id', 'int');
			$table->addColumn('post_id', 'int');
			$table->addColumn('thread_id', 'int');
			$table->addPrimaryKey(['parent_post_id', 'post_id']);
		});
	}

	public function installStep2()
	{
        $this->app()->jobManager()
            ->enqueueUnique(
                'jaysc_quoteBy_build',
                'Jaysc\QuoteBy:QuoteByBuild'
            );
	}

	public function uninstallStep1()
	{
		$this->schemaManager()->dropTable('xf_jaysc_quote_by');
	}
}
