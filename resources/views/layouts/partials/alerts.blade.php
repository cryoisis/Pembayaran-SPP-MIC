@foreach (['success' => 'ok', 'error' => 'bad'] as $key => $tone)
    @if (session($key))
        <x-alert :tone="$tone" dismiss class="mb-4">{{ session($key) }}</x-alert>
    @endif
@endforeach

@if ($errors->any() && ! request()->routeIs('login*'))
    <x-alert tone="bad" class="mb-4">
        <strong>Terjadi kesalahan:</strong>
        <ul class="mt-1 list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </x-alert>
@endif
