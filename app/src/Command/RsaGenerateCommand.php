<?php

declare(strict_types=1);

namespace App\Command;

use Spiral\Console\Command;
use Spiral\Files\Exception\WriteErrorException;
use Spiral\Files\FilesInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class RsaGenerateCommand extends Command
{
    const string PUBLIC_KEY_PLACEHOLDER = '{rsa-public-key}';
    const string PRIVATE_KEY_PLACEHOLDER = '{rsa-private-key}';

    protected const string NAME = 'rsa:gen';

    protected const string DESCRIPTION = 'Generate public and private keys helper';

    protected const array ARGUMENTS = [];

    protected const array OPTIONS = [
        ['out-dir', 'd', InputOption::VALUE_OPTIONAL, 'Output directory for key files [example: app/config/]'],
        ['out-prefix', 'p', InputOption::VALUE_OPTIONAL, 'Output file name pattern for key files [example: app => app-public.key]'],
        ['mount', 'm', InputOption::VALUE_OPTIONAL, 'Mount RSA keys into given file'],
    ];

    /**
     * RsaGenerateCommand constructor.
     *
     * @param \Spiral\Files\FilesInterface $files
     * @param string|null $name
     */
    public function __construct(private readonly FilesInterface $files, ?string $name = null)
    {
        parent::__construct($name);
    }

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
            $this->writeln('Unable to create new OpenSSL key: ' . (string) openssl_error_string());
            return;
        }

        $privateKey = '';
        if (!openssl_pkey_export($key, $privateKey)) {
            $this->writeln('Unable to extract private key from OpenSSL key: ' . (string) openssl_error_string());
            return;
        }

        $publicKey = openssl_pkey_get_details($key);
        if ($publicKey === false) {
            $this->writeln('Unable to extract public key from OpenSSL key: ' . (string) openssl_error_string());
            return;
        }

        $publicKey = $publicKey["key"];

        $file = $this->option('mount');
        if ($file !== null && is_string($file) && $file !== '') {
            $this->mount($file, self::PUBLIC_KEY_PLACEHOLDER, $publicKey);
            $this->mount($file, self::PRIVATE_KEY_PLACEHOLDER, $privateKey);

            $this->writeln("Generated keys has been saved in {$file}");
            return;
        }

        $publicKeyPath = $this->writeFile('public', $publicKey);

        $this->writeln("Public key file located at {$publicKeyPath}");

        $privateKeyPath = $this->writeFile('private', $privateKey);

        $this->writeln("Private key file located at {$privateKeyPath}");
    }

    /**
     * @param non-empty-string $file
     * @param string $placeholder
     * @param string $key
     * @return void
     */
    protected function mount(string $file, string $placeholder, string $key): void
    {
        if (!$this->files->exists($file)) {
            $this->writeln("Unable to locate mount file {$file}");
            return;
        }

        $content = $this->files->read($file);

        $content = str_replace($placeholder, $this->convertRSAKeyToSingleLine($key), $content);

        $this->files->write($file, $content);
    }

    protected function convertRSAKeyToSingleLine(string $key): string
    {
        return base64_encode($key);
    }

    protected function writeFile(string $name, string $data): string
    {
        $path = $this->getKeyPath($name);

        try {
            $this->files->write($path, $data, FilesInterface::READONLY, true);
        } catch (WriteErrorException $exception) {
            $this->writeln("Error on writing {$name} key file. {$exception->getMessage()}");
        }

        return $path;
    }

    protected function getKeyPath(string $name): string
    {
        return $this->getKeysDir() . $this->getKeyFileName($name);
    }

    protected function getKeyFileName(string $name): string
    {
        $fileName = "{$name}.key";

        $prefix = $this->option('out-prefix');

        if ($prefix !== null) {
            $fileName = "{$prefix}-{$fileName}";
        }

        return $fileName;
    }

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
