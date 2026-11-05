{{-- Shared return entry. Needs: $invoice, $invoiceDoc, $notFound, $recent, $action, $invoiceField, $detailField, $partyLabel, $party, $invoiceDate, $withCondition, $title, $prefix, $invoiceExample, $refundLabel --}}
<x-page-header :title="$title" sub="Scan the invoice barcode to open it, then scan each returned product.">
    <a class="btn btn-light" href="{{ route($prefix . '.index') }}">Back</a>
</x-page-header>
<x-form-errors />
<form id="invoice-lookup" method="GET" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-6">
            <label class="form-label" for="invoice">Original invoice</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                <input class="form-control" id="invoice" name="invoice" list="recent-invoices" value="{{ $invoice }}" placeholder="Scan or type invoice number, e.g. {{ $invoiceExample }}" autocomplete="off" required>
            </div>
            <datalist id="recent-invoices">@foreach ($recent as $reference)<option value="{{ $reference }}"></option>@endforeach</datalist>
        </div>
        <div class="col-auto ms-auto"><button class="btn btn-primary btn-submit" type="submit">Open invoice</button></div>
    </div>
    @if ($notFound)<div class="text-danger small mt-2">No invoice found with number {{ $invoice }}.</div>@endif
</form>

@if ($invoiceDoc)
    @if ($invoiceDoc->isCancelled())
        <div class="alert alert-danger">Invoice {{ $invoiceDoc->reference_no }} is cancelled and cannot be returned.</div>
    @else
    <form id="return-form" method="POST" action="{{ $action }}" class="needs-validation" novalidate autocomplete="off">
        @csrf
        <input type="hidden" name="{{ $invoiceField }}" value="{{ $invoiceDoc->unique_id }}">
        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">{{ $invoiceDoc->reference_no }} · {{ $party->name }} · {{ $invoiceDate->format('d M Y') }}</div>
                    <div class="table-responsive">
                        <table class="table line-table">
                            <thead><tr><th>Product</th><th class="text-end">Sold / bought</th><th class="text-end">Can return</th><th class="text-end">Unit value</th><th style="width:120px">Return qty</th>@if ($withCondition)<th style="width:140px">Condition</th>@endif<th class="text-end">Total</th></tr></thead>
                            <tbody>
                            @foreach ($invoiceDoc->details as $i => $detail)
                                @php $netPrice = $invoiceDoc->netUnitPrice((float) $detail->unit_price); @endphp
                                <tr data-codes="{{ trim($detail->product->barcode . ' ' . $detail->product->sku) }}" data-price="{{ (float) $detail->unit_price * $invoiceDoc->netRatio() }}">
                                    <td>{{ $detail->product->name }}<div class="small text-muted">{{ $detail->product->sku }} {{ $detail->product->barcode ? '· ' . $detail->product->barcode : '' }}</div>
                                        <input type="hidden" name="items[{{ $i }}][{{ $detailField }}]" value="{{ $detail->unique_id }}">
                                    </td>
                                    <td class="num">{{ qty($detail->quantity) }}</td>
                                    <td class="num">{{ qty($detail->returnable_quantity) }}</td>
                                    <td class="num">{{ number_format($netPrice, 2) }}</td>
                                    <td>
                                        <input class="form-control form-control-sm" type="number" data-role="qty" name="items[{{ $i }}][quantity]" min="0" max="{{ $detail->returnable_quantity }}" step="any" value="{{ old("items.$i.quantity") }}" placeholder="0" @disabled($detail->returnable_quantity <= 0)>
                                    </td>
                                    @if ($withCondition)
                                        <td>
                                            <select class="form-select form-select-sm" name="items[{{ $i }}][condition]">
                                                <option value="good" @selected(old("items.$i.condition", 'good') === 'good')>Good</option>
                                                <option value="damaged" @selected(old("items.$i.condition") === 'damaged')>Damaged</option>
                                            </select>
                                        </td>
                                    @endif
                                    <td class="num" data-role="total">0.00</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($withCondition)
                    <div class="form-text mt-2">Good: quantity goes back to available stock. Damaged: recorded as damaged and not added to stock.</div>
                @endif
            </div>
            <div class="col-xl-4">
                <div class="card"><div class="card-body">
                    <x-input name="return_date" type="date" label="Return date" placeholder="Select date" :value="now()->toDateString()" required :min="$invoiceDate->toDateString()" />
                    <x-input name="reason" label="Return reason" placeholder="e.g. Wrong size, defective" required maxlength="255" />
                    <div class="summary-row"><span>Return quantity</span><span id="return-qty">0</span></div>
                    <div class="summary-row total"><span>Return value</span><span>{{ setting('currency_symbol') }} <span id="return-total">0.00</span></span></div>
                    <div class="summary-row">
                        <label for="refund_amount">{{ $refundLabel }} <button type="button" class="btn btn-link btn-sm p-0 ms-1" id="refund-full">Full</button></label>
                        <input class="form-control form-control-sm @error('refund_amount') is-invalid @enderror" type="number" id="refund_amount" name="refund_amount" min="0" step="any" value="{{ old('refund_amount') }}" placeholder="0.00">
                    </div>
                    <x-textarea name="notes" label="Notes" placeholder="Notes (optional)" rows="2" maxlength="1000" wrapper="mt-2 mb-3" />
                    <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit"><i class="bi bi-check2-circle me-1"></i>Save Return</button></div>
                </div></div>
            </div>
        </div>
    </form>
    @endif
@endif
