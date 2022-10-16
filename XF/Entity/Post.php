<?php

namespace Jaysc\QuoteBy\XF\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Post extends XFCP_Post
{
    public function quoteByCount()
    {
        $count = 0;

        if ($this && $this->QuoteBy) {
            foreach($this->QuoteBy as $quoteByPost) {
                if ($quoteByPost->Post->message_state == 'visible') {
                    $count++;
                }
            }
        }

        return $count;
    }

    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);
        $structure->getters['quote_by_count'] = ['getter' => 'quoteByCount', 'cache' => false];

        $structure->relations['QuoteBy'] = [
            'entity' => 'Jaysc\QuoteBy:Post',
            'type' => self::TO_MANY,
            'conditions' => [
                ['parent_post_id', '=', '$post_id'],
            ],
        ];

        return $structure;
    }

    protected function _postSave()
    {
        parent::_postSave();

        preg_match_all('/\[QUOTE="(?P<username>[^\s\\\]+), post: (?P<post>\d+), member: (?P<member>\d+)"\]/', $this->message, $matches);

        if ($this->isInsert()) {
            $this->saveQuoteByPost($matches);
        } elseif ($this->isUpdate()) {
            $quoteByPosts = $this->getQuoteByPosts();

            $this->deleteQuoteByPosts($matches, $quoteByPosts);

            $this->saveQuoteByPost($matches, $quoteByPosts);
        } elseif ($this->isDeleted()) {
            $quoteByPosts = $this->getQuoteByPosts();

            $this->deleteQuoteByPosts($matches, $quoteByPosts);
        }

        $matches;
    }

    protected function getQuoteByPosts()
    {
        /** @var \Jaysc\QuoteBy\Repository\QuoteByPost $repo */
        $repo = $this->repository('Jaysc\QuoteBy:QuoteByPost');

        $finder = $repo->findQuoteByPost($this->post_id);

        $quoteByPosts = $finder->fetch();

        return $quoteByPosts;
    }

    protected function deleteQuoteByPosts($matches, $quoteByPosts)
    {
        /** @var \Jaysc\QuoteBy\Repository\QuoteByPost $quoteByPost */
        foreach ($quoteByPosts as $quoteByPost) {
            if (!in_array($quoteByPost->parent_post_id, $matches['post'])) {
                $dbQuoteByPost = $this->em()->find('Jaysc\QuoteBy:Post', [$quoteByPost->parent_post_id, $quoteByPost->post_id]);

                $dbQuoteByPost->delete();
            }
        }
    }

    protected function saveQuoteByPost($matches, $quoteByPosts = null)
    {
        $numOfQuotes = count($matches[0]);

        $posted = [];

        for ($x = 0; $x < $numOfQuotes; $x++) {
            $exists = false;
            //$username = $matches['username'][$x];
            $parentPostId = $matches['post'][$x];
            //$member = $matches['member'][$x];

            $parentPost = $this->em()
                ->find('XF:Post', $parentPostId);

            if (!$parentPost || $parentPost->thread_id != $this->thread_id){
                continue;
            }

            $key = [$parentPostId, $this->post_id];

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

            $quoteByPost = $this->em()->create('Jaysc\QuoteBy:Post');
            $quoteByPost->parent_post_id = $parentPostId;
            $quoteByPost->post_id = $this->post_id;
            $quoteByPost->thread_id = $this->thread_id;

            $quoteByPost->save();

            array_push($posted, $key);
        }
    }
}
