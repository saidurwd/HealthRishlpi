import * as bootstrap from 'bootstrap';
import 'admin-lte';
import { OverlayScrollbars } from 'overlayscrollbars';
import Grid from './grid';
import './searchable';
import './chained';
import './line-form';
import './invoice';
import './prescription';
import './dashboard';

window.bootstrap = bootstrap;
window.Grid = Grid;

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar-wrapper');

    if (sidebar && window.innerWidth > 992) {
        OverlayScrollbars(sidebar, {
            scrollbars: { theme: 'os-theme-light', autoHide: 'leave', clickScroll: true },
        });
    }
});
