import { createRoot } from 'react-dom/client'
import Dashboard from './Dashboard'

const el = document.getElementById('ejecutivo-react')
if (el) createRoot(el).render(<Dashboard data={window.__EJECUTIVO_DATA__} />)
