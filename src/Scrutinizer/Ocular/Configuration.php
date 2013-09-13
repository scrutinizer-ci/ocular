<?php

namespace Scrutinizer\Ocular;

use PhpOption\Option;

class Configuration
{
    private $accessToken;

    public function __construct($accessToken = null)
    {
        $this->accessToken = $accessToken;
    }

    public function getAccessToken()
    {
        return Option::fromValue($this->accessToken);
    }
}