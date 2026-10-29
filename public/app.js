const state = { user: null, products: [], category: '全部', query: '', authMode: 'login', cart: null };
const $ = (selector) => document.querySelector(selector);
const money = (number) => `NT$${Number(number).toLocaleString('zh-TW')}`;

async function api(action, options = {}) {
  const response = await fetch(`api.php?action=${action}`, {
    headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
    ...options,
  });
  const data = await response.json().catch(() => ({ ok: false, message: '伺服器回應格式錯誤。' }));
  if (!response.ok || data.ok === false) throw new Error(data.message || '操作失敗。');
  return data;
}

function showToast(message, type = 'success') {
  const toast = $('#toast');
  toast.textContent = message;
  toast.className = `toast show ${type}`;
  window.setTimeout(() => toast.classList.remove('show'), 2600);
}

function openModal(id) {
  $('#modal-backdrop').hidden = false;
  document.body.classList.add('no-scroll');
  const modal = $(`#${id}`); modal.hidden = false;
  window.setTimeout(() => modal.classList.add('visible'), 10);
}

function closeModals() {
  document.querySelectorAll('.modal').forEach((modal) => { modal.classList.remove('visible'); window.setTimeout(() => { modal.hidden = true; }, 220); });
  $('#modal-backdrop').hidden = true; document.body.classList.remove('no-scroll');
}

function renderProducts() {
  const grid = $('#product-grid');
  const filtered = state.products.filter((product) => {
    const categoryMatch = state.category === '全部' || product.category === state.category;
    const query = state.query.toLowerCase();
    return categoryMatch && (!query || `${product.name}${product.description}`.toLowerCase().includes(query));
  });
  if (!filtered.length) { grid.innerHTML = '<div class="empty-products">找不到符合條件的商品，換個關鍵字試試看。</div>'; return; }
  grid.innerHTML = filtered.map((product, index) => `<article class="product-card" style="--delay:${index * 60}ms"><div class="product-art" style="--accent:${product.accent}"><span>${product.emoji}</span><button class="quick-add" data-add="${product.id}" aria-label="將 ${product.name} 加入購物袋">＋</button></div><div class="product-info"><div><p class="product-category">${product.category}</p><h3>${product.name}</h3></div><strong>${money(product.price)}</strong></div><p class="product-description">${product.description}</p><button class="add-button" data-add="${product.id}">加入購物袋 <span>＋</span></button></article>`).join('');
  grid.querySelectorAll('[data-add]').forEach((button) => button.addEventListener('click', () => addToCart(Number(button.dataset.add))));
}

function renderAccount() {
  $('#account-label').textContent = state.user ? `嗨，${state.user.name}` : '登入 / 註冊';
}

async function openMemberCenter() {
  if (!state.user) { openModal('account-modal'); return; }
  $('#member-greeting').textContent = `${state.user.name}｜${state.user.email}`;
  $('#order-list').innerHTML = '<p class="loading">正在載入訂單…</p>';
  openModal('member-modal');
  try {
    const data = await api('orders');
    $('#order-list').innerHTML = data.orders.length ? data.orders.map((order) => `<div class="order-card"><div><strong>訂單 #${order.id}</strong><span>${order.created_at}</span></div><p>${order.items || '商品明細'}</p><div><span class="order-status">${order.status}</span><strong>${money(order.total)}</strong></div></div>`).join('') : '<div class="empty-order">目前還沒有訂單，去挑一點喜歡的東西吧。</div>';
  } catch (error) {
    $('#order-list').innerHTML = `<p class="empty-order">${error.message}</p>`;
  }
}

function renderCart() {
  const count = state.cart?.count || 0;
  $('#cart-count').textContent = count;
  const body = $('#cart-body'); const footer = $('#cart-footer');
  if (!state.user) { body.innerHTML = '<div class="empty-state"><span>♡</span><p>登入後，就可以把喜歡的商品放進購物袋。</p><button class="text-button" id="cart-login">前往登入 →</button></div>'; footer.hidden = true; $('#cart-login').addEventListener('click', () => { closeCart(); openModal('account-modal'); }); return; }
  if (!state.cart || !state.cart.items.length) { body.innerHTML = '<div class="empty-state"><span>✦</span><p>購物袋還是空的。<br>去逛逛，把喜歡的帶回家吧。</p><a class="text-button" href="#products" id="continue-shopping">去逛逛 →</a></div>'; footer.hidden = true; return; }
  body.innerHTML = state.cart.items.map((item) => `<div class="cart-item"><div class="cart-item-art" style="--accent:${item.accent}">${item.emoji}</div><div class="cart-item-details"><h3>${item.name}</h3><strong>${money(item.price)}</strong><div class="quantity"><button data-update="${item.item_id}" data-quantity="${item.quantity - 1}">−</button><span>${item.quantity}</span><button data-update="${item.item_id}" data-quantity="${item.quantity + 1}">＋</button></div></div><button class="remove-item" data-update="${item.item_id}" data-quantity="0" aria-label="移除 ${item.name}">×</button></div>`).join('');
  body.querySelectorAll('[data-update]').forEach((button) => button.addEventListener('click', () => updateCart(Number(button.dataset.update), Number(button.dataset.quantity))));
  footer.hidden = false; $('#cart-total').textContent = money(state.cart.total);
}

