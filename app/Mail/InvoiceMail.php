<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;

class InvoiceMail extends Mailable
{
    use Queueable;

    public function __construct(public Model $invoice, public string $pdf, public string $fileName, public ?string $customMessage = null)
    {
    }

    public function build()
    {
        $isSale = $this->invoice instanceof Sale;

        return $this->subject(($isSale ? 'Sales' : 'Purchase') . ' invoice ' . $this->invoice->reference_no . ' from ' . setting('site_name', config('app.name')))
            ->view('emails.invoice', [
                'isSale' => $isSale,
                'party' => $isSale ? $this->invoice->customer : $this->invoice->supplier,
                'date' => $isSale ? $this->invoice->sale_date : $this->invoice->purchase_date,
            ])
            ->attachData($this->pdf, $this->fileName, ['mime' => 'application/pdf']);
    }
}
