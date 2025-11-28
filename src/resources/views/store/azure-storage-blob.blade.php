<div class="col-xs-12">
    <div class="form-group @error('sas_url') has-error @enderror">
        <label>SAS URL (optional)</label>
        <input type="text" id="azure-sas-url" class="form-control" placeholder="http://host:port/account/container?sv=...">
        <p class="help-block">
            Paste your full SAS URL here to auto-fill the connection string and container fields below.
        </p>
        @error('sas_url')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>

<div class="col-xs-12">
    <div class="form-group @error('connection_string') has-error @enderror">
        <label>Connection String <span class="text-danger">*</span></label>
        <textarea name="connection_string" id="azure-connection-string" class="form-control" rows="3" placeholder="DefaultEndpointsProtocol=http;BlobEndpoint=...;SharedAccessSignature=...">{{old('connection_string')}}</textarea>
        <p class="help-block">
            Will be autofilled if SAS URL is given. You can find the connection string in the Azure Portal under your Storage Account → Security + networking → Access keys.
            <br>Example: <code>DefaultEndpointsProtocol=https;AccountName=...;AccountKey=...;EndpointSuffix=core.windows.net</code>
        </p>
        @error('connection_string')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>

<div class="col-xs-12">
    <div class="form-group @error('container') has-error @enderror">
        <label>Container <span class="text-danger">*</span></label>
        <input type="text" name="container" id="azure-container" class="form-control" value="{{old('container')}}" placeholder="Container Name">
        @error('container')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>

<script>
document.getElementById('azure-sas-url').addEventListener('input', function(e) {
    const urlStr = e.target.value.trim();
    if (!urlStr) return;
    
    try {
        const url = new URL(urlStr);
        const pathParts = url.pathname.split('/').filter(p => p);
        
        let accountName = '';
        let containerName = '';
        let blobEndpoint = '';
        let protocol = url.protocol.replace(':', '');
        
        // Check if it's standard Azure or Azurite format
        if (url.hostname.endsWith('.blob.core.windows.net')) {
            // Standard Azure: https://account.blob.core.windows.net/container?sas
            accountName = url.hostname.split('.')[0];
            containerName = pathParts[0] || '';
            blobEndpoint = `${url.protocol}//${url.hostname}`;
        } else {
            // Azurite or custom: http://host:port/account/container?sas
            if (pathParts.length >= 2) {
                accountName = pathParts[0];
                containerName = pathParts[1];
                blobEndpoint = `${url.protocol}//${url.host}/${accountName}`;
            } else if (pathParts.length === 1) {
                // Could be just container
                containerName = pathParts[0];
                blobEndpoint = `${url.protocol}//${url.host}`;
            }
        }
        
        // Get SAS token (everything after ?)
        const sasToken = url.search ? url.search.substring(1) : '';
        
        // Build connection string
        if (blobEndpoint && sasToken) {
            const connectionString = `DefaultEndpointsProtocol=${protocol};BlobEndpoint=${blobEndpoint};SharedAccessSignature=${sasToken}`;
            document.getElementById('azure-connection-string').value = connectionString;
        }
        
        // Set container
        if (containerName) {
            document.getElementById('azure-container').value = containerName;
        }
        
    } catch (err) {
        // Invalid URL, ignore
        console.log('Invalid URL:', err);
    }
});
</script>
