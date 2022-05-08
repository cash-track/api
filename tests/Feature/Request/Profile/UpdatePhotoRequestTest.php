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
        $request = $this->getMockBuilder(UpdatePhotoRequest::class)
                        ->disableOriginalConstructor()
                        ->onlyMethods(['getField'])
                        ->getMock();

        $file = $this->getMockBuilder(UploadedFileInterface::class)->getMock();

        $request->method('getField')->with('photo')->willReturn($file);

        $this->assertEquals($file, $request->getPhoto());
    }

    public function testGetPhotoThrownException(): void
    {
        $request = $this->getMockBuilder(UpdatePhotoRequest::class)
                        ->disableOriginalConstructor()
                        ->onlyMethods(['getField'])
                        ->getMock();

        $request->method('getField')->with('photo')->willReturn(null);

        $this->expectException(UploadedFileErrorException::class);

        $request->getPhoto();
    }
}
