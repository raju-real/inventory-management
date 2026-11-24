@php $vat = \App\Services\SaleCalculator::vatDefaults(); $mode = old('vat_default_mode', $vat['mode']); @endphp
<div class="card" style="max-width:760px"><div class="card-body">
    <p class="text-muted small mb-3">Every new sale starts with this VAT. The cashier can still change it on the sale screen.</p>
    <form method="POST" action="{{ route('settings.vat') }}" class="needs-validation" novalidate id="vat-settings-form">
        @csrf @method('PUT')
        <div class="row">
            <div class="col-md-6">
                <x-select name="vat_default_mode" label="VAT calculation" :options="['product' => 'Product wise (each product\'s own VAT)', 'order' => 'On total order']" :placeholder="false" :value="$mode" required />
            </div>
        </div>
        <div class="row" id="vat-order-fields">
            <div class="col-md-6">
                <x-select name="vat_default_type" label="VAT type" :options="['percentage' => 'Percentage (%)', 'flat' => 'Flat amount']" :placeholder="false" :value="old('vat_default_type', $vat['type'])" required />
            </div>
            <div class="col-md-6">
                <x-input name="vat_default_value" type="number" label="VAT" placeholder="e.g. 5" :value="old('vat_default_value', $vat['value'])" required min="0" step="any" help="Percentage: charged on the total after discount. Flat: one amount added to the order." />
            </div>
        </div>
        <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save VAT Settings</button></div>
    </form>
</div></div>
@push('scripts')
<script>
(function () {
    const mode = document.getElementById('vat_default_mode');
    const type = document.getElementById('vat_default_type');
    const value = document.getElementById('vat_default_value');
    const fields = document.getElementById('vat-order-fields');
    function sync() {
        const onOrder = mode.value === 'order';
        fields.classList.toggle('d-none', !onOrder);
        // Hidden fields are not sent, so the saved order VAT is kept for later.
        type.disabled = !onOrder;
        value.disabled = !onOrder;
        value.max = type.value === 'percentage' ? '100' : '';
    }
    mode.addEventListener('change', sync);
    type.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
