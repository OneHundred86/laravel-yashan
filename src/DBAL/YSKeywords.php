<?php

namespace Oh86\LaravelYashan\DBAL;

use Doctrine\DBAL\Platforms\Keywords\KeywordList;
use Oh86\LaravelYashan\YSReservedWords;

class YSKeywords extends KeywordList {
    use YSReservedWords;

    public function getName()
    {
        return 'yashan';
    }

    protected function getKeywords()
    {
        return $this->getReserveds();
    }
}