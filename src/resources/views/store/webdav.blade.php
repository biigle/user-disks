<div class="col-xs-12">
    <div class="form-group @error('baseUri') has-error @enderror">
        <label>Base URI</label>
        <input type="url" name="baseUri" required class="form-control" value="{{old('baseUri')}}" placeholder="https://example.com/webdav">
        @error('baseUri')
            <p class="help-block">{{$message}}</p>
        @else
            <p class="help-block">Some public WebDAV servers do not require a username and password.</p>
        @enderror
    </div>
</div>
<div class="col-xs-6">
    <div class="form-group @error('userName') has-error @enderror">
        <label>Username</label>
        <input type="text" name="userName" class="form-control" value="{{old('userName')}}">
        @error('userName')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-xs-6">
    <div class="form-group @error('password') has-error @enderror">
        <label>Password</label>
        <input type="password" name="password" class="form-control">
        @error('password')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
