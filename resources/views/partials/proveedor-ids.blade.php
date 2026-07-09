@php
    /** @var \App\Models\ProveedorUser|null $proveedor */
    $proveedor = $proveedor ?? null;
@endphp
@if($proveedor)
    <div style="font-size:10px;color:var(--gray-muted);">#{{ $proveedor->id }}</div>
    @if($proveedor->id_proveedor)
        <div style="font-weight:700;color:var(--purple);font-size:12px;">{{ $proveedor->id_proveedor }}</div>
    @else
        <div style="font-size:11px;color:var(--gray-muted);">Sin ID Proveedor</div>
    @endif
@else
    —
@endif
