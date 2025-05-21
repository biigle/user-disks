<div class="col-xs-12">
    <div class="form-group @error('bucket') has-error @enderror">
        <label>Project name</label>
        <input type="text" name="bucket" required class="form-control" value="{{old('bucket')}}" placeholder="MyProject">
        @error('bucket')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-xs-12">
    <div class="form-group @error('endpoint') has-error @enderror">
        <label>Endpoint</label>
        <input type="text" name="endpoint" required class="form-control" value="{{old('endpoint')}}" placeholder="https://MyProject.data.gi.aruna-storage.org">
        @error('endpoint')
            <p class="help-block">{{$message}}</p>
        @enderror
        <p class="help-block">
            This must be the full URL including the project name.
        </p>
    </div>
</div>
<div class="col-sm-6">
    <div class="form-group @error('key') has-error @enderror">
        <label>Access Key</label>
        <input type="text" name="key" required class="form-control" value="{{old('key')}}">
        @error('key')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-sm-6">
    <div class="form-group @error('secret') has-error @enderror">
        <label>Secret Key</label>
        <input type="password" name="secret" required class="form-control" value="{{old('secret')}}">
        @error('secret')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-xs-12">
    <div class="checkbox">
        <label>
            <input type="checkbox" name="cors" @checked(old('cors')) value="1" required> I have configured the bucket rules for cross-origin resource sharing (CORS)
        </label>
        <p class="help-block">
            The CORS rules should allow the <code>{{url('/')}}</code> origin, <code>GET</code> (and <code>OPTIONS</code>) method and <code>x-requested-with</code> header.
        </p>
    </div>
</div>
