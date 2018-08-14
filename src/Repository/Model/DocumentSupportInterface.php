<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Repository\Model;

interface DocumentSupportInterface
{
    /**
     * Can be a string or an integer
     *
     * @return mixed
     */
    public function getId();
}
