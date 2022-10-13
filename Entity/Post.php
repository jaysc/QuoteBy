<?php

namespace Jaysc\QuoteBy\Entity;

use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int $parent_post_id
 * @property int $post_id
 * @property int $thread_id
 *
 * RELATIONS
 * @property \XF\Entity\Post $ParentPost
 * @property \XF\Entity\Post $Post
 * @property \XF\Entity\Thread $Thread
 */
class Post extends \XF\Mvc\Entity\Entity
{
    public function isChildPostVisible()
    {
        $isVisible = false;

        if ($this->Node) {
            $isVisible = $this->Node->Post->message_state == 'visible';
        }

        return $isVisible;
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_jaysc_quote_by';
        $structure->shortName = 'Jaysc\QuoteBy:Post';
        $structure->primaryKey = ['parent_post_id', 'post_id'];
        $structure->columns = [
            'parent_post_id' => ['type' => self::UINT, 'required' => true],
            'post_id' => ['type' => self::UINT, 'required' => true],
            'thread_id' => ['type' => self::UINT, 'required' => true],
        ];
        $structure->getters = [];
        $structure->relations = [
            'ParentPost' => [
                'entity' => 'XF:Post',
                'type' => self::TO_ONE,
                'conditions' => 'parent_post_id',
                'primary' => true
            ],
            'Post' => [
                'entity' => 'XF:Post',
                'type' => self::TO_ONE,
                'conditions' => ['post_id'],
                'primary' => true
            ],
            'Thread' => [
                'entity' => 'XF:Thread',
                'type' => self::TO_ONE,
                'conditions' => 'thread_id',
                'primary' => true
            ],
        ];

        return $structure;
    }
}
