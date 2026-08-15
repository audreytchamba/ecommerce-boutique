/**
 * assets/js/admin/admin-media.js
 * Gère la prévisualisation des images/vidéos dans les formulaires admin.
 */

document.addEventListener('DOMContentLoaded', () => {

    /**
     * Gère la prévisualisation du média principal (image ou vidéo unique)
     */
    const mediaInput = document.getElementById('media_file');
    const mediaPreviewContainer = document.getElementById('media-preview');
    const existingMediaInfo = document.getElementById('existing-media-info');

    if (mediaInput && mediaPreviewContainer) {
        mediaInput.addEventListener('change', (event) => {
            const file = event.target.files[0];

            // Vide l'aperçu précédent
            mediaPreviewContainer.innerHTML = '';

            if (existingMediaInfo) {
                // Cache l'info du média actuel si on en choisit un nouveau
                existingMediaInfo.style.display = file ? 'none' : 'block';
            }

            if (!file) {
                return; // Aucun fichier sélectionné
            }

            const reader = new FileReader();

            reader.onload = (e) => {
                let previewElement;
                if (file.type.startsWith('image/')) {
                    previewElement = document.createElement('img');
                    previewElement.src = e.target.result;
                    previewElement.alt = 'Aperçu du nouveau média';
                } else if (file.type.startsWith('video/')) {
                    previewElement = document.createElement('video');
                    previewElement.src = e.target.result;
                    previewElement.controls = true;
                    previewElement.muted = true;
                    previewElement.autoplay = true;
                    previewElement.loop = true;
                }
                if (previewElement) {
                    mediaPreviewContainer.appendChild(previewElement);
                }
            };

            reader.readAsDataURL(file);
        });
    }

    /**
     * Gère la prévisualisation de la galerie d'images (fichiers multiples)
     */
    const galleryInput = document.getElementById('gallery_files');
    const galleryPreviewContainer = document.getElementById('gallery-preview-new');

    if (galleryInput && galleryPreviewContainer) {
        galleryInput.addEventListener('change', (event) => {
            // Vide l'aperçu des nouveaux ajouts
            galleryPreviewContainer.innerHTML = '';
            const files = event.target.files;

            if (files.length > 0) {
                const heading = document.createElement('p');
                heading.textContent = 'Nouveaux ajouts pour la galerie :';
                heading.className = 'gallery-preview-heading';
                galleryPreviewContainer.appendChild(heading);
            }

            for (const file of files) {
                // On s'assure que ce sont bien des images
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'gallery-preview-item';

                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Aperçu de l\'image';

                        wrapper.appendChild(img);
                        galleryPreviewContainer.appendChild(wrapper);
                    };
                    reader.readAsDataURL(file);
                }
            }
        });
    }
});