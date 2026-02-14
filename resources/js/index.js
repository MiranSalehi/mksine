// MKS CMS JavaScript
import Sortable from 'sortablejs';
import { setupPageBuilderSortable } from './page-builder-sortable.js';

if (typeof window !== 'undefined') {
    window.Sortable = Sortable;
}

setupPageBuilderSortable();
