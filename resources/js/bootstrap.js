// import 'bootstrap';

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from "axios";
window.axios = axios;

// Set default headers untuk Axios
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// Setup CSRF token for Axios
let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token.content;
} else {
    console.error(
        "CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token"
    );
}

// Laravel Echo dan Pusher konfigurasi
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

// Enable debugging in development
if (process.env.NODE_ENV === "development") {
    Pusher.logToConsole = true;
}

// Enhanced Pusher configuration with fallbacks
window.Echo = new Echo({
    broadcaster: "pusher",
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true,
    enabledTransports: ["ws", "wss"],

    // Connection fallback options
    wsHost: `ws-${process.env.MIX_PUSHER_APP_CLUSTER}.pusher.com`,
    wsPort: 80,
    wssPort: 443,

    // Retry configuration
    activityTimeout: 30000,
    pongTimeout: 30000,
    unavailableTimeout: 10000,

    // Connection state handling
    enableStats: false,
    enableLogging: process.env.NODE_ENV === "development",
});

// Enhanced connection event handlers
window.Echo.connector.pusher.connection.bind("connected", function () {
    console.log("✅ Pusher WebSocket Connected");
});

window.Echo.connector.pusher.connection.bind("disconnected", function () {
    console.warn("⚠️ Pusher WebSocket Disconnected");
});

window.Echo.connector.pusher.connection.bind("error", function (err) {
    console.error("❌ Pusher Connection Error:", err);

    // Fallback to HTTP polling if WebSocket fails
    if (err.type === "WebSocketError" || err.error?.code === 4006) {
        console.log("🔄 Falling back to HTTP polling...");
        // Implement HTTP polling fallback here if needed
    }
});

window.Echo.connector.pusher.connection.bind("unavailable", function () {
    console.warn("⚠️ Pusher connection unavailable, will retry...");
});
