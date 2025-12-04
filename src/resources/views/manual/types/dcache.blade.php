<h4><a name="dcache"></a>dCache</h4>

<p>
    dCache is a distributed storage system commonly used in scientific computing environments, particularly within the Helmholtz Association. A dCache storage disk allows you to access files stored in dCache directly from BIIGLE.
</p>

<p>
    Authentication with dCache is handled through Helmholtz AAI (Authentication and Authorization Infrastructure). When you create a dCache storage disk, you will be redirected to the Helmholtz AAI login page to authenticate and authorize BIIGLE to access your dCache storage.
</p>

<p>
    <strong>Note:</strong> The authentication tokens for dCache storage disks have a limited lifetime. BIIGLE will automatically refresh your tokens when they are about to expire. If this fails for some reason, you may have to re-authenticate by editing the storage disk and clicking the "update disk" button.
</p>

<p>
    A dCache storage disk has the following options:
</p>

<dl>
    <dt>Path Prefix (optional)</dt>
    <dd>
        <p>
            An optional path prefix to prepend to all file paths when accessing files in dCache. This is useful if all your files are located in a specific subdirectory within your dCache storage.
        </p>
        <p>
            If no path prefix is specified, file paths must be given as absolute paths within your dCache storage.
        </p>
    </dd>
</dl>

