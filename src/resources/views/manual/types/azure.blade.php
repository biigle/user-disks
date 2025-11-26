<h4><a name="azure"></a>Azure Blob Storage</h4>

<p>
    Azure Blob Storage is Microsoft's object storage solution for the cloud. You can use it to store massive amounts of unstructured data, such as text or binary data.
</p>

<p>
    An Azure Blob Storage disk has the following options:
</p>

<dl>
    <dt>URL</dt>
    <dd>
        <p>
            The full URL to the container, including the SAS token. If you paste a valid URL here, the other fields will be automatically filled.
            <br>Example: <code>https://myaccount.blob.core.windows.net/mycontainer?sv=...</code>
        </p>
    </dd>

    <dt>Account Name</dt>
    <dd>
        <p>
            The name of your Azure Storage account.
        </p>
    </dd>

    <dt>Account Key</dt>
    <dd>
        <p>
            The access key for your storage account. This is optional if you provide a SAS token.
        </p>
    </dd>

    <dt>Container</dt>
    <dd>
        <p>
            The name of the container where your files are stored.
        </p>
    </dd>

    <dt>Endpoint</dt>
    <dd>
        <p>
            The endpoint URL of your storage account.
            <br>Example: <code>https://myaccount.blob.core.windows.net</code>
        </p>
    </dd>

    <dt>SAS Token</dt>
    <dd>
        <p>
            A Shared Access Signature (SAS) token that grants restricted access rights to Azure Storage resources. It must start with a <code>?</code>.
        </p>
    </dd>
</dl>
