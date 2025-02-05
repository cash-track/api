<?php

declare(strict_types=1);

namespace App\Command;

use Spiral\Console\Command;
use Spiral\Encrypter\EncrypterFactory;

class EncryptKeyGenerate extends Command
{
    protected const string NAME = 'encrypt:gen';

    protected const string DESCRIPTION = 'Generate OpenSSL key helper';

    protected const array ARGUMENTS = [];

    protected const array OPTIONS = [];

    protected function perform(EncrypterFactory $enc): void
    {
        $this->writeln($enc->generateKey());
    }
}
