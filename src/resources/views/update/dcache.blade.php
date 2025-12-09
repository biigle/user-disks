@php
    $expiresAt = Illuminate\Support\Carbon::parse($disk->options['refresh_token_expires_at']);
@endphp
<div class="col-xs-12">
    @if ($expiresAt->isPast())
        <p class="text-warning">
            Your dCache access token is expired and cannot be automatically refreshed. Click on "update disk" below to request a new token.
        </p>
    @else
        <p class="text-muted">
            dCache is connected through your Helmholtz AAI account.
        </p>
    @endif
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
