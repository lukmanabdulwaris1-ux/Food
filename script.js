// AyoFoods — Main Script

// ── Auth state management ─────────────────────────────────────────────────────
function getAuthToken() { return localStorage.getItem('ayoToken') || null; }
function getAuthUser() { try { return JSON.parse(localStorage.getItem('ayoUser') || 'null'); } catch { return null; } }
function setAuth(token, user) { localStorage.setItem('ayoToken', token); localStorage.setItem('ayoUser', JSON.stringify(user)); }
function clearAuth() { localStorage.removeItem('ayoToken'); localStorage.removeItem('ayoUser'); }

// ── Cart stored in localStorage ───────────────────────────────────────────────
function getCart() { return JSON.parse(localStorage.getItem('ayoCart') || '[]'); }
function saveCart(cart) { localStorage.setItem('ayoCart', JSON.stringify(cart)); updateCartCount(); }
function updateCartCount() {
  const count = getCart().reduce((s, i) => s + i.qty, 0);
  document.querySelectorAll('#cartCount').forEach(el => el.textContent = count);
}

// ── Toast notification ────────────────────────────────────────────────────────
function showToast(msg) {
  const t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg; t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2800);
}

// ── Navbar scroll effect ──────────────────────────────────────────────────────
window.addEventListener('scroll', () => {
  document.getElementById('navbar')?.classList.toggle('scrolled', window.scrollY > 20);
});

// ── Hamburger menu ────────────────────────────────────────────────────────────
document.getElementById('hamburger')?.addEventListener('click', () => {
  document.getElementById('navLinks')?.classList.toggle('open');
});

// ── Navbar auth UI ────────────────────────────────────────────────────────────
function updateNavAuth() {
  const authNav = document.getElementById('authNav');
  if (!authNav) return;
  const user = getAuthUser();
  const token = getAuthToken();

  if (user && token) {
    const initials = user.name ? user.name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase() : 'U';
    authNav.innerHTML = `
      <div class="user-menu" style="position:relative">
        <button onclick="toggleUserMenu()" style="
          display:flex;align-items:center;gap:.5rem;background:rgba(255,255,255,.08);
          border:1px solid rgba(255,255,255,.15);border-radius:50px;padding:.35rem .85rem .35rem .35rem;
          cursor:pointer;color:#fff;font-family:'Poppins',sans-serif;font-size:.85rem;font-weight:500;transition:all .2s
        ">
          <span style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#e85d04,#f48c06);
            display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:#fff;flex-shrink:0">
            ${initials}
          </span>
          ${user.name.split(' ')[0]}
          <i class="fas fa-chevron-down" style="font-size:.65rem;color:#a0a0b0"></i>
        </button>
        <div id="userDropdown" style="
          display:none;position:absolute;top:calc(100% + .5rem);right:0;
          background:#16213e;border:1px solid rgba(255,255,255,.1);border-radius:12px;
          min-width:180px;box-shadow:0 10px 30px rgba(0,0,0,.4);z-index:500;overflow:hidden
        ">
          <div style="padding:.75rem 1rem;border-bottom:1px solid rgba(255,255,255,.07)">
            <div style="font-size:.88rem;font-weight:600;color:#fff">${user.name}</div>
            <div style="font-size:.75rem;color:#a0a0b0">${user.email}</div>
          </div>
          <a href="#" onclick="showMyOrders();return false" style="display:flex;align-items:center;gap:.6rem;padding:.7rem 1rem;color:#e0e0e0;text-decoration:none;font-size:.85rem;transition:background .2s" onmouseover="this.style.background='rgba(255,255,255,.05)'" onmouseout="this.style.background='transparent'">
            <i class="fas fa-receipt" style="color:#f48c06;width:16px"></i> My Orders
          </a>
          <a href="#" onclick="handleLogout();return false" style="display:flex;align-items:center;gap:.6rem;padding:.7rem 1rem;color:#ff8080;text-decoration:none;font-size:.85rem;transition:background .2s" onmouseover="this.style.background='rgba(255,80,80,.05)'" onmouseout="this.style.background='transparent'">
            <i class="fas fa-sign-out-alt" style="width:16px"></i> Logout
          </a>
        </div>
      </div>`;
  } else {
    authNav.innerHTML = `<a href="auth.html" style="display:inline-flex;align-items:center;gap:.4rem;padding:.45rem 1.1rem;background:linear-gradient(135deg,#e85d04,#f48c06);color:#fff;border-radius:50px;font-family:'Poppins',sans-serif;font-size:.85rem;font-weight:600;text-decoration:none;transition:all .2s">Login</a>`;
  }
}

function toggleUserMenu() {
  const dd = document.getElementById('userDropdown');
  if (dd) dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}

