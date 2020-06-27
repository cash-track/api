<?php

declare(strict_types=1);

namespace App\Command;

use Spiral\Console\Command;
use Spiral\Files\Exception\WriteErrorException;
use Spiral\Files\FilesInterface;
use Symfony\Component\Console\Input\InputOption;

class RsaGenerateCommand extends Command
{
    protected const NAME = 'rsa:gen';

    protected const DESCRIPTION = 'Generate public and private keys helper';

    protected const ARGUMENTS = [];

    protected const OPTIONS = [
        ['out-dir', 'd', InputOption::VALUE_OPTIONAL, 'Output directory for key files [example: app/config/]'],
        ['out-prefix', 'p', InputOption::VALUE_OPTIONAL, 'Output file name pattern for key files [example: app => app-public.key]'],
    ];

    /**
     * @var \Spiral\Files\FilesInterface
     */
    private $files;

    /**
     * RsaGenerateCommand constructor.
     *
     * @param \Spiral\Files\FilesInterface $files
     * @param string|null $name
     */
    public function __construct(FilesInterface $files, string $name = null)
    {
        parent::__construct($name);

        $this->files = $files;
    }

    /**
     * Perform command
     */
    protected function perform(): void
    {
        $config = array(
            'digest_alg' => 'sha512',
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        );

        // Create the private and public key
        $key = openssl_pkey_new($config);
        if ($key === false) {
            $this->writeln('Unable to create new OpenSSL key: ' . openssl_error_string());
            return;
        }

        $privateKey = null;
        if (!openssl_pkey_export($key, $privateKey)) {
            $this->writeln('Unable to extract private key from OpenSSL key: ' . openssl_error_string());
            return;
        }

        $publicKey = openssl_pkey_get_details($key);
        if ($publicKey === false) {
            $this->writeln('Unable to extract public key from OpenSSL key: ' . openssl_error_string());
            return;
        }

        $publicKey = $publicKey["key"];

        $publicKeyPath = $this->writeFile('public', $publicKey);

        $this->writeln("Public key file located at {$publicKeyPath}");

        $privateKeyPath = $this->writeFile('private', $privateKey);

        $this->writeln("Private key file located at {$privateKeyPath}");
    }

    /**
     * @param string $name
     * @param $data
     * @return string
     */
    protected function writeFile(string $name, $data): string
    {
        $path = $this->getKeyPath($name);

        try {
            $this->files->write($path, $data, FilesInterface::READONLY, true);
        } catch (WriteErrorException $exception) {
            $this->writeln("Error on writing {$name} key file. {$exception->getMessage()}");
        }

        return $path;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getKeyPath(string $name): string
    {
        return $this->getKeysDir() . $this->getKeyFileName($name);
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getKeyFileName(string $name): string
    {
        $fileName = "{$name}.key";

        $prefix = $this->option('out-prefix');

        if ($prefix !== null) {
            $fileName = "{$prefix}-{$fileName}";
        }

        return $fileName;
    }

    /**
     * @return string
     */
    protected function getKeysDir(): string
    {
        $dir = $this->option('out-dir');

        if ($dir === null) {
            return '';
        }

        if (! str_ends_with($dir, FilesInterface::SEPARATOR)) {
            $dir .= FilesInterface::SEPARATOR;
        }

        return $dir;
    }
}
