import Chart from 'chart.js/auto';

window.Chart = Chart;

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';

/**
 * QR decoder for the customer "Pindai QR" scanner, loaded on demand so it is
 * only downloaded when a customer actually opens the camera.
 */
window.loadJsQR = () => import('jsqr').then((module) => module.default);
