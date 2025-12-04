<div class="col-xs-12">
    <p class="text-muted">
        dCache is connected through your Helmholtz AAI account. Click on "update disk" below to renew the authentication process.
    </p>
</div>
<div class="col-xs-12">
    <div class="form-group @error('pathPrefix') has-error @enderror">
        <label>Path Prefix (optional)</label>
        <input type="text" name="pathPrefix" class="form-control" value="{{old('pathPrefix', $disk->options['pathPrefix'] ?? '')}}" placeholder="/path/to/directory">
        @error('pathPrefix')
            <p class="help-block">{{$message}}</p>
        @else
            <p class="help-block">Optional path prefix to use for all file paths.</p>
        @enderror
    </div>
</div>
