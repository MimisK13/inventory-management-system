@props([
    // TODO: WIP
])

{{--@session('success')--}}
{{--<div class="alert alert-success alert-dismissible" role="alert">--}}
{{--    <h3 class="mb-1">Success</h3>--}}
{{--    <p>{{ $value }}</p>--}}

{{--    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>--}}
{{--</div>--}}
{{--@endsession--}}

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
        <h3 class="mb-1">Oops...</h3>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>

        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    </div>
@endif
