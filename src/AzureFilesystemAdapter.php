<?php

namespace Biigle\Modules\UserDisks;

use Illuminate\Filesystem\FilesystemAdapter;

class AzureFilesystemAdapter extends FilesystemAdapter
{
    /**
     * Get the URL for the file at the given path.
     *
     * @param  string  $path
     * @return string
     */
    public function url($path)
    {
        if (isset($this->config['url'])) {
            $url = $this->concatPathToUrl($this->config['url'], $path);
        } else {
            $url = $this->concatPathToUrl($this->config['endpoint'] ?? '', $this->config['container'].'/'.$path);
        }

        if (!empty($this->config['sas_token'])) {
            $sas = $this->config['sas_token'];
            // Ensure SAS token starts with ? if not present and url doesn't have query
            if (!str_contains($sas, '?') && !str_contains($url, '?')) {
                 $sas = '?'.$sas;
            } elseif (str_contains($url, '?') && str_starts_with($sas, '?')) {
                 $sas = '&'.substr($sas, 1);
            }
            
            $url .= $sas;
        }

        return $url;
    }
}
