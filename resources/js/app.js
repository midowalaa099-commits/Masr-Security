

import Alpine from 'alpinejs';
import { productCatalog, packageEditor } from './admin-product-catalog';

window.Alpine = Alpine;
Alpine.data('productCatalog', productCatalog);
Alpine.data('packageEditor', packageEditor);

Alpine.start();
