<?php

namespace Biigle\Modules\UserDisks;

use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter as BaseAdapter;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;

class AzureBlobStorageAdapter extends BaseAdapter
{
    /**
     * @inheritDoc
     */
    public function listContents(string $path = '', bool $deep = false): iterable
    {
        if ($deep) {
            yield from parent::listContents($path, true);
            return;
        }

        // Azure Blob Storage is flat, so we simulate directories by listing everything recursively
        // and then grouping the results.
        $contents = parent::listContents($path, true);
        $seenDirectories = [];

        foreach ($contents as $attributes) {
            // If the parent adapter already returns a directory, yield it.
            if ($attributes instanceof DirectoryAttributes) {
                 yield $attributes;
                 continue;
            }
            
            $itemPath = $attributes->path();
            
            // Normalize paths to ensure correct comparison
            $itemPath = trim($itemPath, '/');
            $cleanPath = trim($path, '/');
            
            // Ensure the item is actually within the requested path
            if ($cleanPath !== '' && !str_starts_with($itemPath, $cleanPath . '/')) {
                continue;
            }
            
            // Calculate relative path
            $relativePath = $cleanPath === '' ? $itemPath : substr($itemPath, strlen($cleanPath) + 1);
            
            if (str_contains($relativePath, '/')) {
                // It's in a subdirectory
                $parts = explode('/', $relativePath);
                $dirName = $parts[0];
                $fullDirPath = $cleanPath === '' ? $dirName : $cleanPath . '/' . $dirName;
                
                if (!isset($seenDirectories[$fullDirPath])) {
                    $seenDirectories[$fullDirPath] = true;
                    yield new DirectoryAttributes($fullDirPath);
                }
            } else {
                // It's a file in the current directory
                yield $attributes;
            }
        }
    }
}
