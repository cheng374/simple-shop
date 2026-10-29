const state = { admin: null, products: [], orders: [], view: 'products' };
const $ = (selector) => document.querySelector(selector);
const money = (value) => `NT$${Number(value).toLocaleString('zh-TW')}`;
const escapeHTML = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[character]);

async function api(action, options = {}) {
  const response = await fetch(`../api.php?action=${action}`, { headers: { 'Content-Type': 'application/json', ...(options.headers || {}) }, ...options });
  const data = await response.json().catch(() => ({ ok: false, message: '伺服器回應格式錯誤。' }));
  if (!response.ok || data.ok === false) throw new Error(data.message || '操作失敗。');
  return data;
}

function toast(message, type = 'success') {
  const element = $('#toast'); element.textContent = message; element.className = `toast show ${type}`;
  window.setTimeout(() => element.classList.remove('show'), 2800);
}

function renderStats(stats) {
  $('#stat-products').textContent = stats.products;
  $('#stat-users').textContent = stats.users;
  $('#stat-orders').textContent = stats.orders;
  $('#stat-revenue').textContent = money(stats.revenue);
  $('#stat-pending').textContent = stats.pending;
}

function renderProducts() {
  const table = $('#products-table');
  if (!state.products.length) { table.innerHTML = '<tr><td colspan="5" class="empty">目前沒有商品。</td></tr>'; return; }
  table.innerHTML = state.products.map((product) => `<tr><td><div class="product-cell"><span class="product-icon" style="--accent:${escapeHTML(product.accent)}">${escapeHTML(product.emoji)}</span><div><strong>${escapeHTML(product.name)}</strong><small>${escapeHTML(product.description)}</small></div></div></td><td>${escapeHTML(product.category)}</td><td>${money(product.price)}</td><td><span class="stock ${product.stock === 0 ? 'out' : ''}">${product.stock}</span></td><td><div class="row-actions"><button class="edit-button" data-edit="${product.id}">編輯</button><button class="delete-button" data-delete="${product.id}">刪除</button></div></td></tr>`).join('');
  table.querySelectorAll('[data-edit]').forEach((button) => button.addEventListener('click', () => openProductModal(Number(button.dataset.edit))));
  table.querySelectorAll('[data-delete]').forEach((button) => button.addEventListener('click', () => deleteProduct(Number(button.dataset.delete))));
}

function renderOrders() {
  const table = $('#orders-table');
  if (!state.orders.length) { table.innerHTML = '<tr><td colspan="5" class="empty">目前還沒有訂單。</td></tr>'; return; }
  const statuses = ['待處理', '已確認', '配送中', '已完成', '已取消'];
  table.innerHTML = state.orders.map((order) => `<tr><td><strong>#${order.id}</strong><small>${escapeHTML(order.created_at)}</small></td><td><strong>${escapeHTML(order.customer_name)}</strong><small>${escapeHTML(order.customer_email)}<br>${escapeHTML(order.recipient)}｜${escapeHTML(order.address)}</small></td><td class="items-cell">${escapeHTML(order.items || '商品明細')}</td><td><strong>${money(order.total)}</strong></td><td><select class="status-select status-${escapeHTML(order.status)}" data-status="${order.id}">${statuses.map((status) => `<option value="${status}" ${status === order.status ? 'selected' : ''}>${status}</option>`).join('')}</select></td></tr>`).join('');
  table.querySelectorAll('[data-status]').forEach((select) => select.addEventListener('change', () => updateOrderStatus(Number(select.dataset.status), select.value)));
}

async function refreshDashboard() {
  const [stats, products, orders] = await Promise.all([api('admin_stats'), api('admin_products'), api('admin_orders')]);
  state.products = products.products; state.orders = orders.orders; renderStats(stats.stats); renderProducts(); renderOrders();
}

function showDashboard() {
  $('#login-screen').hidden = true; $('#dashboard').hidden = false; $('#admin-name').textContent = `${state.admin.name}｜${state.admin.email}`; refreshDashboard().catch((error) => toast(error.message, 'error'));
}

function showLogin() { $('#login-screen').hidden = false; $('#dashboard').hidden = true; }

function openProductModal(id = 0) {
  const form = $('#product-form'); form.reset(); form.elements.id.value = '';
  if (id) {
    const product = state.products.find((item) => item.id === id); if (!product) return;
    Object.entries(product).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value; });
    $('#product-modal-title').textContent = '編輯商品';
  } else {
    form.elements.emoji.value = '🛍️'; form.elements.accent.value = '#dce8dc'; form.elements.price.value = 500; form.elements.stock.value = 20; $('#product-modal-title').textContent = '新增商品';
  }
  $('#modal-backdrop').hidden = false; $('#product-modal').hidden = false; document.body.classList.add('no-scroll');
}

function closeProductModal() { $('#product-modal').hidden = true; $('#modal-backdrop').hidden = true; document.body.classList.remove('no-scroll'); }

async function deleteProduct(id) {
  const product = state.products.find((item) => item.id === id); if (!product || !window.confirm(`確定刪除「${product.name}」？`)) return;
  try { const data = await api('admin_product_delete', { method: 'POST', body: JSON.stringify({ id }) }); toast(data.message); await refreshDashboard(); } catch (error) { toast(error.message, 'error'); }
}

async function updateOrderStatus(id, status) {
  try { const data = await api('admin_order_status', { method: 'POST', body: JSON.stringify({ id, status }) }); toast(data.message); await refreshDashboard(); } catch (error) { toast(error.message, 'error'); }
}

document.querySelectorAll('[data-view]').forEach((button) => button.addEventListener('click', () => {
  state.view = button.dataset.view; document.querySelectorAll('[data-view]').forEach((item) => item.classList.toggle('active', item === button)); $('#products-view').hidden = state.view !== 'products'; $('#orders-view').hidden = state.view !== 'orders';
}));

$('#login-form').addEventListener('submit', async (event) => { event.preventDefault(); const payload = Object.fromEntries(new FormData(event.currentTarget).entries()); try { const data = await api('admin_login', { method: 'POST', body: JSON.stringify(payload) }); state.admin = data.admin; showDashboard(); toast(data.message); } catch (error) { toast(error.message, 'error'); } });
$('#logout-button').addEventListener('click', async () => { try { await api('admin_logout', { method: 'POST' }); state.admin = null; showLogin(); toast('已登出後台。', 'info'); } catch (error) { toast(error.message, 'error'); } });
$('#add-product').addEventListener('click', () => openProductModal()); $('#close-product-modal').addEventListener('click', closeProductModal); $('#cancel-product').addEventListener('click', closeProductModal); $('#modal-backdrop').addEventListener('click', closeProductModal);
$('#product-form').addEventListener('submit', async (event) => { event.preventDefault(); const form = event.currentTarget; const payload = Object.fromEntries(new FormData(form).entries()); try { const data = await api('admin_product_save', { method: 'POST', body: JSON.stringify(payload) }); closeProductModal(); toast(data.message); await refreshDashboard(); } catch (error) { toast(error.message, 'error'); } });

api('admin_me').then((data) => { if (data.admin) { state.admin = data.admin; showDashboard(); } else showLogin(); }).catch(() => showLogin());

