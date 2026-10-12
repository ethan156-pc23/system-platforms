(function () {
    'use strict';

    document.querySelectorAll('.js-delete-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var title = form.dataset.title || 'this hotline';
            if (!window.confirm('Delete "' + title + '"? This cannot be undone.')) {
                event.preventDefault();
            }
        });
    });

    var alertBox = document.querySelector('.alert');
    if (alertBox) {
        setTimeout(function () {
            alertBox.style.transition = 'opacity 0.4s ease';
            alertBox.style.opacity = '0';
            setTimeout(function () { alertBox.remove(); }, 400);
        }, 4000);
    }
})();
