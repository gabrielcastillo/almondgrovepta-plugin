(function( $ ) {
	'use strict';

	const agptaPlugin = {
		cart: [],
		cartItems: null,
		cartTotal: null,

		init: function() {
			this.agptaCacheDomElements();
			this.agptaBindEvents();
			//this.fetchProducts();
			//this.renderCart();
		},

		agptaCacheDomElements: function() {
			this.$body = $('body');
			this.cart = JSON.parse(localStorage.getItem('cart')) || [];
			this.$cartItems = this.$body.find('.cart-items');
			this.$cartTotal = this.$body.find('.cart-total');
			this.$checkoutBtn = this.$body.find('.checkout-button');
		},

		agptaBindEvents: function() {
			//this.$checkoutBtn.on('click', this.checkoutProcess.bind(this));
		},

		renderCart: function() {
			this.$cartItems.empty();
			let total = 0;

			this.cart.forEach((item) => {
				const li = $('<li></li>').text(`${item.name} x${item.qty} - $${(item.price * item.qty).toFixed(2)}`);
				this.$cartItems.append(li);
				total += parseFloat(item.price) * item.qty;
			});

			this.$cartTotal.text(total.toFixed(2));
			localStorage.setItem('cart', JSON.stringify(this.cart));
		},

		addToCart: function(product) {
			const existingItem = this.cart.find(item => item.id === product.id);

			if (existingItem) {
				existingItem.qty += 1;
			} else {
				product.qty = 1;
				product.price = parseFloat(product.price);
				this.cart.push(product);
			}

			this.renderCart();
		},

		// fetchProducts: function() {
		// 	fetch(`${agpta_data.ajax_url}?action=get_pta_events`)
		// 		.then(res => res.json())
		// 		.then(events => {
		// 			const container = document.getElementById('event-products');
		// 			if (!container) return;
		//
		// 			events.forEach(event => {
		// 				const div = document.createElement('div');
		// 				div.className = 'border p-4 rounded shadow';
		// 				div.innerHTML = `
		// 					<h4 class="font-bold">${event.title}</h4>
		// 					<p class="text-sm">$${event.price}</p>
		// 					<button class="add-to-cart bg-blue-600 text-white px-3 py-1 mt-2 rounded"
		// 						data-id="${event.id}"
		// 						data-name="${event.title}"
		// 						data-price="${event.price}">
		// 						Add to Cart
		// 					</button>
		// 				`;
		// 				container.appendChild(div);
		// 			});
		//
		// 			const self = this;
		// 			document.querySelectorAll('.add-to-cart').forEach(button => {
		// 				button.addEventListener('click', () => {
		// 					self.addToCart({
		// 						id: button.dataset.id,
		// 						name: button.dataset.name,
		// 						price: button.dataset.price
		// 					});
		// 				});
		// 			});
		// 		})
		// 		.catch(err => {
		// 			alert('Failed to fetch products. Please try again.');
		// 			console.error('Product fetch error:', err);
		// 		});
		// },

		// checkoutProcess: function() {
		// 	fetch(agpta_data.ajax_url, {
		// 		method: 'POST',
		// 		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		// 		body: `action=submit_cart&nonce=${agpta_data.nonce}&cart=${encodeURIComponent(JSON.stringify(this.cart))}`
		// 	})
		// 		.then(res => res.json())
		// 		.then(data => {
		// 			alert(data.message);
		// 			localStorage.removeItem('cart');
		// 			location.reload();
		// 		})
		// 		.catch(err => {
		// 			alert('Checkout failed. Please try again.');
		// 			console.error('Checkout error:', err);
		// 		});
		// }
	};

	$(document).ready(function() {
		agptaPlugin.init();
	});

})( jQuery );
