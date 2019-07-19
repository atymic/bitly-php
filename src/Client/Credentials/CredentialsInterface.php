<?php
declare(strict_types=1);

namespace Atymic\Bitly\Client\Credentials;

interface CredentialsInterface
{
    public function getToken(): string;
}
