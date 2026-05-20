@foreach($preserve ?? [] as $name => $value)
    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
@endforeach
