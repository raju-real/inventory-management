{{-- Shared entry screen for purchases and sales. Needs: $mode, $action, $dateField, $partyField, $partyLabel, $parties, $partyDefault, $oldItems --}}
<x-form-errors />
<form id="transaction-form" method="POST" action="{{ $action }}" class="needs-validation" novalidate autocomplete="off"
      data-mode="{{ $mode }}" data-search-url="{{ route('products.search') }}" data-lookup-url="{{ route('products.lookup') }}"
      data-old-items="{{ json_encode($oldItems) }}"
      @if ($mode === 'purchase' && auth()->user()->canAccess('products', 'create')) data-create-url="{{ route('products.create') }}" @endif>
    @csrf
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card mb-3">
                <div class="card-body">
                    <label class="form-label" for="product-search">Add product</label>
                    <div class="picker">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                            <input class="form-control" id="product-search" placeholder="Scan a barcode, or type product name / code" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-text">Scanning works wherever the cursor is. The product is added automatically and scanning it again adds one more.</div>
                    <div id="scan-not-found" class="alert alert-warning py-2 mt-2 mb-0 d-none">
                        No product has barcode <strong data-code></strong>. <a target="_blank" rel="noopener" href="#">Create this product</a>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="table-responsive">
                    <table class="table line-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                @if ($mode === 'sale')<th>Available</th>@endif
                                <th style="width:120px">Quantity</th>
                                <th style="width:130px">{{ $mode === 'sale' ? 'Unit price' : 'Purchase price' }}</th>
                                <th class="text-end" style="width:120px">Total</th>
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="line-items"></tbody>
                        <tbody><tr id="line-empty"><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-upc-scan d-block fs-2 mb-1"></i>Scan a barcode or search for a product to begin.</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-3"><div class="card-body">
                <x-input :name="$dateField" type="date" :label="$mode === 'sale' ? 'Sales date' : 'Purchase date'" placeholder="Select date" :value="now()->toDateString()" required />
                <x-select :name="$partyField" :label="$partyLabel" class="js-select2" :options="$parties->mapWithKeys(fn ($party) => [$party->unique_id => $party->name . ($party->mobile ? ' (' . $party->mobile . ')' : '')])" :value="$partyDefault ?? null" required wrapper="mb-0">
                    @access($mode === 'sale' ? 'customers' : 'suppliers', 'create')
                        <x-slot name="labelAction">
                            <button type="button" class="btn btn-sm btn-outline-primary quick-add-btn party-add" data-bs-toggle="modal" data-bs-target="#partyModal" title="Add new {{ strtolower($partyLabel) }}" aria-label="Add new {{ strtolower($partyLabel) }}"><i class="bi bi-plus-lg"></i></button>
                        </x-slot>
                    @endaccess
                </x-select>
            </div></div>
            <div class="card"><div class="card-body">
                <div class="summary-row"><span>Items / quantity</span><span><span id="summary-count">0</span> / <span id="summary-qty">0</span></span></div>
                <div class="summary-row"><span>Subtotal</span><span id="summary-subtotal">0.00</span></div>
                <div class="summary-row"><label for="discount">Discount</label><input class="form-control form-control-sm @error('discount') is-invalid @enderror" type="number" id="discount" name="discount" min="0" step="any" value="{{ old('discount') }}" placeholder="0.00"></div>
                @if ($mode === 'purchase')
                    <div class="summary-row"><label for="other_charges">Other charges</label><input class="form-control form-control-sm" type="number" id="other_charges" name="other_charges" min="0" step="any" value="{{ old('other_charges') }}" placeholder="0.00"></div>
                @endif
                <div class="summary-row total"><span>Grand total</span><span>{{ setting('currency_symbol') }} <span id="summary-grand">0.00</span></span></div>
                <div class="summary-row">
                    <label for="paid_amount">Paid <button type="button" class="btn btn-link btn-sm p-0 ms-1" id="pay-full">Full</button></label>
                    <input class="form-control form-control-sm @error('paid_amount') is-invalid @enderror" type="number" id="paid_amount" name="paid_amount" min="0" step="any" value="{{ old('paid_amount') }}" placeholder="0.00">
                </div>
                <div class="summary-row"><label for="payment_method">Payment method</label>
                    <select class="form-select form-select-sm" style="max-width:150px" id="payment_method" name="payment_method">
                        @foreach (['cash' => 'Cash', 'bank' => 'Bank', 'card' => 'Card', 'mobile' => 'Mobile banking', 'other' => 'Other'] as $key => $text)
                            <option value="{{ $key }}" @selected(old('payment_method', 'cash') === $key)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="summary-row fw-semibold"><span>Due</span><span id="summary-due">0.00</span></div>
                <x-textarea name="notes" label="Notes" placeholder="Notes (optional)" rows="2" maxlength="1000" wrapper="mt-2 mb-3" />
                <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit"><i class="bi bi-check2-circle me-1"></i>{{ $mode === 'sale' ? 'Save Sale' : 'Save Purchase' }}</button></div>
            </div></div>
        </div>
    </div>
</form>

@access($mode === 'sale' ? 'customers' : 'suppliers', 'create')
    @include('invoices._party-modal')
@endaccess

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/select2/select2-bootstrap-5-theme.min.css') }}">
@endpush
@push('scripts')
<script src="{{ asset('assets/vendor/select2/select2.min.js') }}"></script>
<script src="{{ asset('assets/js/party-select.js') }}"></script>
<script src="{{ asset('assets/js/quick-add.js') }}"></script>
@endpush
