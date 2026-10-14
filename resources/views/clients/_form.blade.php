<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="company_name">Nama perusahaan</label>
            <input type="text" name="company_name" id="company_name" class="form-control @error('company_name') is-invalid @enderror"
                   value="{{ old('company_name', $client->company_name) }}" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="pic_name">PIC</label>
            <input type="text" name="pic_name" id="pic_name" class="form-control @error('pic_name') is-invalid @enderror"
                   value="{{ old('pic_name', $client->pic_name) }}" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $client->email) }}" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="phone">Telepon</label>
            <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror"
                   value="{{ old('phone', $client->phone) }}">
        </div>
    </div>
    <div class="col-12">
        <div class="form-group">
            <label for="address">Alamat</label>
            <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address', $client->address) }}</textarea>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" {{ old('status', $client->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-12">
        <div class="form-group mb-0">
            <label for="internal_notes">Catatan internal</label>
            <textarea name="internal_notes" id="internal_notes" rows="3" class="form-control @error('internal_notes') is-invalid @enderror">{{ old('internal_notes', $client->internal_notes) }}</textarea>
        </div>
    </div>
</div>