document.addEventListener('click', e => {
  const menu = document.querySelector('.user-menu');
  if (menu && !menu.contains(e.target)) {
    const dd = document.getElementById('userDropdown');
    if (dd) dd.style.display = 'none';
  }
});

function handleLogout() {
  clearAuth(); updateNavAuth();
  showToast('Logged out successfully.');
  if (window.location.pathname.includes('checkout')) window.location.href = 'index.html';
}

// ── My Orders modal ───────────────────────────────────────────────────────────
async function showMyOrders() {
  const dd = document.getElementById('userDropdown');
  if (dd) dd.style.display = 'none';

  let modal = document.getElementById('myOrdersModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'myOrdersModal';
    modal.style.cssText = 'position:fixed;inset:0;z-index:3000;background:rgba(0,0,0,.75);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:1rem';
    modal.innerHTML = `
      <div style="background:#16213e;border-radius:20px;padding:2rem;max-width:600px;width:100%;border:1px solid rgba(255,255,255,.1);max-height:80vh;overflow-y:auto">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
          <h3 style="font-size:1.2rem;font-weight:700;color:#fff"><i class="fas fa-receipt" style="color:#f48c06"></i> My Orders</h3>
          <button onclick="document.getElementById('myOrdersModal').remove()" style="background:none;border:none;color:#a0a0b0;font-size:1.2rem;cursor:pointer"><i class="fas fa-times"></i></button>
        </div>
        <div id="myOrdersList"><div style="text-align:center;padding:2rem;color:#a0a0b0"><i class="fas fa-spinner fa-spin"></i> Loading...</div></div>
      </div>`;
    document.body.appendChild(modal);
  }

  const token = getAuthToken();
  if (!token) { document.getElementById('myOrdersList').innerHTML = '<p style="color:#a0a0b0;text-align:center">Please log in to view orders.</p>'; return; }

  try {
    const res = await fetch('backend/orders.php?action=my_orders', { headers: { 'Authorization': 'Bearer ' + token } });
    const json = await res.json();
    if (json.success && json.data.length) {
      document.getElementById('myOrdersList').innerHTML = json.data.map(o => {
        const items = Array.isArray(o.items) ? o.items.map(i => i.name + ' x' + i.qty).join(', ') : '';
        const statusColors = { pending:'#f48c06', confirmed:'#5096ff', preparing:'#9650ff', delivered:'#50c878', cancelled:'#ff5050' };
        const color = statusColors[o.status] || '#a0a0b0';
        return `<div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:1rem;margin-bottom:.75rem">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
            <strong style="color:#fff">#AYO-${o.id}</strong>
            <span style="background:${color}22;color:${color};padding:.2rem .7rem;border-radius:50px;font-size:.75rem;font-weight:600">${o.status}</span>
          </div>
          <div style="font-size:.83rem;color:#a0a0b0;margin-bottom:.4rem">${items}</div>
          <div style="display:flex;justify-content:space-between;font-size:.85rem">
            <span style="color:#f48c06;font-weight:700">&#8358;${Number(o.total_amount).toLocaleString()}</span>
            <span style="color:#a0a0b0">${new Date(o.created_at).toLocaleDateString('en-NG')}</span>
          </div>
        </div>`;
      }).join('');
    } else if (json.success) {
      document.getElementById('myOrdersList').innerHTML = '<p style="color:#a0a0b0;text-align:center;padding:2rem">No orders yet. <a href="index.html#menu" style="color:#f48c06">Browse menu</a></p>';
    } else {
      document.getElementById('myOrdersList').innerHTML = '<p style="color:#ff8080;text-align:center">Failed to load orders.</p>';
    }
  } catch {
    document.getElementById('myOrdersList').innerHTML = '<p style="color:#ff8080;text-align:center">Network error.</p>';
  }
}

