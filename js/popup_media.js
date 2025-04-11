'use strict';
dotclear.ready(() => {
    $('#media-select-cancel').on('click', () => {
        window.close();
    });
    if (window.opener) {
        document.getElementById('media-select-ok').addEventListener('click', () => {
            const main = window.opener;
            const href = document.querySelector('input[name="url"]').value;
            const thumburl = document.querySelectorAll('input[name="src"]')[2].value;
            const buttonId = main.document.querySelector('input[name="change-button-id"]').value;

            if (buttonId === 'default_image_selector') {
                main.document.getElementById('default_image_tb_url').value = thumburl;
                main.document.getElementById('default_image_url').value = href;
                main.document.getElementById('default_image_url').dispatchEvent(new Event('change'));
            }
            if (buttonId === 'default_small_image_selector') {
                main.document.getElementById('default_small_image_tb_url').value = thumburl;
                main.document.getElementById('default_small_image_url').value = href;
                main.document.getElementById('default_small_image_url').dispatchEvent(new Event('change'));
            }
            window.close();
        });
    }
});
