@extends('layouts.adminlte')

@section('title', 'Tambah Paket')
@section('page_title', 'Tambah Paket Langganan')

@section('content')
<div class="card card-primary col-lg-6">
    <form method="post" action="{{ route('billing.plans.store') }}">
        @csrf
        <div class="card-body">
            <div class="form-group">
                <label>Nama paket</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label>Interval</label>
                <select name="billing_interval" class="form-control" required>
                    @foreach ($intervals as $k => $label)
                        <option value="{{ $k }}" {{ old('billing_interval', $plan->billing_interval) === $k ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Harga (Rp)</label>
                <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required min="0">
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
            </div>
            <div class="form-check">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" checked>
                <label class="form-check-label" for="is_active">Paket aktif</label>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('billing.plans.index') }}" class="btn btn-default">Batal</a>
        </div>
    </form>
</div>
@endsection
