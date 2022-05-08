<?php

declare(strict_types=1);

namespace Tests\Feature\Service;

use App\Config\CdnConfig;
use App\Service\PhotoStorageService;
use Aws\Result;
use Aws\S3\S3Client;
use Laminas\Diactoros\UploadedFile;
use Nyholm\Psr7\Stream;
use Spiral\Boot\EnvironmentInterface;
use Tests\TestCase;

class PhotoStorageServiceTest extends TestCase
{
    public function testStoreUploadedProfilePhoto(): void
    {
        $uploadedFileName = 'uploaded-file-name.jpg';
        $uploadedMime = 'image/jpg';
        $generatedFileName = md5((string) time());
        $extension = 'jpg';
        $bucket = $this->getContainer()->get(EnvironmentInterface::class)->get('CDN_BUCKET');

        $s3ClientResult = $this->getMockBuilder(Result::class)
                               ->disableOriginalConstructor()
                               ->onlyMethods(['get'])
                               ->getMock();

        $s3ClientResult->expects($this->once())
                       ->method('get')
                       ->with('ObjectURL')
                       ->willReturn(PhotoStorageService::PHOTO_PATH . $generatedFileName . '.' . $extension);

        $s3Client = $this->getMockBuilder(S3Client::class)
                         ->disableOriginalConstructor()
                         ->addMethods(['putObject'])
                         ->getMock();

        $stream = Stream::create('test');

        $s3Client->expects($this->once())
                 ->method('putObject')
                 ->with([
                     'Bucket'             => $bucket,
                     'ACL'                => 'public-read',
                     'Key'                => PhotoStorageService::PHOTO_PATH . $generatedFileName . '.' . $extension,
                     'Body'               => $stream,
                     'ContentType'        => $uploadedMime,
                     'ContentDisposition' => 'inline',
                 ])
                 ->willReturn($s3ClientResult);

        $uploadedFileMock = $this->getMockBuilder(UploadedFile::class)
                                 ->disableOriginalConstructor()
                                 ->onlyMethods(['getClientFilename', 'getStream', 'getClientMediaType'])
                                 ->getMock();

        $uploadedFileMock->method('getClientFilename')->willReturn($uploadedFileName);
        $uploadedFileMock->method('getStream')->willReturn($stream);
        $uploadedFileMock->method('getClientMediaType')->willReturn($uploadedMime);

        $service = $this->getMockBuilder(PhotoStorageService::class)
                        ->setConstructorArgs([$s3Client, $this->getContainer()->get(CdnConfig::class)])
                        ->onlyMethods(['generateFileName'])
                        ->getMock();

        $service->expects($this->once())
                ->method('generateFileName')
                ->with($uploadedFileName)
                ->willReturn($generatedFileName);

        $this->assertEquals(
            $generatedFileName . '.' . $extension,
            $service->storeUploadedProfilePhoto($uploadedFileMock)
        );
    }

    public function testStoreUploadedProfilePhotoReturnNull(): void
    {
        $uploadedFileMock = $this->getMockBuilder(UploadedFile::class)
                                 ->disableOriginalConstructor()
                                 ->onlyMethods(['getClientFilename', 'getStream', 'getClientMediaType'])
                                 ->getMock();

        $uploadedFileMock->method('getClientFilename')->willReturn('uploaded-file-name.jpg');
        $uploadedFileMock->method('getStream')->willReturn(Stream::create('test'));
        $uploadedFileMock->method('getClientMediaType')->willReturn('image/jpg');

        $s3ClientResult = $this->getMockBuilder(Result::class)
                               ->disableOriginalConstructor()
                               ->onlyMethods(['get'])
                               ->getMock();

        $s3ClientResult->expects($this->once())
                       ->method('get')
                       ->with('ObjectURL')
                       ->willReturn(null);

        $s3Client = $this->getMockBuilder(S3Client::class)
                         ->disableOriginalConstructor()
                         ->addMethods(['putObject'])
                         ->getMock();

        $s3Client->expects($this->once())
                 ->method('putObject')
                 ->willReturn($s3ClientResult);

        $service = new PhotoStorageService($s3Client, $this->getContainer()->get(CdnConfig::class));

        $this->assertNull($service->storeUploadedProfilePhoto($uploadedFileMock));
    }

    public function getFileExtensionDataProvider(): array
    {
        return [
            ['filename.png', 'png'],
            ['filename..png', 'png'],
            ['file.name.png', 'png'],
            ['filenamepng', 'jpg'],
            [null, 'jpg'],
        ];
    }

    /**
     * @dataProvider getFileExtensionDataProvider
     * @param string|null $fileName
     * @param string $expectedExtension
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function testGetFileExtension(?string $fileName, string $expectedExtension): void
    {
        $service = $this->getContainer()->get(PhotoStorageService::class);

        $this->assertEquals($expectedExtension, $this->callMethod($service, 'getFileExtension', [$fileName]));
    }

    public function testRemoveProfilePhoto(): void
    {
        $fileName = md5((string) time()) . '.jpg';
        $bucket = $this->getContainer()->get(EnvironmentInterface::class)->get('CDN_BUCKET');

        $s3Client = $this->getMockBuilder(S3Client::class)
                         ->disableOriginalConstructor()
                         ->addMethods(['deleteObject'])
                         ->getMock();

        $s3Client->expects($this->once())
                 ->method('deleteObject')
                 ->with([
                     'Bucket' => $bucket,
                     'Key' => PhotoStorageService::PHOTO_PATH . $fileName
                 ]);

        $service = new PhotoStorageService($s3Client, $this->getContainer()->get(CdnConfig::class));

        $service->removeProfilePhoto($fileName);
        $service->removeProfilePhoto('');
    }

    public function testGetProfilePhotoPublicUrl(): void
    {
        $fileName = md5((string) time()) . '.jpg';
        $host = $this->getContainer()->get(EnvironmentInterface::class)->get('CDN_HOST');
        $expectedUrl = $host . '/' . PhotoStorageService::PHOTO_PATH . $fileName;

        $s3Client = $this->getMockBuilder(S3Client::class)->disableOriginalConstructor()->getMock();

        $service = new PhotoStorageService($s3Client, $this->getContainer()->get(CdnConfig::class));

        $this->assertNull($service->getProfilePhotoPublicUrl(null));
        $this->assertEquals($expectedUrl, $service->getProfilePhotoPublicUrl($fileName));
    }
}
