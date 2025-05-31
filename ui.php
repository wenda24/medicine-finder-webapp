<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MedFinder መድሃኒት አፋላጊ - Locate Medicine in Addis Ababa</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://unpkg.com/maplibre-gl@2.4.0/dist/maplibre-gl.css" rel="stylesheet">
  <script src="https://unpkg.com/maplibre-gl@2.4.0/dist/maplibre-gl.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    
    * {
      font-family: 'Poppins', sans-serif;
    }
    
    body {
      background: url('https://images.unsplash.com/photo-1603398938373-e54da0bbf77e?q=80&w=2070&auto=format&fit=crop') center/cover fixed;
      min-height: 100vh;
      position: relative;
    }
    
    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.85);
      z-index: -1;
    }
    
    .hero-section {
      background: url('https://images.unsplash.com/photo-1587854692152-cbe660dbde88?q=80&w=1950&auto=format&fit=crop') center/cover;
      position: relative;
      overflow: hidden;
    }
    
  
    
    .pharmacy-card {
      transition: all 0.3s ease;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
      background: rgba(255, 255, 255, 0.95);
      border: 1px solid #e2e8f0;
      animation: fadeIn 0.5s ease-out;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .pharmacy-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
    }
    
    #map {
      height: 480px;
      border-radius: 16px;
      box-shadow: 0 4px 25px rgba(0, 0, 0, 0.1);
      border: 1px solid #e2e8f0;
    }
    
    .selected-pharmacy {
      border: 2px solid #3b82f6;
      box-shadow: 0 0 20px rgba(59, 130, 246, 0.2);
    }
    
    .loading-spinner {
      border: 4px solid rgba(59, 130, 246, 0.2);
      border-left: 4px solid #3b82f6;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .search-input {
      border-radius: 14px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      background: rgba(75, 165, 98, 0.95);
      border: 1px solid #e2e8f0;
    }
    
    .search-input:focus {
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      border-color:rgb(48, 107, 179);
    }
    
    .search-btn {
      border-radius: 14px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
      background: #3b82f6;
      color: white;
      font-weight: 500;
    }
    
    .search-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
      background: #2563eb;
    }
    
    .pharmacy-tag {
      border-radius: 12px;
      font-size: 12px;
      padding: 4px 12px;
    }
    
    .info-badge {
      display: inline-flex;
      align-items: center;
      background: rgba(186, 189, 4, 0.77);
      backdrop-filter: blur(5px);
      color: white;
      padding: 8px 16px;
      border-radius: 50px;
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 15px;
      border: 1px solid rgba(10, 3, 3, 0.84);
    }
    
    .section-title {
      position: relative;
      padding-left: 24px;
      color: #1e293b;
    }
    
    .section-title::before {
      content: "";
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      height: 28px;
      width: 5px;
      background:rgb(216, 220, 226);
      border-radius: 4px;
    }
    
    .floating-info {
      position: absolute;
      bottom: 20px;
      left: 20px;
      z-index: 10;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 16px;
      padding: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      max-width: 280px;
      border: 1px solid #e2e8f0;
    }
    
    .pill-icon {
      background: linear-gradient(135deg,rgb(194, 207, 226) 0%,rgba(12, 180, 62, 0.81) 100%);
    }
    
    .medicine-pattern {
      position: absolute;
      top: 20px;
      right: 20px;
      width: 200px;
      height: 200px;
      background: url('https://images.unsplash.com/photo-1516549655169-df83a0774514?q=80&w=2070&auto=format&fit=crop') center/cover;
      opacity: 0.01;
      z-index: 0;
      animation: float 6s ease-in-out infinite;
    }
    
    @keyframes float {
      0% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-20px) rotate(5deg); }
      100% { transform: translateY(0px) rotate(0deg); }
    }
    
    .nav-shadow {
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
    }
    
    .footer-bg {
      background: rgba(15, 23, 42, 0.95);
      backdrop-filter: blur(10px);
    }
    
    .results-container {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 16px;
      padding: 20px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }
    
    .search-container {
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      border: 1px solid #e2e8f0;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(5px);
    }
    
    .bg-texture {
      background-image: url('https://www.transparenttextures.com/patterns/brushed-alum.png');
      opacity: 0.03;
    }
  </style>
