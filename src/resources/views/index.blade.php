@extends('user-disks::base')

@section('title', 'Your Storage Disks')

@push('styles')
    <link href="{{ cachebust_asset('vendor/user-disks/styles/main.css') }}" rel="stylesheet">
@endpush

@section('storage-content')
<h2>Your storage disks</h2>
<p>
    Use files from external cloud storage services for new volumes.
</p>

<div class="list-group">
    @foreach ($disks as $disk)
        <div class="list-group-item user-disk-item clearfix">
            <span class="pull-right">
                <a href="{{route('update-storage-disks', $disk->id)}}" class="btn btn-sm btn-default user-disk-edit" title="Edit {{$disk->name}}"><i class="fa fa-pen"></i></a>
                @if ($disk->isAboutToExpire())
                    <form class="inline-block-form" action="{{url("api/v1/user-disks/{$disk->id}/extend")}}" method="POST">
                        @csrf
                        <button class="btn btn-default btn-sm" title="Extend this storage disk"><i class="fa fa-redo"></i></button>
                    </form>
                @endif
            </span>
            <h4 class="list-group-item-heading">
                <small class="label label-default" title="Storage disk type {{strtoupper($disk->type)}}">{{strtoupper($disk->type)}}</small>
                {{$disk->name}}
            </h4>
            @if ($disk->expires_at < $now)
                <div class="list-group-item-text text-danger">
                    Expired <span title="{{$disk->expires_at}}">{{$disk->expires_at->diffForHumans()}}</span>!
                </div>
            @elseif ($disk->isAboutToExpire())
                <div class="list-group-item-text text-warning">
                    Expires <span title="{{$disk->expires_at}}">{{$disk->expires_at->diffForHumans()}}</span>
                </div>
            @else
                <div class="list-group-item-text text-muted">
                    Created <span title="{{$disk->created_at}}">{{$disk->created_at->diffForHumans()}}</span>
                </div>
            @endif
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
