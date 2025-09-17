// Ilumina PWA JavaScript Application
class IluminaApp {
    constructor() {
        // Detect base path to support deployments under subdirectories (e.g., /ILUMINA/public)
    const fullPath = window.location.pathname;
    // Compute directory path (without filename), and remove trailing slash except for root
    const dirPath = fullPath.endsWith('/') ? fullPath : fullPath.substring(0, fullPath.lastIndexOf('/') + 1);
    this.basePath = dirPath === '/' ? '' : dirPath.replace(/\/$/, '');
        this.apiBase = window.location.origin + this.basePath;
        this.currentLat = null;
        this.currentLng = null;
        this.map = null;
        this.markers = [];
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.checkAPIHealth();
        this.setupPWA();
    }
    
    setupEventListeners() {
        // Navigation buttons
        document.getElementById('btn-citizen').addEventListener('click', () => {
            this.showView('citizen-view');
        });
        
        document.getElementById('btn-manager').addEventListener('click', () => {
            this.showView('manager-view');
            this.initManagerView();
        });
        
        // Citizen form
        document.getElementById('ticket-form').addEventListener('submit', (e) => {
            this.handleTicketSubmit(e);
        });
        
        // Location button
        document.getElementById('get-location').addEventListener('click', () => {
            this.getCurrentLocation();
        });
    }
    
    showView(viewId) {
        // Hide all views
        document.querySelectorAll('.view').forEach(view => {
            view.classList.add('hidden');
        });
        
        // Show selected view
        document.getElementById(viewId).classList.remove('hidden');
    }
    
    async checkAPIHealth() {
        try {
            const response = await fetch(`${this.apiBase}/health`);
            if (!response.ok) {
                throw new Error(`Health request failed with status ${response.status}`);
            }
            const data = await response.json();
            
            if (data.status === 'ok') {
                console.log('API is healthy:', data);
                document.querySelector('#system-status').innerHTML = `
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <span>API: Online (${data.version})</span>
                    </div>
                `;
            }
        } catch (error) {
            console.error('API health check failed:', error);
            document.querySelector('#system-status').innerHTML = `
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <span>API: Offline</span>
                </div>
            `;
        }
    }
    
