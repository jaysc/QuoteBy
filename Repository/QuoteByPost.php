<?php

namespace Jaysc\QuoteBy\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;
use XF\Entity\Post;

class QuoteByPost extends Repository
{
    /**
     * @return Finder
     */
    public function findExistingQuoteByPostToId(int $postId)
    {
        $finder = $this->finder('Jaysc\QuoteBy:Post');
        $finder
            ->setDefaultOrder('post_id', 'DESC')
            ->where('post_id', '<=', $postId);

        return $finder;
    }

    /**
     * @return Finder
     */
    public function findQuoteByPost(int $postId)
    {
        $finder = $this->finder('Jaysc\QuoteBy:Post');
        $finder
            ->setDefaultOrder('post_id', 'DESC')
            ->with('Thread', true)
            ->with('Post', true)
            ->where('post_id', $postId);

        return $finder;
    }

    /**
     * @return Finder
     */
    public function findQuoteByPostInThread(int $threadId, $posts)
    {
        $finder = $this->finder('Jaysc\QuoteBy:Post');
        $finder
            ->setDefaultOrder('post_id', 'DESC')
            ->with('Thread', true)
            ->with('Post', true)
            ->where('thread_id', $threadId)
            ->where('post_id', $posts);

        return $finder;
    }

    public function updateQuoteByPost(Post $post, $quoteByPosts = null)
    {
        preg_match_all('/\[QUOTE="(?P<username>.+), post: (?P<post>.+), member: (?P<member>.+)"\]/', $post->message, $matches);
    
        $numOfQuotes = count($matches[0]);

        $posted = [];

        for ($x = 0; $x < $numOfQuotes; $x++) {
            $exists = false;
            //$username = $matches['username'][$x];
            $parentPostId = $matches['post'][$x];
            //$member = $matches['member'][$x];

            $parentPost = $this->app()->em()
            ->find('XF:Post', $parentPostId);

            if (!$parentPost || $parentPost->thread_id != $post->thread_id){
                continue;
            }

            $key = [$parentPostId, $post->post_id];

            if ($quoteByPosts) {
                /** @var \Jaysc\QuoteBy\Repository\QuoteByPost $quoteByPost */
                foreach ($quoteByPosts as $quoteByPost) {
                    if ($quoteByPost->parent_post_id == $parentPostId) {
                        $exists = true;
                    }
                }
            }

            if ($exists || in_array($key, $posted)) {
                continue;
            }

            $quoteByPost = $this->app()->em()->create('Jaysc\QuoteBy:Post');
            $quoteByPost->parent_post_id = $parentPostId;
            $quoteByPost->post_id = $post->post_id;
            $quoteByPost->thread_id = $post->thread_id;

            $quoteByPost->save();

            array_push($posted, $key);
        }
    }
}