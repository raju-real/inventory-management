<?php

namespace App\Services;

use App\Models\CustomerPayment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService extends BaseService
{
    public function paginateCustomer(array $filters)
    {
        return CustomerPayment::rememberCache($this->listKey($filters), function () use ($filters) {
            $query = CustomerPayment::with('customer', 'sale', 'creator')->latest('id');

            return $this->applyFilters($query, $filters, 'customer')->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    public function paginateSupplier(array $filters)
    {
        return SupplierPayment::rememberCache($this->listKey($filters), function () use ($filters) {
            $query = SupplierPayment::with('supplier', 'purchase', 'creator')->latest('id');

            return $this->applyFilters($query, $filters, 'supplier')->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    private function applyFilters($query, array $filters, string $party)
    {
        if (! empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(fn ($q) => $q->where('reference_no', 'like', $search)
                ->orWhereHas($party, fn ($p) => $p->where('name', 'like', $search)));
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('payment_date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('payment_date', '<=', $filters['to']);
        }

        return $query;
    }

    public function createCustomerPayment(array $data): CustomerPayment
    {
        return DB::transaction(function () use ($data) {
            $sale = empty($data['sale_id']) ? null : Sale::whereKey($data['sale_id'])->lockForUpdate()->firstOrFail();

            if ($sale) {
                $this->assertInvoicePayable($sale, (int) $data['customer_id'], (int) $sale->customer_id, (float) $data['amount']);
            }

            $payment = CustomerPayment::create($data);
            $sale?->recalculateBalance();

            return $payment;
        }, 3);
    }

    public function createSupplierPayment(array $data): SupplierPayment
    {
        return DB::transaction(function () use ($data) {
            $purchase = empty($data['purchase_id']) ? null : Purchase::whereKey($data['purchase_id'])->lockForUpdate()->firstOrFail();

            if ($purchase) {
                $this->assertInvoicePayable($purchase, (int) $data['supplier_id'], (int) $purchase->supplier_id, (float) $data['amount']);
            }

            $payment = SupplierPayment::create($data);
            $purchase?->recalculateBalance();

            return $payment;
        }, 3);
    }

    public function cancelCustomerPayment(CustomerPayment $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason) {
            $payment = CustomerPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $this->assertNotCancelled($payment);
            $payment->markCancelled($reason);
            $payment->sale?->recalculateBalance();
        }, 3);
    }

    public function cancelSupplierPayment(SupplierPayment $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason) {
            $payment = SupplierPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $this->assertNotCancelled($payment);
            $payment->markCancelled($reason);
            $payment->purchase?->recalculateBalance();
        }, 3);
    }

    private function assertInvoicePayable($invoice, int $partyId, int $invoicePartyId, float $amount): void
    {
        if ($invoice->isCancelled()) {
            throw ValidationException::withMessages(['amount' => 'This invoice is cancelled.']);
        }
        if ($partyId !== $invoicePartyId) {
            throw ValidationException::withMessages(['amount' => 'This invoice belongs to a different party.']);
        }
        if ($amount > (float) $invoice->due_amount) {
            throw ValidationException::withMessages(['amount' => 'Amount cannot be more than the invoice due (' . number_format((float) $invoice->due_amount, 2) . ').']);
        }
    }

    private function assertNotCancelled($payment): void
    {
        if ($payment->isCancelled()) {
            throw ValidationException::withMessages(['payment' => 'This payment is already cancelled.']);
        }
    }
}
