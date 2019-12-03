function DownloadData() {
    download_switch = document.getElementById('download');
    if (download_switch) {
        download_switch.value = 1;
    }
    document.getElementById('frm_browse').submit();
}