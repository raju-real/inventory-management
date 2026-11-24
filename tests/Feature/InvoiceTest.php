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

    /** Page size in mm from the PDF's MediaBox: [width, height]. */
    private function pageSize(string $pdf): array
    {
        preg_match('/\/MediaBox\s*\[\s*0\s+0\s+([\d.]+)\s+([\d.]+)\s*\]/', $pdf, $box);

        return [round($box[1] * 25.4 / 72), round($box[2] * 25.4 / 72)];
    }

    public function test_the_pos_receipt_fits_the_paper_and_grows_with_the_items()
    {
        $products = collect(range(1, 12))->map(fn ($i) => $this->product(['opening_stock' => 50]));
        $this->postSale($products[0], 1, ['paid_amount' => 150])->assertSessionHasNoErrors();
        $this->postSale($products[0], 1, ['items' => $products->map(fn ($p) => ['product_id' => $p->unique_id, 'quantity' => 2, 'unit_price' => 150])->all(), 'discount' => 100]);
        [$short, $long] = Sale::oldest('id')->get();

        $response = $this->get(route('sales.receipt', $short))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
        $pdf = $response->getContent();
        $this->assertStringStartsWith('%PDF', $pdf);
        [$width, $height] = $this->pageSize($pdf);
        $this->assertSame(80.0, (float) $width);
        $this->assertLessThan(200, $height, 'A one-item receipt must not waste paper.');

        [, $longHeight] = $this->pageSize($this->get(route('sales.receipt', $long))->getContent());
        $this->assertGreaterThan($height + 40, $longHeight, 'More items must make a longer receipt.');

        $this->put(route('settings.update'), ['site_name' => 'Shop', 'currency_symbol' => 'Tk', 'receipt_width' => 58])->assertSessionHasNoErrors();
        $this->assertSame(58.0, (float) $this->pageSize($this->get(route('sales.receipt', $short))->getContent())[0]);
        $this->put(route('settings.update'), ['site_name' => 'Shop', 'currency_symbol' => 'Tk', 'receipt_width' => 70])->assertSessionHasErrors('receipt_width');
    }

    public function test_the_receipt_is_offered_on_sales_only_and_follows_permissions()
    {
        $this->postSale($this->product(), 1);
        $this->postPurchase($this->product(), 1);
        $sale = Sale::first();

        $this->get(route('sales.show', $sale))->assertSee(route('sales.receipt', $sale))->assertSee('POS Receipt');
        $this->get(route('sales.index'))->assertSee(route('sales.receipt', $sale));
        $this->get(route('purchases.show', Purchase::first()))->assertDontSee('POS Receipt');

        $this->post(route('sales.cancel', $sale), ['cancel_reason' => 'x']);
        $this->assertStringStartsWith('%PDF', $this->get(route('sales.receipt', $sale))->assertOk()->getContent());

        $this->actingAs($this->staff(['products' => ['view']]))->get(route('sales.receipt', $sale))->assertForbidden();
        $this->get('sales/' . $sale->id . '/receipt')->assertNotFound();
    }

    public function test_a_viewer_without_access_cannot_download_the_invoice()
    {
        $product = $this->product();
        $this->postSale($product, 1);

        $this->actingAs($this->staff(['products' => ['view']]))->get(route('sales.pdf', Sale::first()))->assertForbidden();
    }
}
