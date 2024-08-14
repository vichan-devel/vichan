function addSecureLinkListener() {
    document.addEventListener('click', function(event) {
        var link = event.target.closest('a[data-href]');
        if (link) {
            if (event.button === 1) return;

            event.preventDefault();

            var confirmMessage = link.getAttribute('data-confirm');
            if (confirm(confirmMessage)) {
                window.location.href = link.getAttribute('data-href');
            }
        }
    });
}
document.addEventListener("DOMContentLoaded", function() {
    addSecureLinkListener();
});