// ── Menu data (fallback when no PHP backend) ──────────────────────────────────
const MENU_ITEMS = [
  { id:1,  name:'Jollof Rice & Chicken',  description:'Smoky Nigerian jollof rice with grilled chicken',  price:2500, category:'food',    image_url:'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=400' },
  { id:2,  name:'Fried Rice & Beef',      description:'Colorful fried rice with tender beef chunks',       price:2200, category:'food',    image_url:'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400' },
  { id:3,  name:'Pounded Yam & Egusi',    description:'Smooth pounded yam with rich egusi soup',           price:3000, category:'food',    image_url:'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400' },
  { id:4,  name:'Grilled Fish',           description:'Whole tilapia grilled with spices and herbs',       price:3500, category:'food',    image_url:'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=400' },
  { id:5,  name:'Suya Platter',           description:'Spiced beef skewers with onions and tomatoes',      price:2000, category:'food',    image_url:'https://images.unsplash.com/photo-1529042410759-befb1204b468?w=400' },
  { id:6,  name:'Burger & Fries',         description:'Juicy beef burger with crispy fries',               price:2800, category:'food',    image_url:'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400' },
  { id:7,  name:'Peppered Snail',         description:'Tender snails in spicy pepper sauce',               price:4000, category:'food',    image_url:'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400' },
  { id:8,  name:'Shawarma',               description:'Chicken shawarma with garlic sauce and veggies',    price:1800, category:'food',    image_url:'https://images.unsplash.com/photo-1561651823-34feb02250e4?w=400' },
  { id:9,  name:'Chapman',                description:'Classic Nigerian Chapman cocktail drink',            price:800,  category:'drink',   image_url:'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=400' },
  { id:10, name:'Zobo Drink',             description:'Chilled hibiscus drink with ginger',                price:500,  category:'drink',   image_url:'https://images.unsplash.com/photo-1497534446932-c925b458314e?w=400' },
  { id:11, name:'Fresh Juice',            description:'Freshly blended fruit juice of the day',            price:700,  category:'drink',   image_url:'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=400' },
  { id:12, name:'Smoothie Bowl',          description:'Thick fruit smoothie with granola toppings',        price:1500, category:'drink',   image_url:'https://images.unsplash.com/photo-1490474418585-ba9bad8fd0ea?w=400' },
  { id:13, name:'Chin Chin',              description:'Crunchy fried dough snack',                         price:600,  category:'dessert', image_url:'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=400' },
  { id:14, name:'Puff Puff',              description:'Soft deep-fried dough balls',                       price:500,  category:'dessert', image_url:'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=400' },
  { id:15, name:'Ice Cream',              description:'Creamy vanilla and chocolate ice cream',             price:1200, category:'dessert', image_url:'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400' },
];

let allItems = [];
let currentFilter = 'all';

function renderMenu(items) {
  const grid = document.getElementById('menuGrid');
  if (!grid) return;
  if (!items.length) { grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:var(--text-muted)">No items found.</p>'; return; }
  grid.innerHTML = items.map(item => `
    <div class="menu-card">
      <img src="${item.image_url}" alt="${item.name}" loading="lazy"/>
      <div class="menu-card-body">
        <h3>${item.name}</h3>
        <p>${item.description}</p>
        <div class="menu-card-footer">
          <span class="price">&#8358;${Number(item.price).toLocaleString()}</span>
          <button class="add-btn" onclick="addToCart(${item.id})"><i class="fas fa-plus"></i> Add</button>
        </div>
      </div>
    </div>`).join('');
}

function filterMenu(cat) {
  currentFilter = cat;
  document.querySelectorAll('.tab').forEach(t => t.classList.toggle('active', t.dataset.cat === cat));
  renderMenu(cat === 'all' ? allItems : allItems.filter(i => i.category === cat));
}

async function loadMenu() {
  const grid = document.getElementById('menuGrid');
  if (!grid) return;
  try {
    const res = await fetch('backend/menu.php');
    if (!res.ok) throw new Error('no backend');
    const json = await res.json();
    allItems = json.data || [];
  } catch { allItems = MENU_ITEMS; }
  renderMenu(allItems);
}

function addToCart(id) {
  const item = allItems.find(i => i.id === id);
  if (!item) return;
  const cart = getCart();
  const existing = cart.find(c => c.id === id);
  if (existing) { existing.qty++; }
  else { cart.push({ id: item.id, name: item.name, price: item.price, image: item.image_url, qty: 1 }); }
  saveCart(cart);
  showToast(`${item.name} added to cart!`);
}

function sendMessage(e) {
  e.preventDefault();
  showToast("Message sent! We'll get back to you soon.");
  e.target.reset();
}

// ── CART PAGE ─────────────────────────────────────────────────────────────────
function renderCart() {
  const layout = document.getElementById('cartLayout');
  if (!layout) return;
  const cart = getCart();
  if (!cart.length) {
    layout.innerHTML = `<div class="cart-empty" style="grid-column:1/-1"><i class="fas fa-shopping-cart"></i><p>Your cart is empty.</p><a href="index.html#menu" class="btn btn-primary" style="margin-top:1rem">Browse Menu</a></div>`;
    return;
  }
  const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
  const delivery = 500;
  const total = subtotal + delivery;
  layout.innerHTML = `
    <div class="cart-items">
      ${cart.map(item => `
        <div class="cart-item">
          <img src="${item.image}" alt="${item.name}" loading="lazy"/>
          <div class="cart-item-info"><h4>${item.name}</h4><div class="item-price">&#8358;${Number(item.price).toLocaleString()}</div></div>
          <div class="qty-controls">
            <button class="qty-btn" onclick="changeQty(${item.id},-1)">&#8722;</button>
            <span class="qty-val">${item.qty}</span>
            <button class="qty-btn" onclick="changeQty(${item.id},1)">+</button>
          </div>
          <button class="remove-btn" onclick="removeItem(${item.id})" aria-label="Remove"><i class="fas fa-trash"></i></button>
        </div>`).join('')}
    </div>
    <div class="cart-summary">
      <h3>Order Summary</h3>
      <div class="summary-row"><span>Subtotal</span><span>&#8358;${subtotal.toLocaleString()}</span></div>
      <div class="summary-row"><span>Delivery</span><span>&#8358;${delivery.toLocaleString()}</span></div>
      <div class="summary-row total"><span>Total</span><span>&#8358;${total.toLocaleString()}</span></div>
      <a href="checkout.html" class="btn btn-primary btn-full" style="margin-top:1.5rem"><i class="fas fa-lock"></i> Proceed to Checkout</a>
      <a href="index.html#menu" class="btn btn-outline btn-full" style="margin-top:.75rem">Continue Shopping</a>
    </div>`;
}

function changeQty(id, delta) {
  const cart = getCart();
  const item = cart.find(i => i.id === id);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) cart.splice(cart.indexOf(item), 1);
  saveCart(cart); renderCart();
}

