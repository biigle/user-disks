<div id="azure-disk-store-form">
    <div class="col-xs-12">
        <div class="form-group @error('sas_url') has-error @enderror">
            <label>SAS URL (optional)</label>
            <input v-model="url" type="text" class="form-control" placeholder="http://host:port/account/container?sv=...">
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
            <textarea v-model="connectionString" name="connection_string" class="form-control" rows="3" placeholder="DefaultEndpointsProtocol=http;BlobEndpoint=...;SharedAccessSignature=..." :readonly="parsedUrl !== null" required>{{old('connection_string')}}</textarea>
            <p class="help-block">
                Will be autofilled if SAS URL is given. You can find the connection string in the Azure Portal under your Storage Account → Security + networking → Access keys.
            </p>
            @error('connection_string')
                <p class="help-block">{{$message}}</p>
            @enderror
        </div>
    </div>

    <div class="col-xs-12">
        <div class="form-group @error('container') has-error @enderror">
            <label>Container <span class="text-danger">*</span></label>
            <input v-model="containerName" type="text" name="container" class="form-control" value="{{old('container')}}" placeholder="Container Name" :readonly="parsedUrl !== null" required>
            @error('container')
                <p class="help-block">{{$message}}</p>
            @enderror
        </div>
    </div>
</div>

@push('scripts')
<script type="module">
biigle.$mount('azure-disk-store-form', {
    data() {
        return {
            url: '',
            connectionString: '',
            containerName: '',
        };
    },
    computed: {
        parsedUrl() {
            let url = this.url.trim();

            if (!url) {
                return null;
            }

            try {
                url = new URL(url);
            } catch (e) {
                return null;
            }
            const pathParts = url.pathname.split('/').filter(p => p);

            let containerName = '';
            let blobEndpoint = '';
            let protocol = url.protocol.replace(':', '');

            // Check if it's standard Azure or Azurite format
            if (url.hostname.endsWith('.blob.core.windows.net')) {
                // Standard Azure: https://account.blob.core.windows.net/container?sas
                containerName = pathParts[0] || '';
                blobEndpoint = `${url.protocol}//${url.hostname}`;
            } else {
                // Azurite or custom: http://host:port/account/container?sas
                if (pathParts.length >= 2) {
                    let accountName = pathParts[0];
                    containerName = pathParts[1];
                    blobEndpoint = `${url.protocol}//${url.host}/${accountName}`;
                } else if (pathParts.length === 1) {
                    // Could be just container
                    containerName = pathParts[0];
                    blobEndpoint = `${url.protocol}//${url.host}`;
                }
            }

            const sasToken = url.search ? url.search.substring(1) : '';

            let connectionString = '';
            if (blobEndpoint && sasToken) {
                connectionString = `DefaultEndpointsProtocol=${protocol};BlobEndpoint=${blobEndpoint};SharedAccessSignature=${sasToken}`;
            }

            return [connectionString, containerName];
        },
    },
    watch: {
        parsedUrl(parsedUrl) {
            this.connectionString = parsedUrl?.[0] || this.connectionString;
            this.containerName = parsedUrl?.[1] || this.containerName;
        },
    },
    created() {
        this.connectionString = biigle.$require('azure.connectionString');
        this.containerName = biigle.$require('azure.containerName');
    }
});
</script>
<script type="module">
biigle.$declare('azure.connectionString', '{!!old('connection_string')!!}');
biigle.$declare('azure.containerName', '{!!old('container')!!}');
</script>
@endpush
