<?php

namespace Jyasc\QuoteBy;

use XF\Mvc\Entity\Entity;

class Listener
{
    public function addQueryPosts($templater, $type, $template, $name, &$output) {
        return $output;
    }
}
