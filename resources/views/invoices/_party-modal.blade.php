{{-- Add a customer or supplier without leaving the invoice. Needs: $mode --}}
@php $partyWord = $mode === 'sale' ? 'Customer' : 'Supplier'; @endphp
<div class="modal fade" id="partyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="party-form" data-quick-add="#{{ $mode === 'sale' ? 'customer_id' : 'supplier_id' }}" method="POST" action="{{ $mode === 'sale' ? route('customers.store') : route('suppliers.store') }}" novalidate autocomplete="off">
            @csrf
            <input type="hidden" name="status" value="1">
            <div class="modal-header"><h2 class="modal-title h5">Add {{ $partyWord }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <x-input name="name" id="party_name" :label="$partyWord . ' name'" :placeholder="$mode === 'sale' ? 'e.g. Rahim Uddin' : 'e.g. ABC Traders'" required maxlength="255" />
                @if ($mode === 'purchase')
                    <x-input name="contact_person" id="party_contact" label="Contact person" placeholder="e.g. Karim Ahmed" maxlength="255" />
                @endif
                <x-input name="mobile" id="party_mobile" label="Mobile number" placeholder="e.g. 01712345678" pattern="[0-9+\-\s\(\)]{6,20}" feedback="Enter a valid mobile number (6-20 digits)." />
                <x-input name="email" id="party_email" type="email" label="Email" :placeholder="strtolower($partyWord) . '@example.com'" maxlength="255" />
                <x-input name="address" id="party_address" label="Address" placeholder="Street, area, city" maxlength="255" wrapper="mb-0" />
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary btn-submit"><i class="bi bi-check2-circle me-1"></i>Save {{ $partyWord }}</button></div>
        </form>
    </div>
</div>
