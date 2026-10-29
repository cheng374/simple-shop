<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>商店後台｜日常選物所</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div id="toast" class="toast" role="status"></div>

  <main id="login-screen" class="login-screen">
    <section class="login-card">
      <a class="brand" href="../"><span class="brand-mark">日</span><span>日常選物所</span></a>
      <p class="eyebrow">STORE ADMIN</p>
      <h1>商店後台</h1>
      <p class="login-note">管理商品、庫存與訂單，讓日常選物所保持最新狀態。</p>
      <form id="login-form">
        <label>Email<input name="email" type="email" value="admin@simple-shop.local" autocomplete="username" required></label>
        <label>密碼<input name="password" type="password" placeholder="請輸入後台密碼" autocomplete="current-password" required></label>
        <button class="primary-button full" type="submit">登入後台 <span>→</span></button>
      </form>
      <p class="demo-account">後台帳號需由管理員在伺服器環境中設定，請勿把密碼提交到公開 repository。</p>
      <a class="back-link" href="../">← 回到商店前台</a>
    </section>
  </main>

  <div id="dashboard" class="dashboard" hidden>
    <header class="admin-header">
      <a class="brand" href="../"><span class="brand-mark">日</span><span>日常選物所 <small>STORE ADMIN</small></span></a>
      <div class="admin-user"><span id="admin-name"></span><button id="logout-button" class="ghost-button">登出</button></div>
    </header>
    <div class="admin-layout">
      <aside class="sidebar">
        <p class="eyebrow">CONTROL CENTER</p>
        <button class="side-tab active" data-view="products">商品與庫存 <span>→</span></button>
        <button class="side-tab" data-view="orders">訂單管理 <span>→</span></button>
        <a class="side-link" href="../">查看商店前台 ↗</a>
      </aside>
      <section class="admin-main">
        <div class="stats-grid">
          <article class="stat-card"><span>商品總數</span><strong id="stat-products">0</strong><small>目前上架商品</small></article>
          <article class="stat-card"><span>會員人數</span><strong id="stat-users">0</strong><small>已註冊購物會員</small></article>
          <article class="stat-card"><span>訂單數</span><strong id="stat-orders">0</strong><small>全部訂單</small></article>
          <article class="stat-card highlight"><span>營業額</span><strong id="stat-revenue">NT$0</strong><small>不含已取消訂單</small></article>
        </div>

        <section id="products-view" class="panel-view">
          <div class="page-heading"><div><p class="eyebrow">PRODUCTS</p><h1>商品與庫存</h1><p>新增商品、調整價格與庫存，前台會同步更新。</p></div><button id="add-product" class="primary-button">新增商品 <span>＋</span></button></div>
          <div class="table-card"><div class="table-scroll"><table><thead><tr><th>商品</th><th>分類</th><th>價格</th><th>庫存</th><th>操作</th></tr></thead><tbody id="products-table"><tr><td colspan="5" class="loading">正在載入商品…</td></tr></tbody></table></div></div>
        </section>

        <section id="orders-view" class="panel-view" hidden>
          <div class="page-heading"><div><p class="eyebrow">ORDERS</p><h1>訂單管理</h1><p>查看顧客資料、訂單內容，並更新處理進度。</p></div><div class="pending-badge">待處理 <strong id="stat-pending">0</strong> 筆</div></div>
          <div class="table-card"><div class="table-scroll"><table><thead><tr><th>訂單</th><th>顧客</th><th>商品</th><th>金額</th><th>狀態</th></tr></thead><tbody id="orders-table"><tr><td colspan="5" class="loading">正在載入訂單…</td></tr></tbody></table></div></div>
        </section>
      </section>
    </div>
  </div>

  <div id="modal-backdrop" class="modal-backdrop" hidden></div>
  <section id="product-modal" class="product-modal" hidden>
    <button class="modal-close" id="close-product-modal" aria-label="關閉">×</button>
    <p class="eyebrow">PRODUCT EDITOR</p>
    <h2 id="product-modal-title">新增商品</h2>
    <form id="product-form">
      <input name="id" type="hidden">
      <div class="form-grid"><label>商品名稱<input name="name" required></label><label>分類<input name="category" placeholder="例如：生活選物" required></label></div>
      <label>商品說明<textarea name="description" rows="3" required></textarea></label>
      <div class="form-grid"><label>價格（NT$）<input name="price" type="number" min="1" required></label><label>庫存<input name="stock" type="number" min="0" required></label></div>
      <div class="form-grid"><label>圖示 Emoji<input name="emoji" maxlength="8" placeholder="🛍️" required></label><label>背景色<input name="accent" type="text" placeholder="#dce8dc" required></label></div>
      <div class="form-actions"><button type="button" class="ghost-button" id="cancel-product">取消</button><button class="primary-button" type="submit">儲存商品 <span>→</span></button></div>
    </form>
  </section>
  <script src="app.js"></script>
</body>
</html>

