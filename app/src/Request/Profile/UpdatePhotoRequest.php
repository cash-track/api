<?php

declare(strict_types=1);

namespace App\Request\Profile;

use Laminas\Diactoros\Exception\UploadedFileErrorException;
use Psr\Http\Message\UploadedFileInterface;
use Spiral\Filters\Attribute\Input\File;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

class UpdatePhotoRequest extends Filter implements HasFilterDefinition
{
    #[File]
    public ?UploadedFileInterface $photo = null;

    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: [
            'photo' => [
                'file::uploaded',
                'image::valid',
                ['file::size', 5120],
                ['image::smaller', 5000, 5000],
                ['image::bigger', 50, 50],
            ],
        ]);
    }

    public function getPhoto(): UploadedFileInterface
    {
        if ($this->photo instanceof UploadedFileInterface) {
            return $this->photo;
        }

        throw new UploadedFileErrorException('Empty uploaded file');
    }
}
