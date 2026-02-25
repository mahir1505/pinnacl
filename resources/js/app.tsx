import '../css/app.css';
import { createRoot } from 'react-dom/client';
import Root from './Root';

const container = document.getElementById('app');
if (container) {
    createRoot(container).render(<Root />);
}
