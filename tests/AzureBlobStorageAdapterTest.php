<?php

namespace Biigle\Tests\Modules\UserDisks;

use Biigle\Modules\UserDisks\AzureBlobStorageAdapter;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\Blob;
use MicrosoftAzure\Storage\Blob\Models\BlobPrefix;
use MicrosoftAzure\Storage\Blob\Models\BlobProperties;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsResult;
use Mockery;
use TestCase;

class AzureBlobStorageAdapterTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testListContentsShallowWithDelimiter()
    {
        $client = Mockery::mock(BlobRestProxy::class);
        $adapter = new AzureBlobStorageAdapter($client, 'container');

        $result = Mockery::mock(ListBlobsResult::class);
        $result->shouldReceive('getBlobPrefixes')->andReturn([
            $this->createBlobPrefix('folder1/'),
        ]);
        $result->shouldReceive('getBlobs')->andReturn([
            $this->createBlob('file1.txt'),
        ]);
        $result->shouldReceive('getContinuationToken')->andReturnNull();

        $client->shouldReceive('listBlobs')->once()->andReturn($result);

        $contents = iterator_to_array($adapter->listContents('', false));

        $this->assertCount(2, $contents);
        $this->assertInstanceOf(DirectoryAttributes::class, $contents[0]);
        $this->assertEquals('folder1', $contents[0]->path());
        $this->assertInstanceOf(FileAttributes::class, $contents[1]);
        $this->assertEquals('file1.txt', $contents[1]->path());
    }

    public function testListContentsShallowWithoutDelimiter()
    {
        // Simulate Azurite behavior where delimiter is ignored and deep files are returned
        $client = Mockery::mock(BlobRestProxy::class);
        $adapter = new AzureBlobStorageAdapter($client, 'container');

        $result = Mockery::mock(ListBlobsResult::class);
        $result->shouldReceive('getBlobPrefixes')->andReturn([]);
        $result->shouldReceive('getBlobs')->andReturn([
            $this->createBlob('file1.txt'),
            $this->createBlob('folder1/file2.txt'), // Deep file
            $this->createBlob('folder1/subfolder/file3.txt'), // Deeper file
        ]);
        $result->shouldReceive('getContinuationToken')->andReturnNull();

        $client->shouldReceive('listBlobs')->once()->andReturn($result);

        $contents = iterator_to_array($adapter->listContents('', false));

        // Should return file1.txt and folder1 (derived from folder1/file2.txt)
        $this->assertCount(2, $contents);
        
        // Order depends on implementation, but let's check existence
        $paths = array_map(fn($item) => $item->path(), $contents);
        $this->assertContains('file1.txt', $paths);
        $this->assertContains('folder1', $paths);
        
        $types = array_map(fn($item) => get_class($item), $contents);
        $this->assertContains(FileAttributes::class, $types);
        $this->assertContains(DirectoryAttributes::class, $types);
    }

    protected function createBlobPrefix($name)
    {
        $prefix = Mockery::mock(BlobPrefix::class);
        $prefix->shouldReceive('getName')->andReturn($name);
        return $prefix;
    }

    protected function createBlob($name)
    {
        $blob = Mockery::mock(Blob::class);
        $blob->shouldReceive('getName')->andReturn($name);
        
        $properties = Mockery::mock(BlobProperties::class);
        $properties->shouldReceive('getContentLength')->andReturn(100);
        $properties->shouldReceive('getLastModified')->andReturn(new \DateTime());
        $properties->shouldReceive('getContentType')->andReturn('text/plain');
        
        $blob->shouldReceive('getProperties')->andReturn($properties);
        
        return $blob;
    }
}
