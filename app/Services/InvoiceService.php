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

    /**
     * A till receipt for 80mm or 58mm thermal paper. The page is exactly as long as its content:
     * the receipt is laid out once on a tall page to measure it, then again at that height.
     */
    public function receipt(Sale $sale): Response
    {
        $sale->loadMissing('customer', 'creator', 'details.product.unit', 'payments');
        $width = (int) setting('receipt_width', 80) === 58 ? 58 : 80;
        $logo = setting('site_logo');
        $data = [
            'sale' => $sale,
            'width' => $width,
            'logoPath' => $logo && Storage::disk('public')->exists($logo) ? Storage::disk('public')->path($logo) : null,
        ];
        $margin = 4;
        $config = fn (float $height) => [
            'format' => [$width, $height],
            'title' => $sale->reference_no . ' receipt',
            'margin_left' => 3,
            'margin_right' => 3,
            'margin_top' => $margin,
            'margin_bottom' => $margin,
            'margin_header' => 0,
            'margin_footer' => 0,
        ];

        $probe = Pdf::loadView('pdf.receipt', $data, [], $config(2000));
        $mpdf = (fn () => $this->mpdf)->call($probe);

        // A very long receipt already runs over several tall pages, so keep that layout.
        $pdf = $mpdf->page > 1 ? $probe : Pdf::loadView('pdf.receipt', $data, [], $config(max(60, ceil($mpdf->y + $margin + 2))));

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $sale->reference_no . '-receipt.pdf"',
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
