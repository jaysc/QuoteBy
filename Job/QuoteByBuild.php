<?php

namespace Jaysc\QuoteBy\Job;

use XF\Job\AbstractJob;

class QuoteByBuild extends AbstractJob
{
	protected $defaultData = [
		'steps' => 0,
		'start' => 0,
		'batch' => 100
	];

	public function run($maxRunTime)
	{

		$startTime = microtime(true);

		$db = $this->app->db();

		$postIds = $db->fetchAllColumn($db->limit(
			"
				SELECT post_id
				FROM xf_post
				WHERE post_id > ?
					AND message_state = 'visible'
					AND message LIKE '%QUOTE%'
				ORDER BY post_id
			",
			$this->data['batch']
		), $this->data['start']);
		if (!$postIds)
		{
			$db->query(
			"
				DELETE FROM xf_jaysc_quote_by
				WHERE (
					SELECT xf_post.post_id
					FROM xf_post
					WHERE xf_post.post_id = xf_jaysc_quote_by.post_id
				) IS NULL
			",)->execute();

			$db->query(
				"
					DELETE FROM xf_jaysc_quote_by
					WHERE (
						SELECT xf_post.post_id
						FROM xf_post
						WHERE xf_post.post_id = xf_jaysc_quote_by.parent_post_id
					) IS NULL
				",)->execute();

			return $this->complete();
		}

		/** @var \XF\Finder\Post $userFinder */
		$postFinder = $this->app->finder('XF:Post');
		$postFinder->where('post_id', $postIds)->order('post_id');

		$posts = $postFinder->fetch();

        /** @var \Jaysc\QuoteBy\Repository\QuoteByPost $quoteByRepo */
        $quoteByRepo = $this->app->repository('Jaysc\QuoteBy:QuoteByPost');
		$quoteByFinder = $quoteByRepo->findExistingQuoteByPostToId($postIds);

		$quoteByPosts = $quoteByFinder->fetch();

		$done = 0;

		foreach ($posts as $post) {
			$this->data['start'] = $post->post_id;

			$quoteByRepo->updateQuoteByPost($post, $quoteByPosts);
			$quoteByRepo->deleteUnreferencedQuoteByPosts($post, $quoteByPosts);

			$done++;

			if (microtime(true) - $startTime >= $maxRunTime)
			{
				break;
			}
		}
		
		$this->data['batch'] = $this->calculateOptimalBatch($this->data['batch'], $done, $startTime, $maxRunTime, 1000);

		return $this->resume();
	}

	public function getStatusMessage()
	{
		$actionPhrase = \XF::phrase('rebuilding');
		return $actionPhrase;
	}

	public function canCancel()
	{
		return true;
	}

	public function canTriggerByChoice()
	{
		return true;
	}
}
