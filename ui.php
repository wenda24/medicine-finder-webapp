<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedFinder - Locate Medicines in Addis Ababa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
document.querySelector('button').addEventListener('click', async () => {
    const query = document.querySelector('input[type="text"]').value.trim();
    if (!query) return;

    const res = await fetch(`search.php?q=${encodeURIComponent(query)}`);
    const data = await res.json();

    const container = document.getElementById('results');
    container.innerHTML = ''; // Clear old results

    if (data.length === 0) {
        container.innerHTML = '<p class="text-gray-600">No pharmacies found with that medicine.</p>';
        return;
    }

    data.forEach(item => {
        container.innerHTML += `
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xl font-semibold">${item.pharmacy_name}</h3>
                        ${item.verified ? `<span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm"><i class="fas fa-check-circle"></i> Verified</span>` : ''}
                    </div>
                    <p class="text-gray-600 mb-4">
                        <i class="fas fa-map-marker-alt mr-2"></i>${item.location}<br>
                        <i class="fas fa-phone mr-2"></i>${item.phone}
                    </p>
                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-medium">${item.medicine_name}</span>
                            <span class="text-blue-600">${item.quantity > 0 ? 'In Stock' : 'Out of Stock'}</span>
                        </div>
                        <span class="text-sm text-gray-500">Price: ETB ${item.price}</span>
                    </div>
                </div>
            </div>
        `;
    });
});
</script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <img url="manage.jpg" 
                         <!-- 
                         "💊">
                    <span class="text-xl font-bold text-blue-600 ml-2">MedFinder</span>
                </div>
                <div class="space-x-4">
                    <a href="adminlogin.php" class="text-gray-600 hover:text-blue-600">Admin portal</a>
                    <a href="registration.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-center">
                        Sign Up
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Search Section -->
    <div class="max-w-7xl mx-auto px-4 py-12">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                Find Medicines Near You in Addis
            </h1>
            <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-4">
                <div class="relative">
                   <input id="searchInput" type="text" 
       placeholder="Search for medicine (e.g., Paracetamol 500mg)" 
       class="w-full p-4 border-2 border-blue-200 rounded-lg pr-16">

                    <button type="button" id="searchBtn" class="absolute right-2 top-2 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
    <i class="fas fa-search"></i>
</button>

                </div>
                <div class="mt-4 flex gap-2 text-sm">
                    <span class="text-gray-600">Popular searches:</span>
                    <a href="#" class="text-blue-600">Amoxicillin</a>
                    <a href="#" class="text-blue-600">Antibiotics</a>
                    <a href="#" class="text-blue-600">Diabetes Medication</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Pharmacy Results -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <h2 class="text-2xl font-bold mb-6">Available Pharmacies</h2>
<div id="results" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6"></div>

            <!-- Pharmacy Card 1 -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadw">
                <img src="https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" 
                     alt="24 መድሃኒት ቤት" 
                     class="w-full h-48 object-cover">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xl font-semibold">24 መድሃኒት ቤት</h3>
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm">
                            <i class="fas fa-check-circle"></i> Verified
                        </span>
                    </div>
                    <p class="text-gray-600 mb-4">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        Bole 24, Addis Ababa<br>
                        <i class="fas fa-phone mr-2"></i>+251 944113030
                    </p>
                    
                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-medium">Paracetamol 500mg</span>
                            <span class="text-blue-600">In Stock</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
document.addEventListener('DOMContentLoaded', () => {
    const searchBtn = document.getElementById('searchBtn');
    const searchInput = document.getElementById('searchInput');
    const resultContainer = document.getElementById('results');

    searchBtn.addEventListener('click', async () => {
        const query = searchInput.value.trim();
        resultContainer.innerHTML = '';

        if (!query) {
            resultContainer.innerHTML = '<p class="text-gray-500">Please enter a medicine name.</p>';
            return;
        }

        try {
            const res = await fetch(`search.php?q=${encodeURIComponent(query)}`);
            const data = await res.json();

            if (!Array.isArray(data) || data.length === 0) {
                resultContainer.innerHTML = '<p class="text-gray-500">No pharmacies found with that medicine.</p>';
                return;
            }

            data.forEach(item => {
                resultContainer.innerHTML += `
                    <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-xl font-semibold">${item.pharmacy_name}</h3>
                                ${item.verified ? `<span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm"><i class="fas fa-check-circle"></i> Verified</span>` : ''}
                            </div>
                            <p class="text-gray-600 mb-4">
                                <i class="fas fa-map-marker-alt mr-2"></i>${item.location}<br>
                                <i class="fas fa-phone mr-2"></i>${item.phone}
                            </p>
                            <div class="border-t pt-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-medium">${item.medicine_name}</span>
                                    <span class="text-blue-600">${item.quantity > 0 ? 'In Stock' : 'Out of Stock'}</span>
                                </div>
                                <span class="text-sm text-gray-500">Price: ETB ${item.price}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
        } catch (err) {
            console.error('Fetch error:', err);
            resultContainer.innerHTML = '<p class="text-red-500">Something went wrong while fetching results.</p>';
        }
    });
});
</script>

</body>
</html>