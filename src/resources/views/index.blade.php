@extends('settings.base')

@section('title', 'Your Storage Disks')

@section('settings-content')
<h2>Storage Disks</h2>
<p>
    Storage disks allow you to use files from remote locations (such as cloud storage services) in BIIGLE volumes.
</p>

<div class="list-group">
    @foreach ($disks as $disk)
        <div class="list-group-item">
            <h4 class="list-group-item-heading clearfix">
                <small class="label label-default" title="Storage disk type {{strtoupper($disk->type)}}">{{strtoupper($disk->type)}}</small>
                {{$disk->name}}
                <a hef="#" class="pull-right btn btn-sm btn-default" title="Edit {{$disk->name}}"><i class="fa fa-pen"></i></a>
            </h4>
            <div class="list-group-item-text text-muted">
                Created {{$disk->created_at->diffForHumans()}}
            </div>
        </div>
    @endforeach
    <a href="#" class="list-group-item" title="Add a new storage disk">
        <i class="fa fa-plus"></i> Add a new storage disk
    </a>
</div>
@endsection
