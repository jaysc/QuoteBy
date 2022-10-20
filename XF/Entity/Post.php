<?php

namespace Jaysc\QuoteBy\XF\Entity;

use XF\Mvc\Entity\Structure;

class Post extends XFCP_Post
{
    public function quoteByPosts()
    {

        $result = [];
        if ($this && $this->QuoteBy) {
            foreach($this->QuoteBy as $quoteByPost) {
                if ($quoteByPost->Post->message_state == 'visible') {
                    array_push($result, $quoteByPost);
                }
            }
        }

        return $result;
    }

    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);
        $structure->getters['quote_by_posts'] = ['getter' => 'quoteByPosts', 'cache' => false];

        $structure->relations['QuoteBy'] = [
            'entity' => 'Jaysc\QuoteBy:Post',
            'type' => self::TO_MANY,
            'conditions' => [
                ['parent_post_id', '=', '$post_id'],
            ],
        ];

        return $structure;
    }

    public function getMatches()
    {
        preg_match_all('/\[QUOTE="(?P<username>[^\s\\\]+), post: (?P<post>\d+), member: (?P<member>\d+)"\]/', $this->message, $matches);

        return $matches;
    }

    protected function _postSave()
    {
        parent::_postSave();

        /** @var \Jaysc\QuoteBy\Repository\QuoteByPost $repo */
        $repo = $this->repository('Jaysc\QuoteBy:QuoteByPost');

        $matches = $this->getMatches();

        if ($this->isInsert()) {
            $repo->updateQuoteByPost($this);
        } elseif ($this->isUpdate()) {
            $quoteByPosts = $this->getQuoteByPosts();

            $repo->deleteUnreferencedQuoteByPosts($this, $quoteByPosts);

            $repo->updateQuoteByPost($this, $quoteByPosts);
        }

        $matches;
    }

    protected function _postDelete()
    {
        parent::_postDelete();

        /** @var \Jaysc\QuoteBy\Repository\QuoteByPost $repo */
        $repo = $this->repository('Jaysc\QuoteBy:QuoteByPost');

        $repo->deleteQuoteByPostByPostId($this);
    }

    protected function getQuoteByPosts()
    {
        /** @var \Jaysc\QuoteBy\Repository\QuoteByPost $repo */
        $repo = $this->repository('Jaysc\QuoteBy:QuoteByPost');

        $finder = $repo->findQuoteByPost($this->post_id);

        $quoteByPosts = $finder->fetch();

        return $quoteByPosts;
    }
}
