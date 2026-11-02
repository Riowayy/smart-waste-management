document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-user-report-form]');
    if (!form) return;

    const input = form.querySelector('[data-report-image]');
    const preview = form.querySelector('[data-image-preview]');
    const image = form.querySelector('[data-preview-image]');
    const feedback = form.querySelector('[data-image-feedback]');
    const submit = form.querySelector('[data-report-submit]');
    const description = form.querySelector('#description');
    const characterCount = form.querySelector('[data-description-count]');
    const updateCharacterCount = () => {
        characterCount.textContent = `${Array.from(description.value).length} / ${description.maxLength} karakter`;
    };
    description.addEventListener('input', updateCharacterCount);
    updateCharacterCount();
    let objectUrl;
    let imageError = false;

    const clearPreview = () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        image.removeAttribute('src');
        preview.hidden = true;
    };

    input.addEventListener('change', () => {
        clearPreview();
        feedback.textContent = '';
        input.removeAttribute('aria-invalid');
        imageError = false;
        const file = input.files[0];
        if (!file) return;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            feedback.textContent = 'Pilih foto JPEG, PNG, atau WebP dengan ukuran maksimal 5 MB.';
            input.setAttribute('aria-invalid', 'true');
            imageError = true;
            return;
        }

        objectUrl = URL.createObjectURL(file);
        image.src = objectUrl;
        preview.hidden = false;
    });

    image.addEventListener('error', () => {
        clearPreview();
        feedback.textContent = 'Foto tidak dapat dibaca. Pilih foto JPEG, PNG, atau WebP yang valid.';
        input.setAttribute('aria-invalid', 'true');
        imageError = true;
    });

    form.addEventListener('submit', (event) => {
        if (imageError) {
            event.preventDefault();
            input.focus();
            return;
        }
        submit.disabled = true;
        submit.textContent = 'Mengirim laporan…';
        form.setAttribute('aria-busy', 'true');
    });

    window.addEventListener('pagehide', clearPreview);
    window.addEventListener('pageshow', () => {
        submit.disabled = false;
        submit.textContent = 'Kirim Laporan';
        form.removeAttribute('aria-busy');
    });
    form.querySelector('[data-form-errors]')?.focus();
});
