<?php

namespace App\Http\Controllers\Purchase;

use App\Enums\PurchaseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseController extends Controller
{
    public function index()
    {
        return view('purchases.index', [
            'purchases' => Purchase::latest()->get(),
        ]);
    }

    public function approvedPurchases()
    {
        $purchases = Purchase::with(['supplier'])
            ->where('status', PurchaseStatus::APPROVED)->get(); // 1 = approved

        return view('purchases.approved-purchases', [
            'purchases' => $purchases,
        ]);
    }

    public function show(Purchase $purchase)
    {
        // N+1 Problem if load 'createdBy', 'updatedBy',
        $purchase->loadMissing(['supplier', 'details'])->get();

        return view('purchases.show', [
            'purchase' => $purchase,
        ]);
    }

    public function edit(Purchase $purchase)
    {
        // N+1 Problem if load 'createdBy', 'updatedBy',
        $purchase->with(['supplier', 'details'])->get();

        return view('purchases.edit', [
            'purchase' => $purchase,
        ]);
    }

    public function create()
    {
        return view('purchases.create', [
            'categories' => Category::select(['id', 'name'])->get(),
            'suppliers' => Supplier::select(['id', 'name'])->get(),
        ]);
    }

    public function store(StorePurchaseRequest $request)
    {
        $purchase = Purchase::create($request->all());

        /*
         * TODO: Must validate that
         */
        if (! $request->invoiceProducts == null) {
            $pDetails = [];

            foreach ($request->invoiceProducts as $product) {
                $pDetails['purchase_id'] = $purchase['id'];
                $pDetails['product_id'] = $product['product_id'];
                $pDetails['quantity'] = $product['quantity'];
                $pDetails['unitcost'] = $product['unitcost'];
                $pDetails['total'] = $product['total'];
                $pDetails['created_at'] = Carbon::now();

                $purchase->details()->insert($pDetails);
            }
        }

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Purchase has been created!');
    }

    public function update(Purchase $purchase, Request $request)
    {
        $isUpdated = DB::transaction(function () use ($purchase) {
            $lockedPurchase = Purchase::query()
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            if ($lockedPurchase->status === PurchaseStatus::APPROVED) {
                return false;
            }

            $products = PurchaseDetails::query()
                ->where('purchase_id', $lockedPurchase->id)
                ->lockForUpdate()
                ->get();

            foreach ($products as $product) {
                Product::query()
                    ->whereKey($product->product_id)
                    ->lockForUpdate()
                    ->increment('quantity', $product->quantity);
            }

            $lockedPurchase->update([
                'status' => PurchaseStatus::APPROVED,
                'updated_by' => auth()->id(),
            ]);

            return true;
        });

        if (! $isUpdated) {
            return redirect()
                ->route('purchases.index')
                ->with('info', 'Purchase is already approved.');
        }

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Purchase has been approved!');
    }

    public function destroy(Purchase $purchase)
    {
        $purchase->delete();

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Purchase has been deleted!');
    }

    public function dailyPurchaseReport()
    {
        $purchases = Purchase::with(['supplier'])
            ->whereDate('date', today())
            ->latest()
            ->get();

        return view('purchases.daily-report', [
            'purchases' => $purchases,
        ]);
    }

    public function getPurchaseReport()
    {
        return view('purchases.report-purchase');
    }

    public function exportPurchaseReport(Request $request)
    {
        $rules = [
            'start_date' => 'required|string|date_format:Y-m-d',
            'end_date' => 'required|string|date_format:Y-m-d',
        ];

        $validatedData = $request->validate($rules);

        $sDate = $validatedData['start_date'];
        $eDate = $validatedData['end_date'];

        $purchases = Purchase::query()
            ->with(['details.product', 'supplier'])
            ->whereBetween('date', [$sDate, $eDate])
            ->where('status', PurchaseStatus::APPROVED)
            ->get();

        $purchase_array[] = [
            'Date',
            'No Purchase',
            'Supplier',
            'Product Code',
            'Product',
            'Quantity',
            'Unitcost',
            'Total',
        ];

        foreach ($purchases as $purchase) {
            foreach ($purchase->details as $detail) {
                $purchase_array[] = [
                    'Date' => $purchase->date->format('Y-m-d'),
                    'No Purchase' => $purchase->purchase_no,
                    'Supplier' => $purchase->supplier?->name ?? '-',
                    'Product Code' => $detail->product?->code ?? '-',
                    'Product' => $detail->product?->name ?? '-',
                    'Quantity' => $detail->quantity,
                    'Unitcost' => $detail->unitcost,
                    'Total' => $detail->total,
                ];
            }
        }

        return $this->exportExcel($purchase_array);
    }

    public function exportExcel(array $products): StreamedResponse
    {
        $spreadSheet = new Spreadsheet;
        $spreadSheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(20);
        $spreadSheet->getActiveSheet()->fromArray($products);

        return response()->streamDownload(function () use ($spreadSheet) {
            $excelWriter = new Xls($spreadSheet);
            $excelWriter->save('php://output');
        }, 'purchase-report.xls', [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }
}
