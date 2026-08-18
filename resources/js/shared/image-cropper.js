import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

const DEFAULT_OPTIONS = {
    shape: 'rectangle',
    maxWidth: 1600,
    maxHeight: 1600,
    quality: 0.86,
    mimeType: 'image/webp',
};

class ImageCropService {
    constructor(){
        this.states = new WeakMap();
        this.activeState = null;
        this.modal = null;
        this.cropper = null;
        this.previousFocusedElement = null;
    }

    handleInputChange(input, event){
        event.preventDefault();
        event.stopImmediatePropagation();
        this.open(input);
    }

    open(input){
        const file = input.files && input.files[0];
        if(!file || !file.type.startsWith('image/')){
            input.value = '';
            return;
        }

        const state = this.states.get(input) || { acceptedFile: null, previewUrl: null };
        const options = this.getOptions(input);
        const sourceUrl = URL.createObjectURL(file);

        if(this.activeState) this.finish();
        this.states.set(input, state);
        this.activeState = { input, file, options, sourceUrl, state };
        this.previousFocusedElement = document.activeElement;
        this.ensureModal();
        this.setModalState(options);
        this.modal.hidden = false;
        document.body.classList.add('image-crop-open');
        this.modal.querySelector('[data-image-crop-cancel]').focus();

        const image = this.modal.querySelector('[data-image-crop-source]');
        image.onload = () => {
            if(!this.activeState || this.activeState.sourceUrl !== sourceUrl) return;
            this.cropper = new Cropper(image, {
                aspectRatio: this.getAspectRatio(options.shape),
                viewMode: 1,
                autoCropArea: 0.9,
                responsive: true,
                restore: false,
                guides: true,
                center: true,
                highlight: true,
                background: false,
                movable: true,
                zoomable: true,
                rotatable: true,
                scalable: false,
            });
        };
        image.onerror = () => this.cancel();
        image.src = sourceUrl;
    }

    getOptions(input){
        const dataset = input.dataset || {};
        const shape = dataset.imageCrop === 'free' ? 'rectangle' : dataset.imageCrop;
        const parsedMaxWidth = Number(dataset.imageCropMaxWidth);
        const parsedMaxHeight = Number(dataset.imageCropMaxHeight);
        const parsedQuality = Number(dataset.imageCropQuality);
        const mimeType = dataset.imageCropFormat
            ? `image/${dataset.imageCropFormat.replace('image/', '')}`
            : DEFAULT_OPTIONS.mimeType;

        return {
            ...DEFAULT_OPTIONS,
            shape: ['rectangle', 'square', 'circle'].includes(shape) ? shape : DEFAULT_OPTIONS.shape,
            maxWidth: parsedMaxWidth > 0 ? parsedMaxWidth : DEFAULT_OPTIONS.maxWidth,
            maxHeight: parsedMaxHeight > 0 ? parsedMaxHeight : DEFAULT_OPTIONS.maxHeight,
            quality: parsedQuality > 0 && parsedQuality <= 1 ? parsedQuality : DEFAULT_OPTIONS.quality,
            mimeType,
        };
    }

    getAspectRatio(shape){
        return shape === 'rectangle' ? NaN : 1;
    }

