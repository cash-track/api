<?php

declare(strict_types=1);

namespace App\Command;

use Spiral\Console\Command;
use Spiral\Encrypter\EncrypterFactory;

class EncryptKeyGenerate extends Command
{
    protected const NAME = 'encrypt:gen';

    protected const DESCRIPTION = 'Generate OpenSSL key helper';

    protected const ARGUMENTS = [];

    protected const OPTIONS = [];

    protected function perform(EncrypterFactory $enc): void
    {
        $this->writeln($enc->generateKey());
    }
}
