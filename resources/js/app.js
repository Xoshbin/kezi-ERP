import './bootstrap';

// Conditionally load documentation functionality
if (window.location.pathname.startsWith('/docs')) {
    import('./docs.js');
}