async function addToCart(productId) {
  if (!state.user) { showToast('請先登入，再把商品放進購物袋。', 'info'); openModal('account-modal'); return; }
  try { const data = await api('cart_add', { method: 'POST', body: JSON.stringify({ product_id: productId }) }); state.cart = data.cart; renderCart(); openCart(); showToast(data.message); } catch (error) { showToast(error.message, 'error'); }
}

async function updateCart(itemId, quantity) {
  try { const data = await api('cart_update', { method: 'POST', body: JSON.stringify({ item_id: itemId, quantity }) }); state.cart = data.cart; renderCart(); } catch (error) { showToast(error.message, 'error'); }
}

function openCart() { $('#cart-drawer').classList.add('open'); $('#cart-drawer').setAttribute('aria-hidden', 'false'); $('#modal-backdrop').hidden = false; document.body.classList.add('no-scroll'); }
function closeCart() { $('#cart-drawer').classList.remove('open'); $('#cart-drawer').setAttribute('aria-hidden', 'true'); if (document.querySelector('.modal[hidden]') || !document.querySelector('.modal.visible')) { $('#modal-backdrop').hidden = true; document.body.classList.remove('no-scroll'); } }

function setAuthMode(mode) {
  state.authMode = mode; document.querySelectorAll('[data-auth-tab]').forEach((tab) => tab.classList.toggle('active', tab.dataset.authTab === mode));
  const registerOnly = document.querySelector('.register-only'); registerOnly.hidden = mode !== 'register'; registerOnly.querySelector('input').required = mode === 'register';
  $('#account-title').textContent = mode === 'login' ? '登入日常選物所' : '加入日常選物所';
  $('#account-subtitle').textContent = mode === 'login' ? '登入後可以查看訂單與使用購物袋。' : '註冊一個帳號，開始收藏你的日常。';
  $('#auth-submit').innerHTML = mode === 'login' ? '登入 <span>→</span>' : '建立帳號 <span>→</span>';
  $('#auth-form input[name="password"]').autocomplete = mode === 'login' ? 'current-password' : 'new-password';
}

async function loadProducts() { const data = await api('products'); state.products = data.products; renderProducts(); }
async function loadUser() { const data = await api('me'); state.user = data.user; renderAccount(); renderCart(); if (state.user) { try { state.cart = (await api('cart')).cart; renderCart(); } catch { /* not logged in */ } } }

$('#category-tabs').addEventListener('click', (event) => { const button = event.target.closest('[data-category]'); if (!button) return; state.category = button.dataset.category; document.querySelectorAll('[data-category]').forEach((item) => item.classList.toggle('active', item === button)); renderProducts(); });
$('#search-button').addEventListener('click', () => { $('#search-wrap').classList.toggle('active'); $('#search-input').focus(); });
$('#search-input').addEventListener('input', (event) => { state.query = event.target.value; renderProducts(); });
$('#clear-search').addEventListener('click', () => { $('#search-input').value = ''; state.query = ''; renderProducts(); });
$('#account-button').addEventListener('click', openMemberCenter);
$('#mobile-account').addEventListener('click', openMemberCenter);
$('#cart-button').addEventListener('click', openCart); $('#mobile-cart').addEventListener('click', openCart); $('#close-cart').addEventListener('click', closeCart);
$('#modal-backdrop').addEventListener('click', () => { closeModals(); closeCart(); });
document.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', closeModals));
document.querySelectorAll('[data-auth-tab]').forEach((tab) => tab.addEventListener('click', () => setAuthMode(tab.dataset.authTab)));

$('#auth-form').addEventListener('submit', async (event) => { event.preventDefault(); const form = new FormData(event.currentTarget); const payload = Object.fromEntries(form.entries()); try { const data = await api(state.authMode, { method: 'POST', body: JSON.stringify(payload) }); state.user = data.user; state.cart = (await api('cart')).cart; renderAccount(); renderCart(); closeModals(); event.currentTarget.reset(); setAuthMode('login'); showToast(data.message); } catch (error) { showToast(error.message, 'error'); } });
$('#checkout-button').addEventListener('click', () => { closeCart(); openModal('checkout-modal'); });
$('#checkout-form').addEventListener('submit', async (event) => { event.preventDefault(); const payload = Object.fromEntries(new FormData(event.currentTarget).entries()); try { const data = await api('checkout', { method: 'POST', body: JSON.stringify(payload) }); state.cart = { items: [], count: 0, total: 0 }; renderCart(); closeModals(); event.currentTarget.reset(); showToast(`${data.message} 訂單編號：${data.order_id}`); } catch (error) { showToast(error.message, 'error'); } });
$('#logout-button').addEventListener('click', async () => { try { await api('logout', { method: 'POST' }); state.user = null; state.cart = null; renderAccount(); renderCart(); closeModals(); showToast('已安全登出。', 'info'); } catch (error) { showToast(error.message, 'error'); } });
$('#newsletter-form').addEventListener('submit', (event) => { event.preventDefault(); event.currentTarget.reset(); showToast('訂閱成功，期待在信箱見到你。'); });

loadProducts().catch(() => { $('#product-grid').innerHTML = '<div class="empty-products">商品載入失敗，請確認 PHP 伺服器已啟動。</div>'; });
loadUser().catch(() => { renderAccount(); renderCart(); });

