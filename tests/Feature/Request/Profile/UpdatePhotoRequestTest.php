<?php

declare(strict_types=1);

namespace Tests\Feature\Request\Profile;

use App\Request\Profile\UpdatePhotoRequest;
use Laminas\Diactoros\Exception\UploadedFileErrorException;
use Psr\Http\Message\UploadedFileInterface;
use Tests\TestCase;

class UpdatePhotoRequestTest extends TestCase
{
    public function testGetPhoto(): void
    {
        $request = new UpdatePhotoRequest();

        $file = $this->getMockBuilder(UploadedFileInterface::class)->getMock();

        $request->photo = $file;

        $this->assertEquals($file, $request->getPhoto());
    }

    public function testGetPhotoThrownException(): void
    {
        $request = new UpdatePhotoRequest();

        $this->expectException(UploadedFileErrorException::class);

        $request->getPhoto();
    }
}
