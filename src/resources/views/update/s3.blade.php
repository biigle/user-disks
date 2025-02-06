<div class="col-xs-6">
    <div class="form-group @error('bucket') has-error @enderror">
        <label>Bucket name</label>
        <input type="text" name="bucket" required class="form-control" value="{{old('bucket', $disk->options['bucket'])}}" placeholder="MyBucket">
        @error('bucket')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-xs-6">
    <div class="form-group @error('region') has-error @enderror">
        <label>Region</label>
        <input type="text" name="region" class="form-control" value="{{old('region', $disk->options['region'] ?? '')}}" placeholder="us-east-1">
        @error('region')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-xs-12">
    <div class="form-group @error('endpoint') has-error @enderror">
        <label>Endpoint</label>
        <input type="text" name="endpoint" required class="form-control" value="{{old('endpoint', $disk->options['endpoint'])}}" placeholder="https://MyBucket.s3.eu-central-1.amazonaws.com">
        @error('endpoint')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
    <p class="help-block">
        This must be the full URL including the bucket name, region etc.
    </p>
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