function removeItem(id) {
  saveCart(getCart().filter(i => i.id !== id));
  renderCart(); showToast('Item removed.');
}

// ── CHECKOUT PAGE ─────────────────────────────────────────────────────────────
function renderOrderSummary() {
  const summaryItems = document.getElementById('summaryItems');
  const summaryTotals = document.getElementById('summaryTotals');
  if (!summaryItems) return;

  const cart = getCart();
  if (!cart.length) { window.location.href = 'cart.html'; return; }

  // Redirect to login if not authenticated
  if (!getAuthToken()) { window.location.href = 'auth.html?redirect=checkout'; return; }

  const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
  const delivery = 500;
  const total = subtotal + delivery;

  summaryItems.innerHTML = cart.map(item => `
    <div class="summary-item"><span>${item.name} &times; ${item.qty}</span><span>&#8358;${(item.price * item.qty).toLocaleString()}</span></div>`).join('');

  summaryTotals.innerHTML = `
    <div class="summary-row"><span>Subtotal</span><span>&#8358;${subtotal.toLocaleString()}</span></div>
    <div class="summary-row"><span>Delivery</span><span>&#8358;${delivery.toLocaleString()}</span></div>
    <div class="summary-row total"><span>Total</span><span>&#8358;${total.toLocaleString()}</span></div>`;

  // Pre-fill user info
  const user = getAuthUser();
  if (user) {
    const nameField = document.getElementById('cName');
    const phoneField = document.getElementById('cPhone');
    if (nameField && !nameField.value) nameField.value = user.name || '';
    if (phoneField && !phoneField.value) phoneField.value = user.phone || '';
  }
}

async function placeOrder(e) {
  e.preventDefault();
  const btn = document.getElementById('placeOrderBtn');
  btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Placing Order...';

  const cart = getCart();
  const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
  const total = subtotal + 500;
  const paymentMethod = document.querySelector('input[name="payment"]:checked').value;

  const payload = {
    customer_name:    document.getElementById('cName').value,
    customer_phone:   document.getElementById('cPhone').value,
    customer_address: document.getElementById('cAddress').value,
    payment_method:   paymentMethod,
    items:            cart,
    total_amount:     total,
  };

  let orderId = 'AYO-' + Date.now();
  const token = getAuthToken();
  const headers = { 'Content-Type': 'application/json' };
  if (token) headers['Authorization'] = 'Bearer ' + token;

  try {
    const res = await fetch('backend/orders.php', { method: 'POST', headers, body: JSON.stringify(payload) });
    if (res.ok) {
      const json = await res.json();
      if (json.order_id) orderId = 'AYO-' + json.order_id;
    }
  } catch {}

  // Save pending order for payment page
  localStorage.setItem('pendingOrder', JSON.stringify({
    orderId, items: cart, total,
    name: payload.customer_name, phone: payload.customer_phone, address: payload.customer_address,
  }));

  // Redirect to payment page for card/transfer/upi
  if (['card', 'transfer', 'upi'].includes(paymentMethod)) {
    localStorage.removeItem('ayoCart'); updateCartCount();
    window.location.href = 'upi.html';
    return;
  }

  // Cash on delivery — show success modal
  localStorage.removeItem('ayoCart'); localStorage.removeItem('pendingOrder'); updateCartCount();
  document.getElementById('orderIdDisplay').textContent = 'Order ID: ' + orderId;
  document.getElementById('modalMsg').textContent = `Your order has been received, ${payload.customer_name}! We'll prepare it right away.`;
  document.getElementById('successModal').classList.add('active');
  btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Order Placed!';
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  updateCartCount();
  updateNavAuth();
  if (document.getElementById('menuGrid')) loadMenu();
  if (document.getElementById('cartLayout')) renderCart();
  if (document.getElementById('summaryItems')) renderOrderSummary();
});
