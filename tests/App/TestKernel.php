<?php

declare(strict_types=1);

namespace Tests\App;

use App\App;
use Spiral\Testing\TestableKernelInterface;
use Spiral\Testing\Traits\TestableKernel;

class TestKernel extends App implements TestableKernelInterface
{
    use TestableKernel;
}
