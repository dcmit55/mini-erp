import axios from 'axios';

const client = axios.create({
    baseURL: '/qc/api',
    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
    withCredentials: true,
});

// Attach Laravel CSRF token from meta tag
client.interceptors.request.use((config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) config.headers['X-CSRF-TOKEN'] = token;
    return config;
});

// Unwrap .data automatically; surface error message
client.interceptors.response.use(
    (res) => res,
    (err) => {
        const message = err.response?.data?.message ?? err.message ?? 'Request failed';
        return Promise.reject(new Error(message));
    }
);

export default client;
