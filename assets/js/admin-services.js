document.addEventListener('DOMContentLoaded', function () {
    const serviceForm = document.getElementById('service-form');
    const servicesList = document.getElementById('services-list');
    const serviceModalElement = document.getElementById('service-modal');
    const addServiceBtn = document.getElementById('add-service-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const toastContainer = document.getElementById('toast-container');

    // Injected via wp_localize_script
    const apiBase = bookingAppAdmin.restUrl;
    const nonce = bookingAppAdmin.nonce;

    // Store modal instance
    let modalInstance = null;

    if (window.Modal && serviceModalElement) {
        modalInstance = new Modal(serviceModalElement, {
            onHide: () => {
                // Ensure backdrop is removed and body scroll is restored
                document.querySelectorAll('[modal-backdrop]').forEach(el => el.remove());
                document.body.classList.remove('overflow-hidden');
            }
        });
    }

    // Toast helper
    function showToast(message, type = 'success') {
        const id = 'toast-' + Date.now();
        const bgColor = type === 'success' ? 'bg-green-100 border-green-400 text-green-800' : 'bg-red-100 border-red-400 text-red-800';

        const toastHtml = `
            <div id="${id}" class="flex items-center p-4 mb-4 w-full max-w-xs rounded-lg border shadow-lg ${bgColor} transform transition-all duration-300 translate-x-full" role="alert">
                <div class="text-sm font-normal">${message}</div>
                <button type="button" class="ml-auto -mx-1.5 -my-1.5 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-white inline-flex h-8 w-8" onclick="this.parentElement.remove()" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                </button>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toast = document.getElementById(id);

        // Slide in
        setTimeout(() => {
            if (toast) toast.classList.remove('translate-x-full');
        }, 10);

        // Auto remove
        setTimeout(() => {
            if (toast && toast.parentElement) {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }
        }, 3000);
    }

    // Helper for fetch with nonce
    async function apiFetch(endpoint, options = {}) {
        const defaultOptions = {
            headers: {
                'X-WP-Nonce': nonce,
                'Content-Type': 'application/json'
            }
        };
        const response = await fetch(`${apiBase}${endpoint}`, { ...defaultOptions, ...options });
        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'Server error');
        }
        return result;
    }

    // Refresh List
    async function refreshServices() {
        try {
            const services = await apiFetch('/services');
            renderServices(services);
        } catch (err) {
            console.error('Failed to load services:', err);
        }
    }

    function renderServices(services) {
        if (!services.length) {
            servicesList.innerHTML = `<tr><td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No services found.</td></tr>`;
            return;
        }

        servicesList.innerHTML = services.map(service => `
            <tr data-service-id="${service.id}">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${service.name}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${service.duration} min</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${parseFloat(service.price) <= 0
                ? '<span class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded border border-green-400">Free</span>'
                : `$${parseFloat(service.price).toFixed(2)}`}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer status-toggle" ${service.status === 'active' ? 'checked' : ''} data-id="${service.id}">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    </label>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button class="text-indigo-600 hover:text-indigo-900 mr-3 edit-service" data-id="${service.id}">Edit</button>
                    <button class="text-red-600 hover:text-red-900 delete-service" data-id="${service.id}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </td>
            </tr>
        `).join('');

        attachListeners();
    }

    function attachListeners() {
        // Edit
        document.querySelectorAll('.edit-service').forEach(btn => {
            btn.onclick = async () => {
                const id = btn.dataset.id;
                const row = btn.closest('tr');

                // Set hidden ID field first!
                document.getElementById('service-id').value = id;

                // Populate fields
                document.getElementById('name').value = row.children[0].innerText.trim();
                document.getElementById('duration').value = parseInt(row.children[1].innerText);
                const priceText = row.children[2].innerText.trim();
                document.getElementById('price').value = priceText.includes('Free') ? '0.00' : priceText.replace('$', '').replace(',', '');

                // Fetch full description
                try {
                    const services = await apiFetch('/services');
                    const service = services.find(s => s.id == id);
                    if (service) {
                        document.getElementById('description').value = service.description || '';
                    }
                } catch (e) { }

                if (modalInstance) modalInstance.show();
            };
        });

        // Delete
        document.querySelectorAll('.delete-service').forEach(btn => {
            btn.onclick = async () => {
                if (!confirm('Are you sure you want to delete this service?')) return;
                const id = btn.dataset.id;
                try {
                    const result = await apiFetch(`/services/${id}`, { method: 'DELETE' });
                    if (result.success) {
                        refreshServices();
                        showToast('Service deleted successfully');
                    }
                } catch (err) {
                    showToast('Error: ' + err.message, 'error');
                }
            };
        });

        // Status Toggle
        document.querySelectorAll('.status-toggle').forEach(chk => {
            chk.onchange = async () => {
                const id = chk.dataset.id;
                const status = chk.checked ? 'active' : 'inactive';
                try {
                    await apiFetch('/services', {
                        method: 'POST',
                        body: JSON.stringify({ id, status })
                    });
                    showToast(`Service ${status}`);
                } catch (err) {
                    showToast('Failed to update status', 'error');
                    chk.checked = !chk.checked;
                }
            };
        });
    }

    // Modal Add Button
    if (addServiceBtn) {
        addServiceBtn.onclick = () => {
            serviceForm.reset();
            document.getElementById('service-id').value = "0";
            if (modalInstance) modalInstance.show();
        };
    }

    // Modal Close Button
    if (closeModalBtn) {
        closeModalBtn.onclick = () => {
            if (modalInstance) modalInstance.hide();
        };
    }

    // Form Submit
    if (serviceForm) {
        serviceForm.onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(serviceForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const result = await apiFetch('/services', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });

                if (result.id) {
                    if (modalInstance) modalInstance.hide();
                    serviceForm.reset();
                    document.getElementById('service-id').value = "0";
                    refreshServices();
                    showToast('Service saved successfully!');
                }
            } catch (err) {
                showToast('Save Error: ' + err.message, 'error');
            }
        };
    }

    // Initial load
    attachListeners();
});
