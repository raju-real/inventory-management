/* Searchable customer / supplier dropdown. */
(function () {
    'use strict';

    const $select = jQuery('.js-select2');

    $select.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: $select.find('option[value=""]').text(),
    });

    // Put the cursor in the search box as soon as the dropdown opens.
    jQuery(document).on('select2:open', function () {
        const search = document.querySelector('.select2-container--open .select2-search__field');
        if (search) {
            search.placeholder = 'Type to search...';
            search.focus();
        }
    });
})();
