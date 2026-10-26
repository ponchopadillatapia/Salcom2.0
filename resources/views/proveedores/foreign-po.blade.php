@extends('layouts.proveedor-extranjero')
@section('title', 'Purchase Orders')

@section('content')
<div style="max-width:1000px;margin:0 auto;">
    <h1 style="font-size:22px;font-weight:700;color:var(--gray-text);margin-bottom:4px;">Purchase Orders</h1>
    <p style="font-size:14px;color:var(--gray-muted);margin-bottom:22px;">Purchase orders issued to you by Industrias Salcom.</p>

    <div style="background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:20px;box-shadow:var(--shadow-sm);">
        <div style="text-align:center;padding:40px;color:var(--gray-muted);font-size:14px;">
            No purchase orders yet.
        </div>
    </div>
</div>
@endsection
