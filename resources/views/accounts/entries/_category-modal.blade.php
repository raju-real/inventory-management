{{-- Add an expense or income category without leaving the entry form. Needs: $categoryMenu, $categoryWord, $categoryExample --}}
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="category-form" method="POST" action="{{ route($categoryMenu . '.store') }}" data-quick-add="#category_id" novalidate autocomplete="off">
            @csrf
            <input type="hidden" name="status" value="1">
            <div class="modal-header"><h2 class="modal-title h5">Add {{ $categoryWord }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <x-input name="name" id="category_name" label="Category name" :placeholder="$categoryExample" required maxlength="255" />
                <x-input name="description" id="category_description" label="Description" placeholder="Short description (optional)" maxlength="255" wrapper="mb-0" />
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary btn-submit"><i class="bi bi-check2-circle me-1"></i>Save Category</button></div>
        </form>
    </div>
</div>
