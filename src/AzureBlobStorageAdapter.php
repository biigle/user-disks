<?php

namespace Biigle\Modules\UserDisks;

use AzureOss\FlysystemAzureBlobStorage\AzureBlobStorageAdapter as BaseAdapter;
use AzureOss\Storage\Blob\BlobContainerClient;
use AzureOss\Storage\Blob\Models\Blob;
use AzureOss\Storage\Blob\Models\BlobPrefix;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;

class AzureBlobStorageAdapter implements FilesystemAdapter
{
    private BaseAdapter $adapter;
    private BlobContainerClient $client;
    private string $prefix;

    public function __construct(BlobContainerClient $client, string $prefix = '')
    {
        $this->client = $client;
        $this->prefix = $prefix;
        
        if ($prefix !== '' && substr($prefix, -1) !== '/') {
            $prefix .= '/';
        }
        
        $this->adapter = new BaseAdapter($client, $prefix);
    }

    public function fileExists(string $path): bool
    {
        return $this->adapter->fileExists($path);
    }

    public function directoryExists(string $path): bool
    {
        return $this->adapter->directoryExists($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->adapter->write($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->adapter->writeStream($path, $contents, $config);
    }

    public function read(string $path): string
    {
        return $this->adapter->read($path);
    }

    public function readStream(string $path)
    {
        return $this->adapter->readStream($path);
    }

    public function delete(string $path): void
    {
        $this->adapter->delete($path);
    }

    public function deleteDirectory(string $path): void
    {
        $this->adapter->deleteDirectory($path);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $this->adapter->createDirectory($path, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->adapter->setVisibility($path, $visibility);
    }

    public function visibility(string $path): FileAttributes
    {
        return $this->adapter->visibility($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->adapter->mimeType($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->adapter->lastModified($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->adapter->fileSize($path);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->adapter->move($source, $destination, $config);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->adapter->copy($source, $destination, $config);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        if ($deep) {
            return $this->adapter->listContents($path, true);
        }

        $location = $this->applyPathPrefix($path);
        
        if (strlen($location) > 0 && substr($location, -1) !== '/') {
            $location .= '/';
        }

        // Use getBlobsByHierarchy for shallow listing
        $generator = $this->client->getBlobsByHierarchy($location, '/');
        $seenDirs = [];

        foreach ($generator as $item) {
            if ($item instanceof BlobPrefix) {
                $dirPath = $this->removePathPrefix($item->name);
                $dirPath = rtrim($dirPath, '/');
                if (!isset($seenDirs[$dirPath])) {
                    $seenDirs[$dirPath] = true;
                    yield new DirectoryAttributes($dirPath);
                }
            } elseif ($item instanceof Blob) {
                $filePath = $this->removePathPrefix($item->name);
                
                if ($filePath === '' || $filePath === $path) {
                    continue;
                }

                // Azurite compatibility: Check for deep files in shallow listing
                $relativePath = substr($filePath, strlen($path));
                $relativePath = ltrim($relativePath, '/');

                if (str_contains($relativePath, '/')) {
                    // It's in a subdirectory (Server ignored delimiter)
                    $parts = explode('/', $relativePath);
                    $dirName = $parts[0];
                    $fullDirPath = $path ? $path . '/' . $dirName : $dirName;
                    
                    if (!isset($seenDirs[$fullDirPath])) {
                        $seenDirs[$fullDirPath] = true;
                        yield new DirectoryAttributes($fullDirPath);
                    }
                } else {
                    yield new FileAttributes(
                        $filePath,
                        $item->properties->contentLength,
                        null,
                        $item->properties->lastModified->getTimestamp(),
                        $item->properties->contentType
                    );
                }
            }
        }
    }

    protected function applyPathPrefix($path): string
    {
        return ltrim($this->prefix . ltrim($path, '\\/'), '\\/');
    }

    protected function removePathPrefix($path): string
    {
        return substr($path, strlen($this->prefix));
    }
}
