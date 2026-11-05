<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    public function test_sales_and_purchase_invoices_render_as_real_pdf_files()
    {
        $product = $this->product();
        $this->postSale($product, 2, ['discount' => 10, 'paid_amount' => 50, 'notes' => 'Handle with care']);
        $this->postPurchase($product, 3, ['other_charges' => 5]);

        foreach ([route('sales.pdf', Sale::first()), route('purchases.pdf', Purchase::first())] as $url) {
            $response = $this->get($url)->assertOk();
            $response->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }

        $download = $this->get(route('sales.pdf', [Sale::first(), 'download' => 1]))->assertOk();
        $this->assertStringContainsString('attachment', $download->headers->get('content-disposition'));
    }

    public function test_a_cancelled_invoice_still_renders()
    {
        $product = $this->product();
        $this->postSale($product, 1);
        $sale = Sale::first();
        $this->post(route('sales.cancel', $sale), ['cancel_reason' => 'x']);

        $this->assertStringStartsWith('%PDF', $this->get(route('sales.pdf', $sale))->assertOk()->getContent());
    }

    public function test_an_invoice_is_emailed_with_the_pdf_attached()
    {
        Mail::fake();
        $product = $this->product();
        $this->postSale($product, 1);
        $sale = Sale::first();

        $this->post(route('sales.email', $sale), ['email' => 'buyer@example.com', 'message' => 'Thanks'])->assertSessionHasNoErrors()->assertSessionHas('success');

        Mail::assertSent(InvoiceMail::class, function (InvoiceMail $mail) use ($sale) {
            $mail->build();

            return $mail->hasTo('buyer@example.com')
                && str_starts_with($mail->pdf, '%PDF')
                && count($mail->rawAttachments) === 1
                && $mail->rawAttachments[0]['name'] === $sale->reference_no . '.pdf'
                && str_contains($mail->subject, $sale->reference_no);
        });
    }

    public function test_the_purchase_invoice_email_works_and_renders_its_body()
    {
        Mail::fake();
        $product = $this->product();
        $this->postPurchase($product, 1);
        $purchase = Purchase::first();

        $this->post(route('purchases.email', $purchase), ['email' => 'vendor@example.com'])->assertSessionHas('success');

        Mail::assertSent(InvoiceMail::class, fn (InvoiceMail $mail) => $mail->hasTo('vendor@example.com'));
    }

    public function test_the_email_body_renders()
    {
        $this->postPurchase($this->product(), 1);
        $purchase = Purchase::first();

        $html = (new InvoiceMail($purchase->load('supplier'), '%PDF', 'a.pdf', 'Hello there'))->render();

        $this->assertStringContainsString($purchase->reference_no, $html);
        $this->assertStringContainsString('Hello there', $html);
    }

    public function test_an_invalid_address_is_rejected_and_a_mail_failure_shows_an_error()
    {
        $product = $this->product();
        $this->postSale($product, 1);
        $sale = Sale::first();

        $this->post(route('sales.email', $sale), ['email' => 'not-an-email'])->assertSessionHasErrors('email');

        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 1]);
        $this->post(route('sales.email', $sale), ['email' => 'buyer@example.com'])->assertSessionHas('error');
    }

    public function test_an_email_address_with_a_line_break_is_rejected()
    {
        $this->postSale($this->product(), 1);

        $this->post(route('sales.email', Sale::first()), ['email' => "buyer@example.com\r\nBcc: spam@example.com"])->assertSessionHasErrors('email');
    }

    public function test_a_viewer_without_access_cannot_download_the_invoice()
    {
        $product = $this->product();
        $this->postSale($product, 1);

        $this->actingAs($this->staff(['products' => ['view']]))->get(route('sales.pdf', Sale::first()))->assertForbidden();
    }
}
