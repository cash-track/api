<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\CdnConfig;
use App\Jobs\DownloadProfilePictureJob;
use Aws\S3\S3ClientInterface;
use Nyholm\Psr7\UploadedFile;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;
use Spiral\Queue\QueueInterface;

class PhotoStorageService
{
    const DEFAULT_EXT = 'jpg';
    const DEFAULT_MIME = 'image/jpeg';
    const PHOTO_PATH = 'photos/';
    const TMP_PATH = '/tmp/';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly S3ClientInterface $storage,
        private readonly CdnConfig $config,
        private readonly QueueInterface $queue,
    ) {
    }

    public function getProfilePhotoPublicUrl(?string $fileName): ?string
    {
        if ($fileName === null) {
            return null;
        }

        return $this->config->getHost() . '/' . self::PHOTO_PATH . $fileName;
    }

    public function storeUploadedProfilePhoto(UploadedFileInterface $uploadedFile): ?string
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
            $this->logger->warning('Unable to upload profile photo => ' . print_r($result, true));

            return null;
        }

        $this->logger->info('Uploaded profile photo', [
            'filename' => $fileName,
            'type' => $uploadedFile->getClientMediaType(),
            'size' => $uploadedFile->getSize(),
        ]);

        return $fileName;
    }

    public function removeProfilePhoto(string $fileName): void
    {
        if ($fileName === '') {
            return;
        }

        $this->storage->deleteObject([
            'Bucket' => $this->config->getBucket(),
            'Key' => self::PHOTO_PATH . $fileName
        ]);
    }

    public function queueDownloadProfilePhoto(int $userId, string $url, ?string $ext = null, ?string $mime = null): void
    {
        $this->logger->info('Queuing download profile photo', [
            'userId' => $userId,
            'url' => $url,
            'ext' => $ext,
            'mime' => $mime,
        ]);

        $this->queue->push(DownloadProfilePictureJob::class, [
            'userId' => $userId,
            'url' => $url,
            'ext' => $ext,
            'mime' => $mime,
        ]);
    }

    public function storeRemoteProfilePhoto(string $url, ?string $ext = null, ?string $mime = null): ?string
    {
        $fileName = $this->generateFileName($url) . '.' . ($ext ?? $this->getFileExtension($url));

        $tmpPath = self::TMP_PATH . $fileName;

        $size = $this->downloadFile($url, $tmpPath);

        if ($size === null) {
            $this->logger->warning('Unable to download remote profile photo', [
                'path' => $tmpPath,
                'url' => $url,
                'ext' => $ext,
                'mime' => $mime,
            ]);

            return null;
        }

        $this->logger->info('Downloaded remote profile photo before uploading on storage', [
            'path' => $tmpPath,
            'url' => $url,
            'size' => $size,
        ]);

        $uploadedFileName = $this->storeUploadedProfilePhoto(
            new UploadedFile($tmpPath, $size, UPLOAD_ERR_OK, $fileName, $mime ?? self::DEFAULT_MIME)
        );

        try {
            unlink($tmpPath);
        } catch (\Throwable) {
        }

        return $uploadedFileName;
    }

    protected function downloadFile(string $url, string $downloadPath): ?int
    {
        $result = file_put_contents($downloadPath, file_get_contents($url));

        return is_int($result) ? $result : null;
    }

    /**
     * @param string|null $fileName
     * @return string
     */
    protected function generateFileName(?string $fileName = ''): string
    {
        return md5(((string) $fileName) . microtime());
    }

    /**
     * @param string|null $fileName
     * @param string $default
     * @return string
     */
    private function getFileExtension(?string $fileName, string $default = self::DEFAULT_EXT): string
    {
        if ($fileName === null) {
            return $default;
        }

        $parts = explode('.', $fileName);

        if (($count = count($parts)) <= 1 || empty($parts[$count - 1]) || strlen($parts[$count - 1]) > 4) {
            return $default;
        }

        return $parts[$count - 1];
    }
}
