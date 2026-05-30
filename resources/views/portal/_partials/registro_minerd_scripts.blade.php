<script>
function toggleMrd(btn) {
    const body = btn.nextElementSibling;
    const open = btn.classList.contains('open');

    if (open) {
        body.classList.add('collapsed');
        btn.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
    } else {
        body.classList.remove('collapsed');
        btn.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
    }
}
</script>
