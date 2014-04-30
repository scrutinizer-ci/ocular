<?php

namespace Scrutinizer\Tests\Ocular;

use Scrutinizer\Ocular\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @dataProvider providerTestConfiguration
     */
    public function testConfiguration($accessToken, $instance)
    {
        $configuration = new Configuration($accessToken);


        $token = $configuration->getAccessToken();

        $this->assertInstanceOf($instance, $token);
        if (!is_null($accessToken)) {
            $this->assertEquals($accessToken, $token->get());
        }
    }

    public function providerTestConfiguration()
    {
        return array(
            array('accesss key for', '\PhpOption\Some'),
            array(null, '\PhpOption\None'),
        );
    }
}
