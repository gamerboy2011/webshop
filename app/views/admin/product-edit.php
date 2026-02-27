<div class="max-w-4xl">
    
    <?php if (!empty($product)): ?>
    <!-- KÉPEK KEZELÉSE -->
    <div class="bg-white rounded-xl shadow-sm p-8 mb-6">
        <h2 class="text-lg font-semibold mb-4">
            <i class="las la-images mr-2"></i>Termék képek
        </h2>
        
        <!-- Kép feltöltés -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Új kép feltöltése</label>
            <div class="flex items-center gap-4">
                <input type="file" id="imageUpload" accept="image/*" multiple
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-900 file:text-white hover:file:bg-gray-700">
                <button type="button" id="uploadBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    <i class="las la-upload mr-1"></i>Feltöltés
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-1">Max 5MB, JPG/PNG/WebP formátum</p>
        </div>
        
        <!-- Kép galéria (drag & drop) -->
        <div id="imageGallery" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <?php foreach ($productImages as $img): ?>
            <div class="image-item relative group bg-gray-100 rounded-lg overflow-hidden aspect-square" 
                 data-id="<?= $img['product_img_id'] ?>">
                <img src="/webshop/<?= htmlspecialchars($img['src']) ?>" 
                     class="w-full h-full object-contain cursor-move" draggable="true">
                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                    <span class="text-white text-sm font-medium">#<?= $img['position'] ?></span>
                    <button type="button" onclick="deleteImage(<?= $img['product_img_id'] ?>)" 
                            class="w-8 h-8 bg-red-500 text-white rounded-full hover:bg-red-600 transition">
                        <i class="las la-trash"></i>
                    </button>
                </div>
                <div class="absolute top-1 left-1 w-6 h-6 bg-black/70 text-white text-xs rounded flex items-center justify-center">
                    <?= $img['position'] ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($productImages)): ?>
            <p class="col-span-4 text-gray-400 text-center py-8" id="noImagesMsg">
                <i class="las la-image text-4xl mb-2"></i><br>
                Még nincs kép feltöltve
            </p>
            <?php endif; ?>
        </div>
        
        <p class="text-xs text-gray-500 mt-3">
            <i class="las la-info-circle"></i> Húzd át a képeket a sorrend módosításához. Az első kép lesz a főkép.
        </p>
    </div>
    <?php endif; ?>
    
    <form method="post" action="/webshop/yw-admin" class="bg-white rounded-xl shadow-sm p-8">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_product">
        <?php if (!empty($product)): ?>
            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Név -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Termék neve *</label>
                <input type="text" name="name" required
                       value="<?= htmlspecialchars($product['name'] ?? '') ?>"
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none">
            </div>
            
            <!-- Leírás -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Leírás</label>
                <textarea name="description" rows="4"
                          class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>
            
            <!-- Ár -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ár (Ft) *</label>
                <input type="number" name="price" required min="0"
                       value="<?= htmlspecialchars($product['price'] ?? '') ?>"
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none">
            </div>
            
            <!-- Márka -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Márka *</label>
                <select name="vendor_id" required
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none">
                    <option value="">Válassz...</option>
                    <?php foreach ($vendors as $v): ?>
                        <option value="<?= $v['vendor_id'] ?>" <?= ($product['vendor_id'] ?? '') == $v['vendor_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Szín -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Szín *</label>
                <select name="color_id" required
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none">
                    <option value="">Válassz...</option>
                    <?php foreach ($colors as $c): ?>
                        <option value="<?= $c['color_id'] ?>" <?= ($product['color_id'] ?? '') == $c['color_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Nem -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nem *</label>
                <select name="gender_id" required
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none">
                    <option value="">Válassz...</option>
                    <?php foreach ($genders as $g): ?>
                        <option value="<?= $g['gender_id'] ?>" <?= ($product['gender_id'] ?? '') == $g['gender_id'] ? 'selected' : '' ?>>
                            <?= $g['gender'] === 'm' ? 'Férfi' : ($g['gender'] === 'f' ? 'Női' : 'Uniszex') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Kategória -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kategória *</label>
                <select name="subtype_id" required
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none">
                    <option value="">Válassz...</option>
                    <?php 
                    $currentType = '';
                    foreach ($subtypes as $st): 
                        if ($currentType !== $st['type_name']):
                            if ($currentType !== '') echo '</optgroup>';
                            $currentType = $st['type_name'];
                            echo '<optgroup label="' . htmlspecialchars($currentType) . '">';
                        endif;
                    ?>
                        <option value="<?= $st['product_subtype_id'] ?>" <?= ($product['subtype_id'] ?? '') == $st['product_subtype_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($st['name']) ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if ($currentType !== '') echo '</optgroup>'; ?>
                </select>
            </div>
            
            <!-- Akciós -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Akció</label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_sale" value="1" 
                           <?= !empty($product['is_sale']) ? 'checked' : '' ?>
                           class="w-5 h-5 rounded border-gray-300 text-red-500 focus:ring-red-500">
                    <span class="text-sm text-gray-600">Akciós termék (-20%)</span>
                </label>
            </div>
            
            <!-- Aktív -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Státusz</label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" 
                           <?= !isset($product) || !empty($product['is_active']) ? 'checked' : '' ?>
                           class="w-5 h-5 rounded border-gray-300 text-green-500 focus:ring-green-500">
                    <span class="text-sm text-gray-600">Aktív (látható a webshopban)</span>
                </label>
            </div>
            
        </div>
        
        <!-- Gombok -->
        <div class="flex items-center gap-4 mt-8 pt-6 border-t">
            <button type="submit" 
                    class="bg-gray-900 text-white px-8 py-3 rounded-lg font-medium hover:bg-gray-800 transition">
                <i class="las la-save mr-2"></i>
                Mentés
            </button>
            <a href="/webshop/yw-admin/products" 
               class="px-8 py-3 border rounded-lg text-gray-600 hover:bg-gray-50 transition">
                Mégse
            </a>
        </div>
        
    </form>
</div>

<?php if (!empty($product)): ?>
<script>
const productId = <?= $product['product_id'] ?>;
const csrfToken = '<?= generate_csrf_token() ?>';

// Kép feltöltés
document.getElementById('uploadBtn')?.addEventListener('click', async function() {
    const input = document.getElementById('imageUpload');
    const files = input.files;
    
    if (files.length === 0) {
        alert('Válassz ki legalább egy képet!');
        return;
    }
    
    this.disabled = true;
    this.innerHTML = '<i class="las la-spinner la-spin mr-1"></i>Feltöltés...';
    
    for (const file of files) {
        const formData = new FormData();
        formData.append('action', 'upload_product_image');
        formData.append('csrf_token', csrfToken);
        formData.append('product_id', productId);
        formData.append('image', file);
        
        try {
            const res = await fetch('/webshop/yw-admin', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                addImageToGallery(data.image);
            } else {
                alert('Hiba: ' + (data.error || 'Ismeretlen hiba'));
            }
        } catch (err) {
            console.error(err);
            alert('Feltöltési hiba!');
        }
    }
    
    input.value = '';
    this.disabled = false;
    this.innerHTML = '<i class="las la-upload mr-1"></i>Feltöltés';
});

// Kép hozzáadása a galériához
function addImageToGallery(img) {
    document.getElementById('noImagesMsg')?.remove();
    
    const gallery = document.getElementById('imageGallery');
    const div = document.createElement('div');
    div.className = 'image-item relative group bg-gray-100 rounded-lg overflow-hidden aspect-square';
    div.dataset.id = img.product_img_id;
    div.innerHTML = `
        <img src="/webshop/${img.src}" class="w-full h-full object-contain cursor-move" draggable="true">
        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
            <span class="text-white text-sm font-medium">#${img.position}</span>
            <button type="button" onclick="deleteImage(${img.product_img_id})" 
                    class="w-8 h-8 bg-red-500 text-white rounded-full hover:bg-red-600 transition">
                <i class="las la-trash"></i>
            </button>
        </div>
        <div class="absolute top-1 left-1 w-6 h-6 bg-black/70 text-white text-xs rounded flex items-center justify-center">
            ${img.position}
        </div>
    `;
    gallery.appendChild(div);
    initDragAndDrop();
}

// Kép törlése
async function deleteImage(imageId) {
    if (!confirm('Biztosan törlöd ezt a képet?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_product_image');
    formData.append('csrf_token', csrfToken);
    formData.append('image_id', imageId);
    
    try {
        const res = await fetch('/webshop/yw-admin', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            document.querySelector(`.image-item[data-id="${imageId}"]`)?.remove();
            updatePositionNumbers();
        } else {
            alert('Törlés sikertelen!');
        }
    } catch (err) {
        console.error(err);
        alert('Hiba történt!');
    }
}

// Drag & Drop sorrend
let draggedItem = null;

function initDragAndDrop() {
    const items = document.querySelectorAll('.image-item');
    
    items.forEach(item => {
        item.addEventListener('dragstart', function(e) {
            draggedItem = this;
            this.classList.add('opacity-50');
        });
        
        item.addEventListener('dragend', function() {
            this.classList.remove('opacity-50');
            draggedItem = null;
        });
        
        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('ring-2', 'ring-blue-500');
        });
        
        item.addEventListener('dragleave', function() {
            this.classList.remove('ring-2', 'ring-blue-500');
        });
        
        item.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('ring-2', 'ring-blue-500');
            
            if (draggedItem !== this) {
                const gallery = document.getElementById('imageGallery');
                const items = [...gallery.querySelectorAll('.image-item')];
                const draggedIndex = items.indexOf(draggedItem);
                const targetIndex = items.indexOf(this);
                
                if (draggedIndex < targetIndex) {
                    this.parentNode.insertBefore(draggedItem, this.nextSibling);
                } else {
                    this.parentNode.insertBefore(draggedItem, this);
                }
                
                saveOrder();
            }
        });
    });
}

// Sorrend mentése
async function saveOrder() {
    const items = document.querySelectorAll('.image-item');
    const imageIds = [...items].map(item => item.dataset.id);
    
    const formData = new FormData();
    formData.append('action', 'reorder_product_images');
    formData.append('csrf_token', csrfToken);
    imageIds.forEach(id => formData.append('image_ids[]', id));
    
    try {
        const res = await fetch('/webshop/yw-admin', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            updatePositionNumbers();
        }
    } catch (err) {
        console.error(err);
    }
}

// Pozíció számok frissítése
function updatePositionNumbers() {
    const items = document.querySelectorAll('.image-item');
    items.forEach((item, index) => {
        const badge = item.querySelector('.absolute.top-1');
        const span = item.querySelector('span.text-white');
        if (badge) badge.textContent = index + 1;
        if (span) span.textContent = '#' + (index + 1);
    });
}

// Init
initDragAndDrop();
</script>
<?php endif; ?>
