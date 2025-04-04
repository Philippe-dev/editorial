'use strict';
dotclear.ready(() => {
    $('#link-insert-cancel').on('click', function () {
        window.close();
    });
    $('#form-entries tr>td.maximal>a').on('click', function () {
        function stripBaseURL(url) {
            if (base_url != '') {
                const pos = url.indexOf(base_url);
                if (pos == 0) {
                    return url.substr(base_url.length);
                }
            }
            return url;
        }
        const main = window.opener;
        const base_url = main.$('input[name="base_url"]').val();
        const title = stripBaseURL($(this).attr('title'));
        const next = title.indexOf('/');
        const href = next !== -1 ? title.substring(next + 1) : title;
        main.$('#featured_post_url').prop('value', href);
        window.close();
    });
});