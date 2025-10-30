import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Attach CSRF token for axios requests (useful for non-Inertia calls or fallbacks)
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
	window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
}
