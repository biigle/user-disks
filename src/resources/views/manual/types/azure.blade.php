<h4><a name="azure"></a>Azure Blob Storage</h4>

<p>
    Azure Blob Storage is Microsoft's object storage solution for the cloud. An Azure storage disk can connect to one storage container in Azure.
</p>

<p>
    An Azure Blob Storage disk has the following options:
</p>

<dl>
    <dt>SAS URL (optional)</dt>
    <dd>
        <p>
            If you provide a SAS URL, BIIGLE will auto-fill the connection string and container options (see below). Alternatively, you can set these options directly.
        </p>
    </dd>
    <dt>Connection String</dt>
    <dd>
        <p>
            The Azure Storage connection string. You can find this in the Azure Portal under your Storage Account → Access keys.
        </p>
        <p>
            BIIGLE uses direct URLs to load files more efficiently in the browser. If the account name and key are provided in the connection string, BIIGLE can generate temporary URLs for this purpose. Otherwise it can use a signature from a SAS as direct URL. However, you will have to manually update the SAS in the storage disk configuration whenever it expires.
        </p>
        <p>
            Example:
<pre>DefaultEndpointsProtocol=https;AccountName=myaccount;AccountKey=...;EndpointSuffix=core.windows.net</pre>
        </p>
    </dd>

    <dt>Container</dt>
    <dd>
        <p>
            The name of the container where your files are stored.
        </p>
    </dd>
</dl>
