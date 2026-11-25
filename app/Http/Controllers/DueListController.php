<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Supplier;
use App\Services\DueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class DueListController extends Controller
{
    public function __construct(private DueService $service)
    {
        $this->middleware('access:report-due-list,view');
    }

    public function index(Request $request)
    {
        [$type, $view, $filters] = $this->filters($request);
        $data = $view === 'invoice'
            ? ['rows' => $this->service->invoiceWise($type, $filters), 'totals' => $this->service->invoiceTotals($type, $filters)]
            : ['rows' => $this->service->partyWise($type, $filters), 'totals' => $this->service->partyTotals($type, $filters)];

        return view('reports.due-list', $data + [
            'type' => $type,
            'view' => $view,
            't' => DueService::TYPES[$type],
        ]);
    }

    /** Party wise: one summary row per customer or supplier. Invoice wise: a statement grouped by party. */
    public function pdf(Request $request)
    {
        [$type, $view, $filters] = $this->filters($request);
        $t = DueService::TYPES[$type];
        $logo = setting('site_logo');
        $data = [
            'type' => $type,
            'view' => $view,
            't' => $t,
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
            'logoPath' => $logo && Storage::disk('public')->exists($logo) ? Storage::disk('public')->path($logo) : null,
            'title' => $view === 'invoice' ? strtoupper($t['label']) . ' DUE STATEMENT' : strtoupper($t['label']) . ' DUE LIST',
        ];

        if ($view === 'invoice') {
            $data['parties'] = $this->service->statement($type, $filters);
        } else {
            $data['rows'] = $this->service->partyWise($type, $filters, false);
            $data['totals'] = $this->service->partyTotals($type, $filters);
        }

        $name = ($view === 'invoice' ? 'due-statement-' : 'due-list-') . $type . '-' . now()->format('Y-m-d') . '.pdf';
        $pdf = Pdf::loadView('pdf.due-list', $data, [], [
            'title' => $data['title'],
            'format' => 'A4',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 18,
            'margin_footer' => 6,
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline') . '; filename="' . $name . '"',
        ]);
    }

    private function filters(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $type = DueService::type($request->query('type'));
        $view = $request->query('view') === 'invoice' ? 'invoice' : 'party';
        $filters = $request->only('party_id', 'from', 'to');
        if ($view === 'party') {
            // Party wise shows what is owed today, whatever the invoice dates.
            unset($filters['from'], $filters['to']);
        }

        return [$type, $view, $this->resolveIds($filters, ['party_id' => $type === 'suppliers' ? Supplier::class : Customer::class])];
    }
}
