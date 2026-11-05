<div class="col-md-2">
    <label class="form-label small" for="from">From</label>
    <input type="text" data-datepicker autocomplete="off" class="form-control date-input @error('from') is-invalid @enderror" id="from" name="from" value="{{ request('from') }}" placeholder="From date">
</div>
<div class="col-md-2">
    <label class="form-label small" for="to">To</label>
    <input type="text" data-datepicker autocomplete="off" class="form-control date-input @error('to') is-invalid @enderror" id="to" name="to" value="{{ request('to') }}" placeholder="To date">
    @error('to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
