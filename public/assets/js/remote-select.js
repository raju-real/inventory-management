/* Filter dropdowns that load customers, suppliers or products as you type, showing name and mobile (or code). */
(function () {
    'use strict';

    if (!window.jQuery || !jQuery.fn.select2) {
        return;
    }

    // Name on the first line, mobile or product code under it. Built with text nodes, never HTML from the server.
    function option(item) {
        if (item.loading || !item.id || item.name === undefined) {
            return item.text;
        }
        const $row = jQuery('<div class="remote-option"></div>');
        const $name = jQuery('<div class="remote-option-name"></div>').text(item.name);
        if (item.inactive) {
            $name.append(jQuery('<span class="badge text-bg-light border ms-1"></span>').text('Inactive'));
        }
        $row.append($name);
        if (item.sub) {
            $row.append(jQuery('<div class="remote-option-sub"></div>').text(item.sub));
        }
        return $row;
    }

    jQuery('.js-remote-select').each(function () {
        const $select = jQuery(this);
        $select.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: $select.data('placeholder'),
            allowClear: !this.required,
            minimumInputLength: 0,
            ajax: {
                url: $select.data('url'),
                dataType: 'json',
                delay: 250,
                cache: true,
                data: function (params) {
                    return { q: params.term || '', page: params.page || 1 };
                },
                processResults: function (data) {
                    return data;
                },
            },
            templateResult: option,
            templateSelection: function (item) {
                return item.text;
            },
            language: {
                searching: function () { return 'Searching...'; },
                noResults: function () { return 'Nothing found'; },
                errorLoading: function () { return 'Could not load the list. Try again.'; },
            },
        });

        $select.on('select2:open', function () {
            const search = document.querySelector('.select2-container--open .select2-search__field');
            if (search) {
                search.placeholder = $select.data('search-hint') || 'Type to search...';
                search.focus();
            }
        });
    });
})();