    getCurrentLocation() {
        const button = document.getElementById('get-location');
        const locationInfo = document.getElementById('location-info');
        
        if (!navigator.geolocation) {
            alert('Geolocalização não é suportada neste navegador.');
            return;
        }
        
        button.textContent = '📍 Obtendo localização...';
        button.disabled = true;
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.currentLat = position.coords.latitude;
                this.currentLng = position.coords.longitude;
                
                locationInfo.innerHTML = `
                    <div class="text-green-600">
                        ✅ Localização obtida: ${this.currentLat.toFixed(6)}, ${this.currentLng.toFixed(6)}
                    </div>
                `;
                
                button.textContent = '📍 Localização Obtida';
                button.classList.remove('bg-blue-500', 'hover:bg-blue-700');
                button.classList.add('bg-green-500', 'hover:bg-green-700');
            },
            (error) => {
                console.error('Error getting location:', error);
                locationInfo.innerHTML = `
                    <div class="text-red-600">
                        ❌ Erro ao obter localização: ${error.message}
                    </div>
                `;
                button.textContent = '📍 Tentar Novamente';
                button.disabled = false;
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000
            }
        );
    }
    
    async handleTicketSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const ticketData = {
            title: formData.get('title'),
            description: formData.get('description'),
            citizen_name: formData.get('citizen_name'),
            citizen_phone: formData.get('citizen_phone'),
            priority: 'medium',
            latitude: this.currentLat,
            longitude: this.currentLng
        };
        
        // Validate required location
        if (!this.currentLat || !this.currentLng) {
            alert('Por favor, obtenha sua localização antes de enviar o chamado.');
            return;
        }
        
        try {
            const response = await fetch(`${this.apiBase}/api/v1/tickets`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(ticketData)
            });
            if (!response.ok) {
                throw new Error(`Ticket submit failed with status ${response.status}`);
            }
            const result = await response.json();
            
            if (result.status === 'success') {
                alert('Chamado enviado com sucesso! Protocolo: ' + result.data.id);
                e.target.reset();
                document.getElementById('location-info').innerHTML = '';
                document.getElementById('get-location').textContent = '📍 Obter Localização Atual';
                document.getElementById('get-location').classList.remove('bg-green-500', 'hover:bg-green-700');
                document.getElementById('get-location').classList.add('bg-blue-500', 'hover:bg-blue-700');
                this.currentLat = null;
                this.currentLng = null;
            } else {
                alert('Erro ao enviar chamado: ' + result.message);
            }
        } catch (error) {
            console.error('Error submitting ticket:', error);
            alert('Erro de conexão. Tente novamente.');
        }
    }
    
    async initManagerView() {
        await this.loadKPIs();
        this.initMap();
        await this.loadTickets();
    }
    
    async loadKPIs() {
        try {
            // Simulate KPI data - in real app, this would come from API
            const kpis = {
                total_tickets: 150,
                open_tickets: 25,
                in_progress_tickets: 18,
                closed_tickets: 107,
                overdue_tickets: 5
            };
            
            document.getElementById('total-tickets').textContent = kpis.total_tickets;
            document.getElementById('open-tickets').textContent = kpis.open_tickets;
            document.getElementById('progress-tickets').textContent = kpis.in_progress_tickets;
            document.getElementById('closed-tickets').textContent = kpis.closed_tickets;
        } catch (error) {
            console.error('Error loading KPIs:', error);
        }
    }
    
    initMap() {
        if (this.map) return; // Map already initialized
        
        // Initialize map centered on a default location (adjust as needed)
        this.map = L.map('map').setView([-23.5505, -46.6333], 12); // São Paulo coordinates
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(this.map);
        
        // Add sample markers
        this.addSampleMarkers();
    }
    
    addSampleMarkers() {
        const sampleTickets = [
            { id: 1, lat: -23.5505, lng: -46.6333, title: 'Poste quebrado na Rua A', status: 'open', priority: 'high' },
            { id: 2, lat: -23.5520, lng: -46.6350, title: 'Luz fraca na Av. B', status: 'in_progress', priority: 'medium' },
            { id: 3, lat: -23.5490, lng: -46.6320, title: 'Lâmpada queimada', status: 'resolved', priority: 'low' }
        ];
        
        sampleTickets.forEach(ticket => {
            const color = this.getMarkerColor(ticket.status);
            const marker = L.circleMarker([ticket.lat, ticket.lng], {
                color: color,
                fillColor: color,
                fillOpacity: 0.7,
                radius: 8
            }).addTo(this.map);
            
            marker.bindPopup(`
                <strong>${ticket.title}</strong><br>
                Status: ${ticket.status}<br>
                Prioridade: ${ticket.priority}<br>
                ID: ${ticket.id}
            `);
            
            this.markers.push(marker);
        });
    }
    
    getMarkerColor(status) {
        switch (status) {
            case 'open': return '#ef4444';
            case 'in_progress': return '#f59e0b';
            case 'resolved': return '#10b981';
            default: return '#6b7280';
        }
    }
    
    async loadTickets() {
        try {
            // Simulate ticket data for kanban
            const tickets = [
                { id: 1, title: 'Poste quebrado na Rua A', status: 'open', priority: 'high' },
                { id: 2, title: 'Luz fraca na Av. B', status: 'in_progress', priority: 'medium' },
                { id: 3, title: 'Lâmpada queimada', status: 'resolved', priority: 'low' },
                { id: 4, title: 'Fiação exposta', status: 'open', priority: 'high' },
                { id: 5, title: 'Poste inclinado', status: 'in_progress', priority: 'medium' }
            ];
            
            this.renderKanban(tickets);
        } catch (error) {
            console.error('Error loading tickets:', error);
        }
    }
    
    renderKanban(tickets) {
        const openColumn = document.getElementById('open-column');
        const progressColumn = document.getElementById('progress-column');
        const resolvedColumn = document.getElementById('resolved-column');
        
        // Clear columns
        openColumn.innerHTML = '';
        progressColumn.innerHTML = '';
        resolvedColumn.innerHTML = '';
        
        tickets.forEach(ticket => {
            const ticketCard = this.createTicketCard(ticket);
            
            switch (ticket.status) {
                case 'open':
                    openColumn.appendChild(ticketCard);
                    break;
                case 'in_progress':
                    progressColumn.appendChild(ticketCard);
                    break;
                case 'resolved':
                    resolvedColumn.appendChild(ticketCard);
                    break;
            }
        });
    }
    
    createTicketCard(ticket) {
        const card = document.createElement('div');
        card.className = `ticket-card ticket-priority-${ticket.priority}`;
        card.innerHTML = `
            <div class="text-xs font-semibold text-gray-500">#${ticket.id}</div>
            <div class="font-medium">${ticket.title}</div>
            <div class="text-xs mt-1 capitalize">
                <span class="bg-gray-100 px-2 py-1 rounded">${ticket.priority}</span>
            </div>
        `;
        
        return card;
    }
    
    setupPWA() {
        // Register service worker
        if ('serviceWorker' in navigator) {
            const swPath = `${this.basePath || ''}/sw.js`;
            const scope = (this.basePath || '/') + '/';
            navigator.serviceWorker.register(swPath, { scope: scope.replace(/\/\/$/, '/') })
                .then(registration => {
                    console.log('SW registered:', registration);
                })
                .catch(error => {
                    console.log('SW registration failed:', error);
                });
        }
    }
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new IluminaApp();
});