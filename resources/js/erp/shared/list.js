export function escapeListHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, function(character) {
        return {'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'}[character];
    });
}

export function renderEntityAvatar(entity, imageFolder, isLogo = true) {
    const photo = String(entity && entity.photo ? entity.photo : '').trim();
    const photoPath = entity && entity.photo_path && photo
        ? String(entity.photo_path).trim()
        : (photo && imageFolder ? 'storage/images/erp/'+imageFolder+'/'+photo : '');
    const avatarClass = isLogo ? 'erp-avatar erp-logo' : 'erp-avatar';

    if(!photo || !photoPath || photoPath === 'images/no-image.jpg'){
        return '<span class="'+avatarClass+' erp-avatar-empty" role="img" aria-label="Sin imagen"><i class="fa-regular fa-image" aria-hidden="true"></i></span>';
    }

    const normalizedPath = photoPath.charAt(0) === '/' ? photoPath : '/'+photoPath;
    return '<span class="'+avatarClass+'" role="img" aria-label="Imagen asociada" style="background-image:url(\''+escapeListHtml(normalizedPath)+'\');"></span>';
}