<div class="col-xs-12">
    <div class="form-group @error('baseUri') has-error @enderror">
        <label>Base URI</label>
        <input type="url" name="baseUri" required class="form-control" value="{{old('baseUri', $disk->options['baseUri'])}}" placeholder="https://elements.example.com/webdav">
        @error('baseUri')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-xs-12">
    <div class="form-group @error('token') has-error @enderror">
        <label>API Token</label>
        <input type="password" name="token" class="form-control" value="{{old('token')}}">
        @error('token')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
