<?php
declare(strict_types=1);

namespace Atymic\Bitly\Client\Credentials;

class AccessToken implements CredentialsInterface
{
    /** @var string */
    private $token;

    /**
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }
}
