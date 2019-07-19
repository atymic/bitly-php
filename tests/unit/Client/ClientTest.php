<?php
declare(strict_types=1);

namespace Atymic\Bitly\Client;

use Atymic\Bitly\Client\Credentials\AccessToken;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testCreate()
    {
        $client = Client::create(new AccessToken('test'));

        $this->assertInstanceOf(Client::class, $client);
    }
}
