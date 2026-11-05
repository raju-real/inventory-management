<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Product</th><th class="text-end">Opening</th><th class="text-end">Purchased</th><th class="text-end">Cust. returns</th><th class="text-end">Sold</th><th class="text-end">Supp. returns</th><th class="text-end">Damaged</th><th class="text-end">Adjusted</th><th class="text-end">Available</th></tr></thead>
            <tbody>
            @forelse ($products as $product)
                <tr>
                    <td class="fw-medium">{{ $product->name }}<div class="small text-muted fw-normal">{{ $product->sku }} · {{ $product->category?->name }}</div></td>
                    <td class="num">{{ qty($product->opening_qty) }}</td>
                    <td class="num">{{ qty($product->purchased_qty) }}</td>
                    <td class="num">{{ qty($product->customer_returned_qty) }}</td>
                    <td class="num">{{ qty($product->sold_qty) }}</td>
                    <td class="num">{{ qty($product->supplier_returned_qty) }}</td>
                    <td class="num">{{ qty($product->damaged_qty) }}</td>
                    <td class="num">{{ qty($product->adjusted_qty) }}</td>
                    <td class="num fw-semibold {{ $product->is_low_stock ? 'out' : '' }}">{{ qty($product->current_stock) }} <small class="fw-normal text-muted">{{ $product->unit?->short_name }}</small> @if ($product->is_low_stock)<span class="badge tone-red">Low</span>@endif</td>
                </tr>
            @empty
                <x-empty-row :cols="9" />
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :items="$products" />
</div>
