@extends('app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-sm-4 col-md-3 col-md-offset-1 col-lg-2 col-lg-offset-2">
            <ul class="nav nav-pills nav-stacked">
                @mixin('storageMenu')
            </ul>
        </div>
        <div class="col-sm-8 col-md-7 col-lg-6">
            @yield('storage-content')
        </div>
    </div>
</div>
@endsection
