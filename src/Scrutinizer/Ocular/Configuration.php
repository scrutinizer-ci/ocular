<?php

namespace Scrutinizer\Ocular;

use PhpOption\Option;
use JMS\Serializer\Annotation\Type;

class Configuration
{
    /**
     *
     * @var string
     * @Type("string")
     */
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
