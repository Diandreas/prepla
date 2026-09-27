import { createRoot } from 'react-dom/client';
import '../css/app.css';
import { OfflineApp } from './offline/offline-app';

/**
 * Entry point of the offline space. It deliberately boots without Inertia: the page
 * must open from the cache, with no server round-trip and no serialized session.
 */
const root = document.getElementById('offline-root');
if (root) {
    createRoot(root).render(<OfflineApp />);
}
