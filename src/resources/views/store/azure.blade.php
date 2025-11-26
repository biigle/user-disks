<div class="col-xs-12">
    <div class="form-group @error('url') has-error @enderror">
        <label>URL</label>
        <input type="text" name="url" id="azure-url" class="form-control" value="{{old('url')}}" placeholder="https://account.blob.core.windows.net/container?sas...">
        <p class="help-block">Paste the full SAS URL here to autofill the other fields.</p>
        @error('url')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>

<div class="col-xs-12">
    <div class="form-group @error('name') has-error @enderror">
        <label>Account Name</label>
        <input type="text" name="name" id="azure-name" class="form-control" value="{{old('name')}}" placeholder="Azure Storage Account Name">
        @error('name')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>

<div class="col-xs-12">
    <div class="form-group @error('key') has-error @enderror">
        <label>Account Key (optional if SAS Token is provided)</label>
        <input type="text" name="key" class="form-control" value="{{old('key')}}" placeholder="Azure Storage Account Key">
        @error('key')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>

<div class="col-xs-12">
    <div class="form-group @error('container') has-error @enderror">
        <label>Container</label>
        <input type="text" name="container" id="azure-container" class="form-control" value="{{old('container')}}" placeholder="Container Name">
        @error('container')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>

<div class="col-xs-12">
    <div class="form-group @error('endpoint') has-error @enderror">
        <label>Endpoint</label>
        <input type="text" name="endpoint" id="azure-endpoint" class="form-control" value="{{old('endpoint')}}" placeholder="https://account.blob.core.windows.net">
        @error('endpoint')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>

<div class="col-xs-12">
    <div class="form-group @error('sas_token') has-error @enderror">
        <label>SAS Token</label>
        <input type="text" name="sas_token" id="azure-sas-token" class="form-control" value="{{old('sas_token')}}" placeholder="?sv=...">
        @error('sas_token')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>

<script>
document.getElementById('azure-url').addEventListener('input', function(e) {
    const urlStr = e.target.value;
    try {
        const url = new URL(urlStr);
        const pathParts = url.pathname.split('/').filter(p => p);
        
        let accountName = '';
        let containerName = '';
        let endpoint = '';
        
        if (url.hostname.endsWith('.blob.core.windows.net')) {
            // Standard Azure
            accountName = url.hostname.split('.')[0];
            containerName = pathParts[0] || '';
            endpoint = `${url.protocol}//${url.hostname}`;
        } else {
            // Azurite / Custom
            // Assuming format: http://host:port/account/container
            if (pathParts.length >= 2) {
                accountName = pathParts[0];
                containerName = pathParts[1];
                endpoint = `${url.protocol}//${url.host}/${accountName}`;
            }
        }
        
        if (accountName) document.getElementById('azure-name').value = accountName;
        if (containerName) document.getElementById('azure-container').value = containerName;
        if (endpoint) document.getElementById('azure-endpoint').value = endpoint;
        if (url.search) document.getElementById('azure-sas-token').value = url.search;
        
    } catch (err) {
        // Invalid URL, ignore
    }
});
</script>
