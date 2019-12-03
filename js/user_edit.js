function ConfirmDelete(msg) {
    if (window.confirm(msg)) {
        document.getElementById('delete').value = '1';
        document.getElementById('theform').submit();
    }
    return;
}