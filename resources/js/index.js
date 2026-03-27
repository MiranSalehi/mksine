// MKS CMS JavaScript
import Sortable from 'sortablejs';
import { setupPageBuilderSortable } from './page-builder-sortable.js';
import { setupPageBuilderGlobals } from './page-builder.js';

if (typeof window !== 'undefined') {
    window.Sortable = Sortable;
}

setupPageBuilderSortable();
setupPageBuilderGlobals();
