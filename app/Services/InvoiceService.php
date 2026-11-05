<?php

namespace App\Services;

use App\Mail\InvoiceMail;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class InvoiceService
{
    /** Renders the invoice of a purchase or a sale as a niklasravnsborg/laravel-pdf document. */
    public function pdf(Model $invoice)
    {
        $invoice->loadMissing('details.product.unit');
        $isSale = $invoice instanceof Sale;
        $logo = setting('site_logo');

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'title' => $isSale ? 'SALES INVOICE' : 'PURCHASE INVOICE',
            'partyLabel' => $isSale ? 'Bill To' : 'Supplier',
            'party' => $isSale ? $invoice->customer : $invoice->supplier,
            'date' => $isSale ? $invoice->sale_date : $invoice->purchase_date,
            'logoPath' => $logo && Storage::disk('public')->exists($logo) ? Storage::disk('public')->path($logo) : null,
        ], [], [
            'title' => $invoice->reference_no,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 18,
            'margin_footer' => 6,
        ]);
    }

    /** A normal Laravel response, so a stray warning cannot break the file the way mPDF's direct output does. */
    public function response(Model $invoice, bool $download = false): Response
    {
        return response($this->pdf($invoice)->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline') . '; filename="' . $this->fileName($invoice) . '"',
        ]);
    }

    public function fileName(Model $invoice): string
    {
        return $invoice->reference_no . '.pdf';
    }

    public function email(Model $invoice, string $email, ?string $message = null): void
    {
        Mail::to($email)->send(new InvoiceMail($invoice, $this->pdf($invoice)->output(), $this->fileName($invoice), $message));
    }
}
