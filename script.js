// AyoFoods — Main Script

// ── Cart stored in localStorage ──────────────────────────────────────────────
function getCart() {
  return JSON.parse(localStorage.getItem('ayoCart') || '[]');
}
function saveCart(cart) {
  localStorage.setItem('ayoCart', JSON.stringify(cart));
  updateCartCount();
}
function updateCartCount() {
  const count = getCart().reduce((s, i) => s + i.qty, 0);
  document.querySelectorAll('#cartCount').forEach(el => el.textContent = count);
}

// ── Toast notification ────────────────────────────────────────────────────────
function showToast(msg) {
  const t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.classList.add('show');
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

// ── Menu data (fallback when no PHP backend) ──────────────────────────────────
const MENU_ITEMS = [
  { id:1,  name:'Jollof Rice & Chicken',  description:'Smoky Nigerian jollof rice with grilled chicken',  price:2500, category:'food',    image_url:'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=400' },
  { id:2,  name:'Fried Rice & Beef',      description:'Colorful fried rice with tender beef chunks',       price:2200, category:'food',    image_url:'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400' },
  { id:3,  name:'Pounded Yam & Egusi',    description:'Smooth pounded yam with rich egusi soup',           price:3000, category:'food',    image_url:'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400' },
  { id:4,  name:'Grilled Fish',           description:'Whole tilapia grilled with spices and herbs',       price:3500, category:'food',    image_url:'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=400' },
  { id:5,  name:'Suya Platter',           description:'Spiced beef skewers with onions and tomatoes',      price:2000, category:'food',    image_url:'https://images.unsplash.com/photo-1529042410759-befb1204b468?w=400' },
  { id:6,  name:'Burger & Fries',         description:'Juicy beef burger with crispy fries',               price:2800, category:'food',    image_url:'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400' },
  { id:7,  name:'Peppered Snail',         description:'Tender snails in spicy pepper sauce',               price:4000, category:'food',    image_url:'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400' },
  { id:8,  name:'Shawarma',              description:'Chicken shawarma with garlic sauce and veggies',    price:1800, category:'food',    image_url:'https://images.unsplash.com/photo-1561651823-34feb02250e4?w=400' },
  { id:9,  name:'Chapman',               description:'Classic Nigerian Chapman cocktail drink',            price:800,  category:'drink',   image_url:'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=400' },
  { id:10, name:'Zobo Drink',            description:'Chilled hibiscus drink with ginger',                price:500,  category:'drink',   image_url:'https://images.unsplash.com/photo-1497534446932-c925b458314e?w=400' },
  { id:11, name:'Fresh Juice',           description:'Freshly blended fruit juice of the day',            price:700,  category:'drink',   image_url:'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=400' },
  { id:12, name:'Smoothie Bowl',         description:'Thick fruit smoothie with granola toppings',        price:1500, category:'drink',   image_url:'https://images.unsplash.com/photo-1490474418585-ba9bad8fd0ea?w=400' },
  { id:13, name:'Chin Chin',             description:'Crunchy fried dough snack',                         price:600,  category:'dessert', image_url:'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=400' },
  { id:14, name:'Puff Puff',             description:'Soft deep-fried dough balls',                       price:500,  category:'dessert', image_url:'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=400' },
  { id:15, name:'Ice Cream',             description:'Creamy vanilla and chocolate ice cream',             price:1200, category:'dessert', image_url:'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400' },
];

let allItems = [];
let currentFilter = 'all';

// ── Render menu cards ─────────────────────────────────────────────────────────
function renderMenu(items) {
  const grid = document.getElementById('menuGrid');
  if (!grid) return;
  if (!items.length) {
    grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:var(--text-muted)">No items found.</p>';
    return;
  }
  grid.innerHTML = items.map(item => `
    <div class="menu-card">
      <img src="${item.image_url}" alt="${item.name}" loading="lazy"/>
      <div class="menu-card-body">
        <h3>${item.name}</h3>
        <p>${item.description}</p>
        <div class="menu-card-footer">
          <span class="price">₦${Number(item.price).toLocaleString()}</span>
          <button class="add-btn" onclick="addToCart(${item.id})">
            <i class="fas fa-plus"></i> Add
          </button>
        </div>
      </div>
    </div>
  `).join('');
}

// ── Filter menu ───────────────────────────────────────────────────────────────
function filterMenu(cat) {
  currentFilter = cat;
  document.querySelectorAll('.tab').forEach(t => {
    t.classList.toggle('active', t.dataset.cat === cat);
  });
  const filtered = cat === 'all' ? allItems : allItems.filter(i => i.category === cat);
  renderMenu(filtered);
}

// ── Load menu (tries PHP backend, falls back to static data) ──────────────────
async function loadMenu() {
  const grid = document.getElementById('menuGrid');
  if (!grid) return;
  try {
    const res = await fetch('backend/menu.php');
    if (!res.ok) throw new Error('no backend');
    const json = await res.json();
    allItems = json.data || [];
  } catch {
    allItems = MENU_ITEMS;
  }
  renderMenu(allItems);
}

// ── Add to cart ───────────────────────────────────────────────────────────────
function addToCart(id) {
  const item = allItems.find(i => i.id === id);
  if (!item) return;
  const cart = getCart();
  const existing = cart.find(c => c.id === id);
  if (existing) {
    existing.qty++;
  } else {
    cart.push({ id: item.id, name: item.name, price: item.price, image: item.image_url, qty: 1 });
  }
  saveCart(cart);
  showToast(`${item.name} added to cart!`);
}

// ── Contact form ──────────────────────────────────────────────────────────────
function sendMessage(e) {
  e.preventDefault();
  showToast('Message sent! We\'ll get back to you soon.');
  e.target.reset();
}

// ── CART PAGE ─────────────────────────────────────────────────────────────────
function renderCart() {
  const layout = document.getElementById('cartLayout');
  if (!layout) return;
  const cart = getCart();

  if (!cart.length) {
    layout.innerHTML = `
      <div class="cart-empty" style="grid-column:1/-1">
        <i class="fas fa-shopping-cart"></i>
        <p>Your cart is empty.</p>
        <a href="index.html#menu" class="btn btn-primary" style="margin-top:1rem">Browse Menu</a>
      </div>`;
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
          <div class="cart-item-info">
            <h4>${item.name}</h4>
            <div class="item-price">₦${Number(item.price).toLocaleString()}</div>
          </div>
          <div class="qty-controls">
            <button class="qty-btn" onclick="changeQty(${item.id}, -1)">−</button>
            <span class="qty-val">${item.qty}</span>
            <button class="qty-btn" onclick="changeQty(${item.id}, 1)">+</button>
          </div>
          <button class="remove-btn" onclick="removeItem(${item.id})" aria-label="Remove">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      `).join('')}
    </div>
    <div class="cart-summary">
      <h3>Order Summary</h3>
      <div class="summary-row"><span>Subtotal</span><span>₦${subtotal.toLocaleString()}</span></div>
      <div class="summary-row"><span>Delivery</span><span>₦${delivery.toLocaleString()}</span></div>
      <div class="summary-row total"><span>Total</span><span>₦${total.toLocaleString()}</span></div>
      <a href="checkout.html" class="btn btn-primary btn-full" style="margin-top:1.5rem">
        <i class="fas fa-lock"></i> Proceed to Checkout
      </a>
      <a href="index.html#menu" class="btn btn-outline btn-full" style="margin-top:.75rem">
        Continue Shopping
      </a>
    </div>`;
}

function changeQty(id, delta) {
  const cart = getCart();
  const item = cart.find(i => i.id === id);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) cart.splice(cart.indexOf(item), 1);
  saveCart(cart);
  renderCart();
}

function removeItem(id) {
  const cart = getCart().filter(i => i.id !== id);
  saveCart(cart);
  renderCart();
  showToast('Item removed.');
}

// ── CHECKOUT PAGE ─────────────────────────────────────────────────────────────
function renderOrderSummary() {
  const summaryItems = document.getElementById('summaryItems');
  const summaryTotals = document.getElementById('summaryTotals');
  if (!summaryItems) return;

  const cart = getCart();
  if (!cart.length) {
    window.location.href = 'cart.html';
    return;
  }

  const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
  const delivery = 500;
  const total = subtotal + delivery;

  summaryItems.innerHTML = cart.map(item => `
    <div class="summary-item">
      <span>${item.name} × ${item.qty}</span>
      <span>₦${(item.price * item.qty).toLocaleString()}</span>
    </div>
  `).join('');

  summaryTotals.innerHTML = `
    <div class="summary-row"><span>Subtotal</span><span>₦${subtotal.toLocaleString()}</span></div>
    <div class="summary-row"><span>Delivery</span><span>₦${delivery.toLocaleString()}</span></div>
    <div class="summary-row total"><span>Total</span><span>₦${total.toLocaleString()}</span></div>
  `;
}

async function placeOrder(e) {
  e.preventDefault();
  const btn = document.getElementById('placeOrderBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Placing Order...';

  const cart = getCart();
  const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
  const total = subtotal + 500;

  const payload = {
    customer_name: document.getElementById('cName').value,
    customer_phone: document.getElementById('cPhone').value,
    customer_address: document.getElementById('cAddress').value,
    payment_method: document.querySelector('input[name="payment"]:checked').value,
    items: cart,
    total_amount: total,
  };

  let orderId = 'AYO-' + Date.now();
  try {
    const res = await fetch('backend/orders.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    if (res.ok) {
      const json = await res.json();
      if (json.order_id) orderId = 'AYO-' + json.order_id;
    }
  } catch { /* backend unavailable — use local order ID */ }

  localStorage.removeItem('ayoCart');
  updateCartCount();

  document.getElementById('orderIdDisplay').textContent = 'Order ID: ' + orderId;
  document.getElementById('modalMsg').textContent =
    `Your order has been received, ${payload.customer_name}! We'll prepare it right away.`;
  document.getElementById('successModal').classList.add('active');
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  updateCartCount();
  if (document.getElementById('menuGrid')) loadMenu();
  if (document.getElementById('cartLayout')) renderCart();
  if (document.getElementById('summaryItems')) renderOrderSummary();
});