</head>
<body>
  <div class="min-h-screen flex flex-col">
    <!-- Navigation -->
    <nav class="nav-shadow py-3 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-lg pill-icon flex items-center justify-center">
            <i class="fas fa-pills text-white text-xl"></i>
          </div>
          <span class="text-2xl font-bold text-slate-800">
            MedFinder <span class="text-blue-600">መድሃኒት አፋላጊ</span>
          </span>
        </div>
        <div class="flex items-center space-x-6">
          <a href="adminlogin.php" class="flex items-center text-slate-600 hover:text-blue-600 transition-colors">
            <i class="fas fa-lock-open mr-2"></i>Admin Portal
          </a>
          <a href="registration.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-full 
            transition-all flex items-center space-x-2 shadow-md hover:shadow-blue-200">
            <i class="fas fa-user-plus"></i>
            <span>Sign Up</span>
          </a>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section py-24 relative">
      <div class="bg-texture absolute inset-0"></div>
      <div class="medicine-pattern"></div>
      <div class="hero-overlay absolute inset-0"></div>
      
      <div class="max-w-7xl mx-auto px-4 relative z-10">
        <div class="text-center mb-12">
          <h1 class="text-4xl md:text-5xl font-bold mb-4 text-white">
            Find Medicine in <span class="text-blue-200">Addis Ababa</span>
          </h1>
          <p class="text-xl text-blue-100 max-w-2xl mx-auto">
            Locate pharmacies with the medicine you need in real-time across Ethiopia's capital
          </p>
        </div>
        
        <!-- Search Section -->
        <div class="max-w-3xl mx-auto search-container rounded-2xl p-2">
          <div class="relative">
            <input id="searchInput" type="text" 
              placeholder="Search medicine or generic name (e.g., Paracetamol 500mg)"
              class="w-full px-6 py-5 search-input border-0 focus:ring-0 placeholder-slate-400 text-slate-700 text-lg">
            <button id="searchBtn" class="absolute right-2 top-2 text-white 
                  px-8 py-4 rounded-xl search-btn flex items-center space-x-2">
              <i class="fas fa-search"></i>
              <span class="font-medium">Search</span>
            </button>
          </div>
        </div>
        
        <div class="mt-8 flex justify-center gap-4 flex-wrap">
          <div class="info-badge">
            <i class="fas fa-map-marker-alt mr-2"></i> Real-time location tracking
          </div>
          <div class="info-badge">
            <i class="fas fa-pills mr-2"></i> 500+ medicines in database
          </div>
          <div class="info-badge">
            <i class="fas fa-clock mr-2"></i> 24/7 availability status
          </div>
        </div>
      </div>
    </div>

    <!-- Map and Results Section -->
    <div class="max-w-7xl mx-auto px-4 py-12 flex-1">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Map Column -->
        <div class="lg:col-span-2">
          <div class="relative">
            <div id="map" class="w-full"></div>
            <div class="floating-info">
              <h3 class="font-bold text-lg text-slate-800 mb-2">Map Guide</h3>
              <div class="flex items-center mb-2">
                <div class="w-4 h-4 rounded-full bg-blue-500 mr-2"></div>
                <span class="text-sm">Your Location</span>
              </div>
              <div class="flex items-center mb-2">
                <div class="w-4 h-4 rounded-full bg-green-500 mr-2"></div>
                <span class="text-sm">Medicine Available</span>
              </div>
              <div class="flex items-center">
                <div class="w-4 h-4 rounded-full bg-red-500 mr-2"></div>
                <span class="text-sm">Out of Stock</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Results Column -->
        <div class="results-container">
          <h2 class="text-2xl font-bold text-slate-800 mb-6 section-title">
            Available Pharmacies
          </h2>
          
          <div id="results" class="space-y-6">
            <div class="text-center py-12 rounded-2xl bg-slate-50">
              <div class="flex justify-center mb-4">
                <div class="loading-spinner"></div>
              </div>
              <p class="text-slate-600">Search for medicine to find nearby pharmacies</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="footer-bg text-white py-12">
      <div class="max-w-7xl mx-auto px-4 grid md:grid-cols-4 gap-8">
        <div class="space-y-4">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-lg pill-icon flex items-center justify-center">
              <i class="fas fa-pills text-white text-xl"></i>
            </div>
            <span class="text-xl font-bold text-white">MedFinder መድሃኒት አፋላጊ</span>
          </div>
          <p class="text-slate-300">24/7 Medicine Availability Tracking System</p>
          <div class="flex space-x-4">
            <a href="#" class="p-2 bg-white/10 rounded-full hover:bg-white/20"><i class="fab fa-facebook"></i></a>
            <a href="https://t.me/Stoic_waynen3" target="_blank" class="p-2 bg-white/10 rounded-full hover:bg-white/20"><i class="fab fa-telegram"></i></a>
            <a href="#" class="p-2 bg-white/10 rounded-full hover:bg-white/20"><i class="fab fa-twitter"></i></a>
          </div>
        </div>
        <div>
          <h4 class="text-lg font-bold mb-4">Quick Links</h4>
          <ul class="space-y-3">
            <li><a href="#about" class="text-slate-300 hover:text-white flex items-center"><i class="fas fa-chevron-right mr-2 text-sm text-blue-400"></i>About Us</a></li>
            <li><a href="privacy-policy.html" class="text-slate-300 hover:text-white flex items-center"><i class="fas fa-chevron-right mr-2 text-sm text-blue-400"></i>Privacy Policy</a></li>
            <li><a href="#" class="text-slate-300 hover:text-white flex items-center"><i class="fas fa-chevron-right mr-2 text-sm text-blue-400"></i>FAQs</a></li>
          </ul>
        </div>
        <div>
          <h4 class="text-lg font-bold mb-4">Contact</h4>
          <ul class="space-y-3">
            <li class="flex items-center text-slate-300"><i class="fas fa-phone mr-3 text-blue-400"></i>+251 944 113 030</li>
            <li class="flex items-center text-slate-300"><i class="fas fa-envelope mr-3 text-blue-400"></i>info@medfinder.com</li>
            <li class="flex items-start text-slate-300">
              <i class="fas fa-map-marker-alt mr-3 mt-1 text-blue-400"></i>
              <span>Bole Subcity, Addis Ababa, Ethiopia</span>
            </li>
          </ul>
        </div>
      </div>
      <div class="max-w-7xl mx-auto px-4 mt-12 pt-8 border-t border-slate-700 text-center text-slate-400">
        <p>© 2025 MedFinder መድሃኒት አፋላጊ. All rights reserved. Designed with ❤️ in Addis Ababa</p>
      </div>
    </footer>
  </div>

  <script>
    // Global variables
    let map;
    let medicineMarkers = [];
    let userMarker = null;
    let selectedPharmacy = null;
    let userLocation = null;

    // Initialize map
    function initMap() {
      map = new maplibregl.Map({
        container: 'map',
        style: {
          version: 8,
          sources: {
            'osm': {
              type: 'raster',
              tiles: ['https://a.tile.openstreetmap.org/{z}/{x}/{y}.png'],
              tileSize: 256,
              attribution: '&copy; OpenStreetMap contributors'
            }
          },
          layers: [{
            id: 'osm-tiles',
            type: 'raster',
            source: 'osm',
            minzoom: 0,
            maxzoom: 19
          }]
        },
        center: [38.7578, 9.0301], // Addis Ababa coordinates
        zoom: 13
      });
      
      // Add navigation controls
      map.addControl(new maplibregl.NavigationControl());
    }

    // Show user location on map
    function showUserLocation(lat, lng) {
      if (userMarker) userMarker.remove();

      userLocation = { lat, lng };

      userMarker = new maplibregl.Marker({ 
        color: '#3b82f6',
        scale: 1.2
      })
        .setLngLat([lng, lat])
        .setPopup(new maplibregl.Popup().setHTML(`
          <div class="font-bold text-blue-600">
            <i class="fas fa-user-circle mr-1"></i>Your Location
          </div>
        `))
        .addTo(map);

      map.flyTo({ center: [lng, lat], zoom: 14 });
    }

    // Clear existing medicine markers
    function clearMedicineMarkers() {
      medicineMarkers.forEach(marker => marker.remove());
      medicineMarkers = [];
    }

    // Add markers to map for pharmacies
    function addMarkersToMap(pharmacies) {
      clearMedicineMarkers();
      pharmacies.forEach(item => {
        if (!item.latitude || !item.longitude) return;

        const popupHTML = `
          <div class="min-w-[240px]">
            <div class="font-bold text-lg text-slate-800">${item.pharmacy_name}</div>
            <div class="mt-2">
              <div class="flex items-center mb-1">
                <i class="fas fa-pills text-blue-500 mr-2"></i>
                <span class="font-medium">${item.medicine_name}</span>
              </div>
              <div class="flex items-center mb-1">
                <i class="fas fa-tag text-blue-500 mr-2"></i>
                <span>ETB ${item.price}</span>
              </div>
              <div class="flex items-center mb-1">
                <i class="fas fa-${item.quantity > 0 ? 'check-circle text-green-500' : 'times-circle text-red-500'} mr-2"></i>
                <span>${item.quantity > 0 ? 'In Stock' : 'Out of Stock'}</span>
              </div>
              <div class="flex items-center mb-1">
                <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i>
                <span>${item.location}</span>
              </div>
              <div class="flex items-center">
                <i class="fas fa-phone text-blue-500 mr-2"></i>
                <span>${item.phone}</span>
              </div>
            </div>
          </div>
        `;

        const marker = new maplibregl.Marker({ 
          color: item.quantity > 0 ? '#10b981' : '#ef4444',
          scale: 1.1
        })
        .setLngLat([item.longitude, item.latitude])
        .setPopup(new maplibregl.Popup().setHTML(popupHTML))
        .addTo(map);

        // Add click event to select pharmacy
        marker.getElement().addEventListener('click', () => {
          selectPharmacy(item);
        });

        medicineMarkers.push(marker);
      });
    }

    // Select a pharmacy
    function selectPharmacy(pharmacy) {
      // Update UI
      document.querySelectorAll('.pharmacy-card').forEach(card => {
        card.classList.remove('selected-pharmacy');
      });
      document.getElementById(`pharmacy-${pharmacy.id}`).classList.add('selected-pharmacy');
      
      // Fly to the selected pharmacy
      map.flyTo({
        center: [pharmacy.longitude, pharmacy.latitude],
        zoom: 16
      });
    }

    // Calculate distance between user and pharmacy
    function calculateDistance(lat1, lon1, lat2, lon2) {
      const R = 6371; // Earth radius in km
      const dLat = deg2rad(lat2 - lat1);
      const dLon = deg2rad(lon2 - lon1);
      const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
      return R * c; // Distance in km
    }

    function deg2rad(deg) {
      return deg * (Math.PI/180);
    }

    // Render pharmacy results
    function renderResults(data) {
      const resultContainer = document.getElementById('results');
      if (!Array.isArray(data) || data.length === 0) {
        resultContainer.innerHTML = `
          <div class="bg-white rounded-2xl shadow-md p-8 text-center">
            <div class="text-5xl text-blue-500 mb-4">
              <i class="fas fa-pills"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">No Pharmacies Found</h3>
            <p class="text-slate-600">We couldn't find any pharmacies with the medicine you searched for.</p>
          </div>
        `;
        return;
      }

      resultContainer.innerHTML = '';
      data.forEach(item => {
        const distance = userLocation ? 
          calculateDistance(
            userLocation.lat, userLocation.lng,
            item.latitude, item.longitude
          ).toFixed(1) : 'N/A';
        
        resultContainer.innerHTML += `
          <div id="pharmacy-${item.id}" class="pharmacy-card" data-id="${item.id}">
            <div class="p-6">
              <div class="flex justify-between items-start">
                <div>
                  <div class="flex items-center">
                    <h3 class="text-xl font-bold text-slate-900">${item.pharmacy_name}</h3>
                    <span class="pharmacy-tag ml-3 ${item.quantity > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                      ${item.quantity > 0 ? 'In Stock' : 'Out of Stock'}
                    </span>
                  </div>
                  <p class="text-slate-600 text-sm mt-1 flex items-center">
                    <i class="fas fa-map-marker-alt mr-2 text-blue-500"></i>${item.location}
                  </p>
                  <p class="text-xs text-slate-500 mt-1 flex items-center">
                    <i class="fas fa-phone mr-2 text-blue-500"></i>${item.phone}
                  </p>
                </div>
                <div class="text-right">
                  <div class="text-lg font-bold text-blue-600">${distance} km</div>
                  <div class="text-xs text-slate-500">Distance</div>
                </div>
              </div>
              <div class="mt-4 pt-4 border-t border-slate-100">
                <div class="flex justify-between items-center">
                  <div>
                    <strong class="text-slate-700">${item.medicine_name}</strong>
                    <p class="text-blue-600 font-bold">ETB ${item.price}</p>
                  </div>
                  <div class="text-sm text-slate-500">
                    ${item.quantity} units available
                  </div>
                </div>
              </div>
            </div>
          </div>`;
      });

      // Add event listeners to pharmacy cards
      document.querySelectorAll('.pharmacy-card').forEach(card => {
        card.addEventListener('click', () => {
          const id = card.dataset.id;
          if (!id) return;
          
          const pharmacy = data.find(p => p.id == id);
          if (pharmacy) {
            selectPharmacy(pharmacy);
          }
        });
      });
    }

    // Search medicines in the database
    async function searchMedicines(query, lat = null, lng = null) {
      const resultContainer = document.getElementById('results');
      resultContainer.innerHTML = `
        <div class="bg-white rounded-2xl shadow-md p-8">
          <div class="flex justify-center mb-4">
            <div class="loading-spinner"></div>
          </div>
          <p class="text-slate-600 text-center">Searching pharmacies for "${query}"...</p>
        </div>
      `;
      
      try {
        // Call to your backend API
        const response = await fetch(`search.php?q=${encodeURIComponent(query)}&lat=${lat}&lng=${lng}`);
        const data = await response.json();
        
        // Process the data from your database
        renderResults(data);
        addMarkersToMap(data);
        
      } catch (err) {
        console.error('Search error:', err);
        resultContainer.innerHTML = `
          <div class="bg-white rounded-2xl shadow-md p-8 text-center">
            <div class="text-5xl text-red-500 mb-4">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">Error Loading Results</h3>
            <p class="text-slate-600">Please check your connection and try again.</p>
            <button id="retryBtn" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-full transition">
              Retry Search
            </button>
          </div>
        `;
        
        document.getElementById('retryBtn').addEventListener('click', () => {
          searchMedicines(query, lat, lng);
        });
      }
    }

    // Initialize the application
    document.addEventListener('DOMContentLoaded', () => {
      initMap();
      const searchBtn = document.getElementById('searchBtn');
      const searchInput = document.getElementById('searchInput');
      
      // Focus on search input on page load
      searchInput.focus();
      
      searchBtn.addEventListener('click', () => {
        const query = searchInput.value.trim();
        if (!query) return;
        
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(
            pos => {
              const lat = pos.coords.latitude;
              const lng = pos.coords.longitude;
              showUserLocation(lat, lng);
              searchMedicines(query, lat, lng);
            },
            () => {
              // If location access denied, search without coordinates
              searchMedicines(query);
            }
          );
        } else {
          searchMedicines(query);
        }
      });

      searchInput.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') searchBtn.click();
      });
    });
  </script>
</body>
</html>