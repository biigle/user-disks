<h4><a name="azure"></a>Azure Blob Storage</h4>

<p>
    Azure Blob Storage is Microsoft's object storage solution for the cloud. An Azure storage disk can connect to one storage container in Azure.
</p>

<p>
    An Azure Blob Storage disk has the following options:
</p>

<dl>
    <dt>Connection String</dt>
    <dd>
        <p>
            The Azure Storage connection string. You can find this in the Azure Portal under your Storage Account → Access keys.
            <br>Example: <code>DefaultEndpointsProtocol=https;AccountName=myaccount;AccountKey=...;EndpointSuffix=core.windows.net</code>
        </p>
        <p>
            For local development with Azurite, use:
            <br><code>DefaultEndpointsProtocol=http;AccountName=devstoreaccount1;AccountKey=Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==;BlobEndpoint=http://127.0.0.1:10000/devstoreaccount1;</code>
        </p>
    </dd>

    <dt>Container</dt>
    <dd>
        <p>
            The name of the container where your files are stored.
        </p>
    </dd>
</dl>
