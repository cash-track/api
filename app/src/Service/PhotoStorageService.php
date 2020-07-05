<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\CdnConfig;
use Aws\S3\S3ClientInterface;
use Psr\Http\Message\UploadedFileInterface;
use Spiral\Prototype\Annotation\Prototyped;

/**
 * @Prototyped(property="photoStorageService")
 */
class PhotoStorageService
{
    const PHOTO_PATH = 'photos/';

    /**
     * @var \Aws\S3\S3ClientInterface
     */
    private $storage;

    /**
     * @var \App\Config\CdnConfig
     */
    private $config;

    /**
     * PhotoStorageService constructor.
     *
     * @param \Aws\S3\S3ClientInterface $storage
     * @param \App\Config\CdnConfig $config
     */
    public function __construct(S3ClientInterface $storage, CdnConfig $config)
    {
        $this->storage = $storage;
        $this->config = $config;
    }

    /**
     * @param string $fileName
     * @return string
     */
    public function getProfilePhotoPublicUrl(?string $fileName):? string
    {
        if ($fileName === null) {
            return null;
        }

        return $this->config->getHost() . '/' . self::PHOTO_PATH . $fileName;
    }

    /**
     * @param \Psr\Http\Message\UploadedFileInterface $uploadedFile
     * @return string|null
     */
    public function storeUploadedProfilePhoto(UploadedFileInterface $uploadedFile):? string
    {
        $fileName = $this->generateFileName($uploadedFile->getClientFilename()) . '.' . $this->getFileExtension($uploadedFile->getClientFilename());

        $result = $this->storage->putObject([
            'Bucket'             => $this->config->getBucket(),
            'ACL'                => 'public-read',
            'Key'                => self::PHOTO_PATH . $fileName,
            'Body'               => $uploadedFile->getStream(),
            'ContentType'        => $uploadedFile->getClientMediaType(),
            'ContentDisposition' => 'inline',
        ]);

        $url = $result->get('ObjectURL');

        if ($url === null || $url === '') {
            return null;
        }

        return $fileName;
    }

    /**
     * @param string $fileName
     * @return void
     */
    public function removeProfilePhoto(string $fileName)
    {
        if ($fileName === '') {
            return;
        }

        $this->storage->deleteObject([
            'Bucket' => $this->config->getBucket(),
            'Key' => self::PHOTO_PATH . $fileName
        ]);
    }

    /**
     * @param string $fileName
     * @return string
     */
    private function generateFileName(string $fileName = ''): string
    {
        return md5($fileName . microtime());
    }

    /**
     * @param string $fileName
     * @param string $default
     * @return string
     */
    private function getFileExtension(string $fileName, string $default = 'jpg'): string
    {
        $parts = explode('.', $fileName, 2);

        if ($parts === false) {
            return $default;
        }

        if (count($parts) !== 2) {
            return $default;
        }

        return $parts[1];
    }
}
