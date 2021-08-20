<?php

declare(strict_types = 1);

namespace App\Request\Profile;

use Laminas\Diactoros\Exception\UploadedFileErrorException;
use Psr\Http\Message\UploadedFileInterface;
use Spiral\Filters\Filter;

class UpdatePhotoRequest extends Filter
{
    protected const SCHEMA = [
        'photo'      => 'file:photo',
    ];

    protected const VALIDATES = [
        'photo' => [
            'file::uploaded',
            'image::valid',
            ['file::size', 5120],
            ['image::smaller', 5000, 5000],
            ['image::bigger', 50, 50],
        ],
    ];

    /**
     * @return \Psr\Http\Message\UploadedFileInterface|null
     * @throws \Laminas\Diactoros\Exception\UploadedFileErrorException
     */
    public function getPhoto(): UploadedFileInterface
    {
        $file = $this->getField('photo');

        if ($file instanceof UploadedFileInterface) {
            return $file;
        }

        throw new UploadedFileErrorException('Empty uploaded file');
    }
}
