(() => {
    const imageExtensions = new Set(['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'avif']);
    const objectUrls = new WeakMap();

    const isImage = (file) => {
        if (file.type.startsWith('image/')) {
            return true;
        }

        const extension = file.name.split('.').pop()?.toLowerCase();

        return extension ? imageExtensions.has(extension) : false;
    };

    const clearPreview = (input) => {
        (objectUrls.get(input) ?? []).forEach((url) => URL.revokeObjectURL(url));
        objectUrls.delete(input);
        input.parentElement?.querySelector(':scope > .file-upload-image-preview')?.remove();
    };

    const renderPreview = (input) => {
        clearPreview(input);

        const images = Array.from(input.files ?? []).filter(isImage);

        if (images.length === 0 || ! input.parentElement) {
            return;
        }

        const urls = [];
        const preview = document.createElement('section');
        preview.className = 'file-upload-image-preview';
        preview.setAttribute('aria-label', 'Selected image preview');

        const title = document.createElement('div');
        title.className = 'file-upload-image-preview__title';
        title.textContent = images.length === 1 ? 'Image preview' : 'Image previews';
        preview.append(title);

        const grid = document.createElement('div');
        grid.className = 'file-upload-image-preview__grid';

        images.forEach((file) => {
            const url = URL.createObjectURL(file);
            urls.push(url);

            const item = document.createElement('figure');
            item.className = 'file-upload-image-preview__item';

            const image = document.createElement('img');
            image.className = 'file-upload-image-preview__image';
            image.src = url;
            image.alt = `Preview of ${file.name}`;

            const caption = document.createElement('figcaption');
            caption.className = 'file-upload-image-preview__name';
            caption.textContent = file.name;
            caption.title = file.name;

            item.append(image, caption);
            grid.append(item);
        });

        preview.append(grid);
        input.insertAdjacentElement('afterend', preview);
        objectUrls.set(input, urls);
    };

    document.addEventListener('change', (event) => {
        const input = event.target;

        if (input instanceof HTMLInputElement && input.type === 'file') {
            renderPreview(input);
        }
    });

    document.addEventListener('reset', (event) => {
        event.target.querySelectorAll?.('input[type="file"]').forEach(clearPreview);
    });
})();
