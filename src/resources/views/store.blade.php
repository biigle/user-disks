@extends('app')

@section('title', 'Create a new storage disks')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-sm-8 col-sm-offset-2 col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3">
            <h2>Create a new storage disk</h2>

            <form @if ($stepTwo) action="{{url('api/v1/user-disks')}}" method="POST" @else action="{{route('create-storage-disks')}}" method="GET" @endif>
                <fieldset class="row">
                    <legend class="col-xs-12">1. Choose a type and name</legend>
                    <div class="col-xs-6">
                        <div class="form-group @error('type') has-error @enderror">
                            <label>Type</label>
                            <select name="type" required class="form-control" @disabled($chosenType)>
                                @foreach($types as $type => $description)
                                    <option value="{{$type}}" @selected($type === $chosenType)>{{$description}}</option>
                                @endforeach
                            </select>
                            @error('type')
                                <p class="help-block">{{$message}}</p>
                            @enderror
                            @if ($stepTwo)
                                <input type="hidden" name="type" value="{{$chosenType}}">
                            @endif
                        </div>
                    </div>
                    <div class="col-xs-6">
                        <div class="form-group @error('name') has-error @enderror">
                            <label>Name</label>
                            <input type="text" name="name" required class="form-control" value="{{$chosenName}}" @readonly($chosenName)>
                            @error('name')
                                <p class="help-block">{{$message}}</p>
                            @enderror
                        </div>
                    </div>
                </fieldset>
                @if ($stepTwo)
                    <fieldset class="row">
                        <legend class="col-xs-12">2. Configure the options</legend>
                        @include("user-disks::store.{$chosenType}")
                    </fieldset>
                @endif
                <div class="row">
                    @error('error')
                        <div class="col-xs-12">
                            <div class="panel panel-danger">
                                <div class="panel-body text-danger">
                                    {{$message}}
                                </div>
                            </div>
                        </div>
                    @enderror
                    </div>
                    <div class="col-xs-12">
                        @csrf
                        <div class="form-group clearfix">
                            <button type="submit" class="btn btn-success pull-right">
                                @if ($stepTwo)
                                    Create disk
                                @else
                                    Continue
                                @endif
                            </button>
                            @if ($stepTwo)
                                <a href="{{route('create-storage-disks')}}" class="btn btn-default">Back</a>
                            @else
                                <a href="{{route('storage-disks')}}" class="btn btn-default">Back</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
