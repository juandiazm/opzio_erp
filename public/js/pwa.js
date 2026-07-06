/**
 * PWA - Progressive Web App
 * Registro del Service Worker y funcionalidades PWA
 */

// Registrar Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js', { scope: '/' })
            .then((registration) => {
                console.log('✓ Service Worker registrado con éxito:', registration.scope);
                
                // Verificar actualizaciones cada hora
                setInterval(() => {
                    registration.update();
                }, 60 * 60 * 1000);
            })
            .catch((error) => {
                console.error('✗ Error al registrar Service Worker:', error);
            });
    });

    // Detectar cuando hay una nueva versión disponible
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        console.log('Nueva versión de la aplicación disponible');
        //showUpdateNotification();
    });
}

// Mostrar notificación de actualización
function showUpdateNotification() {
    if (confirm('Hay una nueva versión disponible. ¿Deseas actualizar ahora?')) {
        window.location.reload();
    }
}

// Detectar cuando la app se instala
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    console.log('PWA puede ser instalada');
    // Prevenir que Chrome 67 y anteriores muestren el prompt automáticamente
    e.preventDefault();
    // Guardar el evento para poder dispararlo después
    deferredPrompt = e;
    // Mostrar botón de instalación personalizado
    showInstallButton();
});

// Función para mostrar botón de instalación
function showInstallButton() {
    // Verificar si el usuario ya rechazó la instalación
    if (localStorage.getItem('pwa-install-dismissed') === 'true') {
        return;
    }

    const installButton = document.getElementById('pwa-install-button');
    const closeButton = document.getElementById('pwa-install-close');
    
    if (installButton) {
        installButton.style.display = 'flex';
        
        // Click en el botón de instalar
        installButton.addEventListener('click', async (e) => {
            if (e.target.id === 'pwa-install-close') {
                return; // Ignora clicks en la X
            }
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`Resultado de instalación: ${outcome}`);
                deferredPrompt = null;
                installButton.style.display = 'none';
            }
        });
    }

    // Click en la X para cerrar
    if (closeButton) {
        closeButton.addEventListener('click', (e) => {
            e.stopPropagation();
            installButton.style.display = 'none';
            localStorage.setItem('pwa-install-dismissed', 'true');
            console.log('Usuario rechazó la instalación de PWA');
        });
    }
}

// Detectar cuando la app se ha instalado
window.addEventListener('appinstalled', () => {
    console.log('✓ PWA instalada exitosamente');
    deferredPrompt = null;
    // Ocultar botón de instalación si existe
    const installButton = document.getElementById('pwa-install-button');
    if (installButton) {
        installButton.style.display = 'none';
    }
});

// Detectar estado de conexión
window.addEventListener('online', () => {
    console.log('✓ Conexión restaurada');
    showConnectionStatus('Conexión restaurada', 'success');
});

window.addEventListener('offline', () => {
    console.log('✗ Sin conexión a internet');
    showConnectionStatus('Sin conexión a internet', 'warning');
});

// Mostrar estado de conexión
function showConnectionStatus(message, type) {
    // Buscar o crear elemento de notificación
    let statusElement = document.getElementById('connection-status');
    if (!statusElement) {
        statusElement = document.createElement('div');
        statusElement.id = 'connection-status';
        statusElement.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            font-weight: 600;
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        document.body.appendChild(statusElement);
    }

    // Aplicar estilos según el tipo
    if (type === 'success') {
        statusElement.style.background = '#28a745';
        statusElement.style.color = 'white';
    } else if (type === 'warning') {
        statusElement.style.background = '#ffc107';
        statusElement.style.color = '#000';
    }

    statusElement.textContent = message;
    statusElement.style.display = 'block';

    // Ocultar después de 3 segundos
    setTimeout(() => {
        statusElement.style.display = 'none';
    }, 3000);
}

// Animación CSS
if (!document.getElementById('pwa-animations')) {
    const style = document.createElement('style');
    style.id = 'pwa-animations';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        #pwa-install-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #0066cc;
            color: white;
            border: none;
            padding: 15px 20px 15px 20px;
            padding-right: 48px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
            z-index: 9999;
            display: none;
            animation: slideIn 0.3s ease-out;
            font-family: 'Rajdhani', sans-serif;
        }
        
        #pwa-install-button:hover {
            background: #0052a3;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 102, 204, 0.4);
            transition: all 0.3s ease;
        }
        
        #pwa-install-close {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            line-height: 1;
            padding: 0;
            font-weight: bold;
            transition: background 0.2s ease;
        }
        
        #pwa-install-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-50%) scale(1.1);
        }
        
        @media (max-width: 768px) {
            #pwa-install-button {
                bottom: 10px;
                right: 10px;
                padding: 12px 40px 12px 24px;
                font-size: 14px;
            }
            
            #pwa-install-close {
                right: 10px;
                width: 20px;
                height: 20px;
                font-size: 16px;
            }
        }
    `;
    document.head.appendChild(style);
}

// Crear botón de instalación si no existe
if (!document.getElementById('pwa-install-button')) {
    const installButton = document.createElement('button');
    installButton.id = 'pwa-install-button';
    installButton.innerHTML = '📱 Instalar App<span id="pwa-install-close">×</span>';
    document.body.appendChild(installButton);
}

console.log('PWA inicializada correctamente');
