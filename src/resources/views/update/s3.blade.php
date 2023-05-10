<div class="col-xs-12">
    <div class="form-group @error('bucket') has-error @enderror">
        <label>Bucket Name</label>
        <input type="text" name="bucket" required class="form-control" value="{{old('bucket', $disk->options['bucket'])}}" placeholder="MyBucket">
        @error('bucket')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-xs-12">
    <div class="panel panel-warning">
        <div class="panel-body text-warning">
            Your access credentials are stored in the BIIGLE database. Please configure the credentials to have only the minimum required permissions.
        </div>
    </div>
</div>
<div class="col-sm-6">
    <div class="form-group @error('key') has-error @enderror">
        <label>Access Key</label>
        <input type="text" name="key" required class="form-control" value="{{old('key', $disk->options['key'])}}">
        @error('key')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-sm-6">
    <div class="form-group @error('secret') has-error @enderror">
        <label>Secret Key</label>
        <input type="password" name="secret" class="form-control" value="{{old('secret')}}">
        @error('secret')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-sm-6">
    <div class="form-group @error('region') has-error @enderror">
        <label>Region</label>
        <input type="text" name="region" required class="form-control" value="{{old('region', $disk->options['region'])}}" placeholder="eu-central-1">
        @error('region')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-sm-6">
    <div class="form-group @error('endpoint') has-error @enderror">
        <label>Endpoint</label>
        <input type="text" name="endpoint" required class="form-control" value="{{old('endpoint', $disk->options['endpoint'])}}" placeholder="https://s3.eu-central-1.amazonaws.com">
        @error('endpoint')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-xs-6">
    <div class="radio">
        <label>
            <input type="radio" name="use_path_style_endpoint" @checked(!old('use_path_style_endpoint', $disk->options['use_path_style_endpoint'] ?? false)) value="0"> Subdomain endpoint
        </label>
        <p class="help-block">
            E.g.: <code>BUCKETNAME.s3.example.com</code>
        </p>
    </div>
</div>
<div class="col-xs-6">
    <div class="radio">
        <label>
            <input type="radio" name="use_path_style_endpoint" @checked(old('use_path_style_endpoint', $disk->options['use_path_style_endpoint'] ?? false)) value="1"> Path endpoint
        </label>
        <p class="help-block">
            E.g.: <code>s3.example.com/BUCKETNAME</code>
        </p>
    </div>
</div>
