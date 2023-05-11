@extends('user-disks::base')

@section('title', 'Your Storage Disks')

@push('styles')
    <link href="{{ cachebust_asset('vendor/user-disks/styles/main.css') }}" rel="stylesheet">
@endpush

@section('storage-content')
<h2>Your storage disks</h2>
<p>
    Storage disks allow you to use files from remote locations (such as cloud storage services) in BIIGLE volumes.
</p>

<div class="list-group">
    @foreach ($disks as $disk)
        <div class="list-group-item user-disk-item clearfix">
            <a href="{{route('update-storage-disks', $disk->id)}}" class="pull-right btn btn-sm btn-default" title="Edit {{$disk->name}}"><i class="fa fa-pen"></i></a>
            <h4 class="list-group-item-heading">
                <small class="label label-default" title="Storage disk type {{strtoupper($disk->type)}}">{{strtoupper($disk->type)}}</small>
                {{$disk->name}}
            </h4>
            <div class="list-group-item-text text-muted">
                Created {{$disk->created_at->diffForHumans()}}
            </div>
        </div>
    @endforeach
    @can('create', \Biigle\Modules\UserDisks\UserDisk::class)
        <a href="{{route('create-storage-disks')}}" class="list-group-item" title="Add a new storage disk">
            <i class="fa fa-plus"></i> Add a new storage disk
        </a>
    @else
        <div class="list-group-item disabled" title="Guests are not allowed to create new storage disks">
            <i class="fa fa-plus"></i> Add a new storage disk
        </div>
    @endcan
</div>
@endsection
