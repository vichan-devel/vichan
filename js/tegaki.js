/*
 * tegaki.js - Add support for tegaki drawing 
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js'; (required if both below are in use)
 *   $config['additional_javascript'][] = 'js/ajax.js'; // (optional) to clear state after upload
 *   $config['additional_javascript'][] = 'js/file-selector.js'; // (optional) for thumbnails
 *   $config['additional_javascript'][] = 'js/tegaki/tegaki.min.js';
 *   $config['additional_javascript'][] = 'js/tegaki.js';
 */

function clearTegaki(blob = null) {
    const edit = document.getElementById('tegaki-edit');
    const clear = document.getElementById('tegaki-clear');
    const start = document.getElementById('tegaki-start');
    const fileInput = document.getElementById('upload_file');

    if (typeof FileSelector !== 'undefined' && FileSelector.removeFile) {
        FileSelector.removeFile(blob);
    } else {
        fileInput.value = '';
    }

    toggleButtonState(start, true);
    toggleButtonState(edit, false, true);
    toggleButtonState(clear, false, true);
}

function toggleButtonState(button, isVisible, isDisabled = false) {
    button.style.display = isVisible ? '' : 'none';
    button.disabled = isDisabled;
}

function initializeTegaki() {
    if (!['thread', 'index', 'ukko', 'catalog'].includes(active_page)) return;

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = `${configRoot}js/tegaki/tegaki.css`;
    document.head.appendChild(link);

    const upload = document.querySelector('#upload');
    upload.insertAdjacentHTML('afterend', `
        <tr id="tegaki-form">
            <th>Tegaki</th>
            <td id="tegaki-buttons">
                <input type="text" id="width-tegaki" title="${_('Width')}" class="tegaki-input" size="4" maxlength="4" value="800"> x 
                <input type="text" id="height-tegaki" title="${_('Height')}" class="tegaki-input" size="4" maxlength="4" value="800">
                <input type="button" id="tegaki-start" value="${_('Draw')}">
                <input type="button" id="tegaki-edit" style="display: none;" value="${_('Edit')}" disabled>
                <input type="button" id="tegaki-clear" style="display: none;" value="${_('Clear')}" disabled>
            </td>
        </tr>
    `);

    document.getElementById('tegaki-start').addEventListener('click', startTegaki);
}

function afterDraw(blob) {
    const edit = document.getElementById('tegaki-edit');
    const clear = document.getElementById('tegaki-clear');
    const start = document.getElementById('tegaki-start');

    toggleButtonState(clear, true);
    toggleButtonState(edit, true);
    toggleButtonState(start, false, true);

    edit.addEventListener('click', () => {
        clearTegaki(blob);
        startTegaki();
    });

    clear.addEventListener('click', () => {
        clearTegaki(blob);
    });
}

function startTegaki() {
    if (typeof Tegaki === 'undefined') {
        console.error('Tegaki library is not loaded.');
        return;
    }

    Tegaki.open({
        onDone: () => {
            Tegaki.flatten().toBlob((blob) => {
                const tmp = new File([blob], "Tegaki.png", { type: 'image/png' });

                if (typeof FileSelector !== 'undefined' && FileSelector.addFile) {
                    FileSelector.addFile(tmp);
                } else {
                    const fileInput = document.getElementById('upload_file');
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(tmp);
                    fileInput.files = dataTransfer.files;
                }

                afterDraw(tmp);
            }, 'image/png');
        },
        onCancel: () => {
            console.log('Closing...');
        },
        width: document.getElementById('width-tegaki')?.value.trim() || '800',
        height: document.getElementById('height-tegaki')?.value.trim() || '800'
    });
}

if (typeof jQuery !== 'undefined') {
    $(document).on('ajax_after_post', () => {
        clearTegaki();
    });
}

onReady(initializeTegaki);
