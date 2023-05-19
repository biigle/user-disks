<div class="col-xs-12">
    <div class="form-group @error('collectionId') has-error @enderror">
        <label>Collection ID</label>
        <input type="text" name="collectionId" required class="form-control" value="{{old('collectionId')}}" placeholder="MYARUNACOLLECTIONID">
        @error('collectionId')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-xs-12">
    <div class="form-group @error('bucket') has-error @enderror">
        <label>Bucket name</label>
        <input type="text" name="bucket" required class="form-control" value="{{old('bucket')}}" placeholder="latest.my-collection.my-project">
        @error('bucket')
            <p class="help-block">{{$message}}</p>
        @enderror
        <p class="help-block">
            The bucket name consists of the collection version (or "latest"), the collection name and the project name joined with dots.
        </p>
    </div>
</div>
<div class="col-xs-12">
    <div class="form-group @error('endpoint') has-error @enderror">
        <label>Endpoint</label>
        <input type="text" name="endpoint" required class="form-control" value="{{old('endpoint')}}" placeholder="https://latest.my-collection.my-project.data.gi.aruna-storage.org">
        @error('endpoint')
            <p class="help-block">{{$message}}</p>
        @enderror
        <p class="help-block">
            The endpoint is the URL <code>https://&lt;bucket&gt;.data.gi.aruna-storage.org</code> where <code>&lt;bucket&gt;</code> is replaced with the bucket name above.
        </p>
    </div>
</div>
<div class="col-sm-4">
    <div class="form-group @error('key') has-error @enderror">
        <label>Access Key</label>
        <input type="text" name="key" required class="form-control" value="{{old('key')}}">
        @error('key')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-sm-4">
    <div class="form-group @error('secret') has-error @enderror">
        <label>Secret Key</label>
        <input type="password" name="secret" required class="form-control" value="{{old('secret')}}">
        @error('secret')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-sm-4">
    <div class="form-group @error('token') has-error @enderror">
        <label>API Secret</label>
        <input type="password" name="token" required class="form-control" value="{{old('token')}}">
        @error('token')
            <p class="help-block">{{$message}}</p>
        @enderror
    </div>
</div>
<div class="col-xs-12">
    <div class="checkbox">
        <label>
            <input type="checkbox" name="know" @checked(old('know')) value="1" required> I have configured the credentials to have only the minimum required permissions (collection access).
        </label>
        <p class="help-block">
            The access credentials are stored in the BIIGLE database and minimum permissions reduce risk in case of exposure.
        </p>
    </div>
</div>
