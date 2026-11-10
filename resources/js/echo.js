import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

try {
    const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
    const reverbHost = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
    const reverbPort = import.meta.env.VITE_REVERB_PORT ?? 8080;
    const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';

    if (reverbKey) {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: reverbHost,
            wsPort: reverbPort,
            wssPort: reverbPort,
            forceTLS: reverbScheme === 'https',
            enabledTransports: ['ws', 'wss'],
            authorizer: (channel, options) => {
                return {
                    authorize: (socketId, callback) => {
                        const isTicketChannel = channel.name.startsWith('private-ticket.');
                        const authUrl = isTicketChannel ? '/broadcasting/auth/ticket' : '/broadcasting/auth';
                        const payload = {
                            socket_id: socketId,
                            channel_name: channel.name,
                        };

                        if (isTicketChannel && window.__ANTREIN_TICKET_TOKEN__) {
                            payload.token = window.__ANTREIN_TICKET_TOKEN__;
                        }

                        fetch(authUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                            },
                            body: JSON.stringify(payload),
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`Broadcast authorization failed: HTTP ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            callback(null, data);
                        })
                        .catch(error => {
                            callback(error);
                        });
                    }
                };
            },
        });
    }
} catch (error) {
    console.warn('AntreIn Echo initialization skipped:', error);
}
