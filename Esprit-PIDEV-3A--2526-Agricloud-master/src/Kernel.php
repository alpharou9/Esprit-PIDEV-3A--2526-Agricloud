<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * OneDrive can block writes inside the project cache directory on Windows.
     * Using a temp-based cache path keeps Symfony writable in dev mode.
     */
    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/agricloud-symfony/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/agricloud-symfony/log';
    }
}
