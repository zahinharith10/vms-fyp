import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configure Laravel Echo to connect to Reverb (WebSocket server) dynamically from meta tags
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

const reverbKey = document.querySelector('meta[name="reverb-key"]')?.getAttribute('content');
const reverbHost = document.querySelector('meta[name="reverb-host"]')?.getAttribute('content');
const reverbPort = document.querySelector('meta[name="reverb-port"]')?.getAttribute('content');
const reverbScheme = document.querySelector('meta[name="reverb-scheme"]')?.getAttribute('content');

if (reverbKey) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost || window.location.hostname,
        wsPort: reverbPort || 8080,
        wssPort: reverbPort || 8080,
        forceTLS: reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
    });
} else {
    console.log('Real-time updates via WebSockets are disabled (Reverb not configured).');
}
