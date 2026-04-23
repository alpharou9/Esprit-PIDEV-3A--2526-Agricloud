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
     * Each local copy needs its own temp folder so dev caches do not collide.
     */
    public function getCacheDir(): string
    {
        return $this->getProjectTempDir() . '/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getProjectTempDir() . '/log';
    }

    private function getProjectTempDir(): string
    {
        $projectName = preg_replace('/[^A-Za-z0-9_-]+/', '-', basename($this->getProjectDir())) ?: 'agricloud';
        $projectHash = substr(md5($this->getProjectDir()), 0, 8);

        return sys_get_temp_dir() . '/' . $projectName . '-' . $projectHash;
    }
}
