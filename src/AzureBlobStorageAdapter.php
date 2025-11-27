<?php

namespace Biigle\Modules\UserDisks;

use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter as BaseAdapter;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;

class AzureBlobStorageAdapter extends BaseAdapter
{
    /**
     * @var BlobRestProxy
     */
    protected $client;

    /**
     * @var string
     */
    protected $container;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * Constructor.
     *
     * @param BlobRestProxy $client
     * @param string $container
     * @param string $prefix
     */
    public function __construct(BlobRestProxy $client, string $container, string $prefix = '')
    {
        parent::__construct($client, $container, $prefix);
        $this->client = $client;
        $this->container = $container;
        
        if ($prefix !== '' && substr($prefix, -1) !== '/') {
            $prefix .= '/';
        }
        
        $this->prefix = $prefix;
    }

    /**
     * @inheritDoc
     */
    public function listContents(string $path = '', bool $deep = false): iterable
    {
        if ($deep) {
            yield from parent::listContents($path, true);
            return;
        }

        $location = $this->applyPathPrefix($path);
        
        if (strlen($location) > 0 && substr($location, -1) !== '/') {
            $location .= '/';
        }

        $options = new ListBlobsOptions();
        $options->setPrefix($location);
        $options->setDelimiter('/');
        // Max results per page (default is usually 5000, but good to be explicit or leave default)
        // $options->setMaxResults(1000);

        $continuationToken = null;

        $seenDirs = [];

        do {
            $options->setContinuationToken($continuationToken);
            $result = $this->client->listBlobs($this->container, $options);

            foreach ($result->getBlobPrefixes() as $prefix) {
                $dirPath = $this->removePathPrefix($prefix->getName());
                $dirPath = rtrim($dirPath, '/');
                if (!isset($seenDirs[$dirPath])) {
                    $seenDirs[$dirPath] = true;
                    yield new DirectoryAttributes($dirPath);
                }
            }

            foreach ($result->getBlobs() as $blob) {
                $filePath = $this->removePathPrefix($blob->getName());
                // Skip if it matches the directory itself (virtual directory marker)
                if ($filePath === '' || $filePath === $path) {
                    continue;
                }

                // Check if the file is in a subdirectory relative to the requested path
                $relativePath = substr($filePath, strlen($path));
                $relativePath = ltrim($relativePath, '/');

                if (str_contains($relativePath, '/')) {
                    // It's in a subdirectory (Server ignored delimiter, e.g. Azurite)
                    $parts = explode('/', $relativePath);
                    $dirName = $parts[0];
                    $fullDirPath = $path ? $path . '/' . $dirName : $dirName;
                    
                    if (!isset($seenDirs[$fullDirPath])) {
                        $seenDirs[$fullDirPath] = true;
                        yield new DirectoryAttributes($fullDirPath);
                    }
                } else {
                    // It's a direct child file
                    yield new FileAttributes(
                        $filePath,
                        $blob->getProperties()->getContentLength(),
                        null, // visibility
                        $blob->getProperties()->getLastModified()->getTimestamp(),
                        $blob->getProperties()->getContentType()
                    );
                }
            }

            $continuationToken = $result->getContinuationToken();
        } while ($continuationToken);
    }

    /**
     * Apply the path prefix.
     *
     * @param string $path
     *
     * @return string
     */
    protected function applyPathPrefix($path): string
    {
        return ltrim($this->prefix . ltrim($path, '\\/'), '\\/');
    }

    /**
     * Remove the path prefix.
     *
     * @param string $path
     *
     * @return string
     */
    protected function removePathPrefix($path): string
    {
        return substr($path, strlen($this->prefix));
    }
}
