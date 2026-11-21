import Alpine from 'alpinejs';
import htmx from 'htmx.org';

window.htmx = htmx;
window.Alpine = Alpine;
Alpine.start();

document.addEventListener('htmx:beforeHistoryUpdate', ({ detail }) => {
    if (detail.target?.id === 'directory' && detail.xhr?.responseURL) {
        detail.history.path = detail.xhr.responseURL;
    }
});

function syncDirectoryMetadata() {
    const directory = document.getElementById('directory');
    if (directory) {
        document.querySelector('meta[name="robots"]').content = directory.dataset.robots;
        document.querySelector('link[rel="canonical"]').href = directory.dataset.canonical;
    }
}
document.addEventListener('htmx:afterSwap', syncDirectoryMetadata);
document.addEventListener('htmx:historyRestore', syncDirectoryMetadata);
