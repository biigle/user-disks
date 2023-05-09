@extends('app')

@section('title', "Edit storage disk {$disk->name}")

@section('content')
<div class="container">
    <div class="row">
        <div class="col-sm-8 col-sm-offset-2 col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3">
            <h2>Edit storage disk {{$disk->name}}</h2>

            <form id="delete-form" action="{{url("api/v1/user-disks/{$disk->id}")}}" method="POST" onsubmit="return prompt('Please confirm the deletion of the storage disk by typing the name \'{{$disk->name}}\'.')?.toLowerCase() === '{{strtolower($disk->name)}}'">
                @csrf
                @method('DELETE')
            </form>

            <form action="{{url("api/v1/user-disks/{$disk->id}")}}" method="POST">
                <div class="row">
                    <div class="col-xs-4">
                        <div class="form-group">
                            <label>Type</label>
                            <input type="text" name="name" disabled class="form-control" value="{{$disk->type}}">
                        </div>
                    </div>
                    <div class="col-xs-8">
                        <div class="form-group @error('name') has-error @enderror">
                            <label>Name</label>
                            <input type="text" name="name" required class="form-control" value="{{old('name', $disk->name)}}">
                            @error('name')
                                <p class="help-block">{{$message}}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    @include("user-disks::update.{$disk->type}")
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        @csrf
                        @method('PUT')
                        <div class="form-group clearfix">
                            <span class="pull-right">
                                <button type="submit" form="delete-form" class="btn btn-danger" title="Delete {{$disk->name}}">
                                    Delete disk
                                </button>
                                <button type="submit" class="btn btn-success">
                                    Update disk
                                </button>
                            </span>
                            <a href="{{route('settings-storage-disks')}}" class="btn btn-default">Back</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
