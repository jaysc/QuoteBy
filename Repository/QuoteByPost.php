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
    public function findExistingQuoteByPostToId($postIds)
    {
        $finder = $this->finder('Jaysc\QuoteBy:Post');
        $finder
            ->setDefaultOrder('post_id', 'DESC')
            ->where('post_id', '=', $postIds);

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

    /** @param \Jaysc\QuoteBy\xf\Entity\Post $post */
    public function updateQuoteByPost(Post $post, $quoteByPosts = [])
    {
        $matches = $post->getMatches();

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

            if (count($quoteByPosts) > 0) {
                /** @var \Jaysc\QuoteBy\Entity\Post $quoteByPost */
                foreach ($quoteByPosts as $quoteByPost) {
                    if ($quoteByPost->parent_post_id == $parentPostId) {
                        $exists = true;
                        break;
                    }
                }
            }

            if ($exists || in_array($key, $posted)) {
                array_push($posted, $key);
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

    /** @param \Jaysc\QuoteBy\xf\Entity\Post $post */
    public function deleteUnreferencedQuoteByPosts(Post $post, $quoteByPosts = []) {
        $matches = $post->getMatches();
        
        /** @var \Jaysc\QuoteBy\Entity\Post $quoteByPost */
        foreach ($quoteByPosts as $quoteByPost) {
            if ($quoteByPost->post_id != $post->post_id) {
                continue;
            }

            if (!in_array($quoteByPost->parent_post_id, $matches['post'])) {
                $quoteByPost->delete();
            }
        }
    }

    public function deleteQuoteByPostByPostId(Post $post) {
        $finder = $this->finder('Jaysc\QuoteBy:Post');
        $quoteByPosts = $finder
            ->setDefaultOrder('post_id', 'DESC')
            ->with('Thread', true)
            ->with('Post', true)
            ->where('post_id', $post->post_id)->fetch();

        foreach ($quoteByPosts as $quoteByPost) {
            $quoteByPost->delete();
        }
    }
}