    ensureModal(){
        if(this.modal) return;

        const modal = document.createElement('div');
        modal.className = 'image-crop-modal';
        modal.hidden = true;
        modal.innerHTML = `
            <div class="image-crop-dialog" role="dialog" aria-modal="true" aria-labelledby="image-crop-title">
                <div class="image-crop-header">
                    <h2 id="image-crop-title">Recortar imagen</h2>
                    <button type="button" class="image-crop-icon-button" data-image-crop-cancel title="Cerrar" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="image-crop-workspace">
                    <div class="image-crop-stage" data-image-crop-stage>
                        <img data-image-crop-source alt="Imagen seleccionada">
                    </div>
                </div>
                <div class="image-crop-toolbar" role="toolbar" aria-label="Herramientas de recorte">
                    <button type="button" class="image-crop-icon-button" data-image-crop-rotate-left title="Rotar a la izquierda" aria-label="Rotar a la izquierda">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="image-crop-icon-button" data-image-crop-rotate-right title="Rotar a la derecha" aria-label="Rotar a la derecha">
                        <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="image-crop-icon-button" data-image-crop-zoom-out title="Alejar" aria-label="Alejar">
                        <i class="fa-solid fa-magnifying-glass-minus" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="image-crop-icon-button" data-image-crop-zoom-in title="Acercar" aria-label="Acercar">
                        <i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="image-crop-icon-button" data-image-crop-reset title="Restablecer" aria-label="Restablecer">
                        <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="image-crop-footer">
                    <button type="button" class="btn btn-light" data-image-crop-cancel>Cancelar</button>
                    <button type="button" class="btn btn-primary" data-image-crop-apply>Aplicar recorte</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        this.modal = modal;

        modal.addEventListener('click', (event) => {
            if(event.target === modal) this.cancel();
        });
        modal.querySelectorAll('[data-image-crop-cancel]').forEach((button) => {
            button.addEventListener('click', () => this.cancel());
        });
        modal.querySelector('[data-image-crop-apply]').addEventListener('click', () => this.apply());
        modal.querySelector('[data-image-crop-rotate-left]').addEventListener('click', () => this.cropper?.rotate(-90));
        modal.querySelector('[data-image-crop-rotate-right]').addEventListener('click', () => this.cropper?.rotate(90));
        modal.querySelector('[data-image-crop-zoom-out]').addEventListener('click', () => this.cropper?.zoom(-0.1));
        modal.querySelector('[data-image-crop-zoom-in]').addEventListener('click', () => this.cropper?.zoom(0.1));
        modal.querySelector('[data-image-crop-reset]').addEventListener('click', () => this.cropper?.reset());
        modal.addEventListener('keydown', (event) => {
            if(event.key === 'Escape') this.cancel();
        });
    }

    setModalState(options){
        const stage = this.modal.querySelector('[data-image-crop-stage]');
        stage.classList.toggle('is-circle', options.shape === 'circle');
        stage.classList.toggle('is-square', options.shape === 'square');
        stage.classList.toggle('is-rectangle', options.shape === 'rectangle');
    }

    async apply(){
        if(!this.activeState || !this.cropper) return;

        const state = this.activeState;
        const button = this.modal.querySelector('[data-image-crop-apply]');
        button.disabled = true;

        try{
            const canvas = this.cropper.getCroppedCanvas({
                maxWidth: state.options.maxWidth,
                maxHeight: state.options.maxHeight,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            if(state.options.shape === 'circle') this.applyCircleMask(canvas);

            const blob = await this.canvasToBlob(canvas, state.options);
            const file = new File([blob], this.getOutputName(state.file.name, blob.type), {
                type: blob.type,
                lastModified: Date.now(),
            });

            this.setInputFile(state.input, file);
            this.states.set(state.input, { acceptedFile: file, previewUrl: this.showPreview(state.input, file) });
            this.finish();
        }catch(error){
            console.error('No fue posible procesar la imagen seleccionada.', error);
            this.cancel();
        }finally{
            button.disabled = false;
        }
    }

    applyCircleMask(canvas){
        const context = canvas.getContext('2d');
        const size = Math.min(canvas.width, canvas.height);
        const offsetX = (canvas.width - size) / 2;
        const offsetY = (canvas.height - size) / 2;

        context.globalCompositeOperation = 'destination-in';
        context.fillStyle = '#000';
        context.beginPath();
        context.arc(offsetX + size / 2, offsetY + size / 2, size / 2, 0, Math.PI * 2);
        context.fill();
        context.globalCompositeOperation = 'source-over';
    }

    canvasToBlob(canvas, options){
        return new Promise((resolve, reject) => {
            canvas.toBlob((blob) => {
                if(blob){
                    resolve(blob);
                    return;
                }

                canvas.toBlob((fallbackBlob) => {
                    fallbackBlob ? resolve(fallbackBlob) : reject(new Error('El navegador no pudo generar la imagen recortada.'));
                }, 'image/png');
            }, options.mimeType, options.quality);
        });
    }

    getOutputName(originalName, mimeType){
        const baseName = originalName.replace(/\.[^/.]+$/, '') || 'imagen';
        const extension = mimeType === 'image/png' ? 'png' : mimeType === 'image/jpeg' ? 'jpg' : 'webp';
        return `${baseName}.${extension}`;
    }

    setInputFile(input, file){
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;
    }

    showPreview(input, file){
        const previewUrl = URL.createObjectURL(file);
        const container = input.parentElement;
        const preview = container?.querySelector('.image_preview');
        const icon = container?.querySelector('.image-icon');
        const previousState = this.states.get(input);

        if(previousState?.previewUrl) URL.revokeObjectURL(previousState.previewUrl);

        if(preview?.tagName === 'IMG'){
            preview.src = previewUrl;
            preview.style.display = 'block';
        }else if(preview){
            preview.style.backgroundImage = `url('${previewUrl}')`;
            preview.style.display = 'block';
        }else if(container){
            container.style.backgroundImage = `url('${previewUrl}')`;
            container.style.backgroundPosition = 'center';
            container.style.backgroundRepeat = 'no-repeat';
            container.style.backgroundSize = 'cover';
        }
        if(icon) icon.style.display = 'none';
        if(container) container.style.backgroundColor = 'transparent';
        return previewUrl;
    }

    cancel(){
        if(!this.activeState) return;
        const { input, state } = this.activeState;

        if(state.acceptedFile) this.setInputFile(input, state.acceptedFile);
        else input.value = '';
        this.finish();
    }

    finish(){
        if(!this.activeState) return;

        const sourceUrl = this.activeState.sourceUrl;
        this.cropper?.destroy();
        this.cropper = null;
        URL.revokeObjectURL(sourceUrl);
        this.modal.querySelector('[data-image-crop-source]').removeAttribute('src');
        this.modal.hidden = true;
        document.body.classList.remove('image-crop-open');
        this.previousFocusedElement?.focus?.();
        this.previousFocusedElement = null;
        this.activeState = null;
    }
}

export const imageCropService = new ImageCropService();

export function handleImageCropChange(input, event){
    imageCropService.handleInputChange(input, event);
}

window.ImageCropService = imageCropService;