<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management | Haiphong Life</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="robots" content="follow, noindex"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .nobifashion_order_manage {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(255, 51, 102, 0.15);
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-processing {
            background-color: #FFF4E5;
            color: #FF9500;
        }
        
        .status-shipped {
            background-color: #E5F6FF;
            color: #0077CC;
        }
        
        .status-delivered {
            background-color: #E6F7EE;
            color: #00A854;
        }
        
        .status-cancelled {
            background-color: #FFEBEE;
            color: #FF3D00;
        }
        
        .pagination .active {
            background-color: #FF3366;
            color: white;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #FF3366;
            border-radius: 4px;
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class=.nobifashion_order_manage min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-[#FF3366] flex items-center justify-center text-white font-bold text-xl">HL</div>
                    <h1 class="ml-3 text-xl font-semibold text-gray-900">Haiphong Life Orders</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text" placeholder="Search orders..." class="pl-10 pr-4 py-2 border rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-[#FF3366] focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                    </div>
                    <div class="relative">
                        <button class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-300">
                            <i class="fas fa-bell"></i>
                            <span class="absolute top-0 right-0 w-2 h-2 bg-[#FF3366] rounded-full"></span>
                        </button>
                    </div>
                    <div class="flex items-center">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Profile" class="w-8 h-8 rounded-full">
                        <span class="ml-2 text-sm font-medium text-gray-700 hidden md:inline">Sarah Johnson</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <!-- Filters and Actions -->
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">
                    <h2 class="text-lg font-semibold text-gray-800">Order History</h2>
                    <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">128 orders</span>
                </div>
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                    <div class="relative">
                        <select class="appearance-none bg-white border border-gray-300 rounded-md pl-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF3366] focus:border-transparent">
                            <option>All Status</option>
                            <option>Processing</option>
                            <option>Shipped</option>
                            <option>Delivered</option>
                            <option>Cancelled</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-2.5 text-gray-400 text-xs"></i>
                    </div>
                    <div class="relative">
                        <select class="appearance-none bg-white border border-gray-300 rounded-md pl-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF3366] focus:border-transparent">
                            <option>Last 30 days</option>
                            <option>Last 3 months</option>
                            <option>Last 6 months</option>
                            <option>Last year</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-2.5 text-gray-400 text-xs"></i>
                    </div>
                    <button class="bg-[#FF3366] hover:bg-[#E62E5C] text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        <i class="fas fa-download mr-2"></i> Export
                    </button>
                </div>
            </div>

            <!-- Order Cards -->
            <div class="grid grid-cols-1 gap-6">
                <!-- Order Card 1 -->
                <div class="order-card bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden transition-all duration-200 animate-fade-in" style="animation-delay: 0.1s">
                    <div class="p-5">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div class="mb-4 md:mb-0">
                                <div class="flex items-center">
                                    <h3 class="text-lg font-semibold text-gray-800">Order #HL-2023-0875</h3>
                                    <span class="status-badge status-processing ml-3">Processing</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Placed on June 12, 2023 at 10:45 AM</p>
                            </div>
                            <div class="flex items-center">
                                <span class="text-lg font-bold text-gray-900">$128.50</span>
                                <button class="ml-4 text-[#FF3366] hover:text-[#E62E5C]">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <div class="flex -space-x-2">
                                    <img src="https://via.placeholder.com/40" alt="Product" class="w-10 h-10 rounded-full border-2 border-white">
                                    <img src="https://via.placeholder.com/40/FF3366" alt="Product" class="w-10 h-10 rounded-full border-2 border-white">
                                    <img src="https://via.placeholder.com/40/00A854" alt="Product" class="w-10 h-10 rounded-full border-2 border-white">
                                    <div class="w-10 h-10 rounded-full border-2 border-white bg-gray-100 flex items-center justify-center text-xs font-semibold text-gray-500">+2</div>
                                </div>
                                <div class="flex space-x-3">
                                    <button class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium transition-colors duration-200">
                                        <i class="fas fa-truck mr-1"></i> Track
                                    </button>
                                    <button class="px-3 py-1.5 bg-[#FF3366] hover:bg-[#E62E5C] text-white rounded-md text-sm font-medium transition-colors duration-200">
                                        <i class="fas fa-shopping-bag mr-1"></i> View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Card 2 -->
                <div class="order-card bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden transition-all duration-200 animate-fade-in" style="animation-delay: 0.2s">
                    <div class="p-5">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div class="mb-4 md:mb-0">
                                <div class="flex items-center">
                                    <h3 class="text-lg font-semibold text-gray-800">Order #HL-2023-0874</h3>
                                    <span class="status-badge status-shipped ml-3">Shipped</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Placed on June 10, 2023 at 3:22 PM</p>
                            </div>
                            <div class="flex items-center">
                                <span class="text-lg font-bold text-gray-900">$89.99</span>
                                <button class="ml-4 text-[#FF3366] hover:text-[#E62E5C]">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <div class="flex -space-x-2">
                                    <img src="https://via.placeholder.com/40/0077CC" alt="Product" class="w-10 h-10 rounded-full border-2 border-white">
                                    <img src="https://via.placeholder.com/40/FF9500" alt="Product" class="w-10 h-10 rounded-full border-2 border-white">
                                </div>
                                <div class="flex space-x-3">
                                    <button class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium transition-colors duration-200">
                                        <i class="fas fa-truck mr-1"></i> Track
                                    </button>
                                    <button class="px-3 py-1.5 bg-[#FF3366] hover:bg-[#E62E5C] text-white rounded-md text-sm font-medium transition-colors duration-200">
                                        <i class="fas fa-shopping-bag mr-1"></i> View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Card 3 -->
                <div class="order-card bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden transition-all duration-200 animate-fade-in" style="animation-delay: 0.3s">
                    <div class="p-5">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div class="mb-4 md:mb-0">
                                <div class="flex items-center">
                                    <h3 class="text-lg font-semibold text-gray-800">Order #HL-2023-0873</h3>
                                    <span class="status-badge status-delivered ml-3">Delivered</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Placed on June 8, 2023 at 9:15 AM</p>
                            </div>
                            <div class="flex items-center">
                                <span class="text-lg font-bold text-gray-900">$245.75</span>
                                <button class="ml-4 text-[#FF3366] hover:text-[#E62E5C]">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <div class="flex -space-x-2">
                                    <img src="https://via.placeholder.com/40/00A854" alt="Product" class="w-10 h-10 rounded-full border-2 border-white">
                                    <img src="https://via.placeholder.com/40/FF3366" alt="Product" class="w-10 h-10 rounded-full border-2 border-white">
                                    <img src="https://via.placeholder.com/40/0077CC" alt="Product" class="w-10 h-10 rounded-full border-2 border-white">
                                </div>
                                <div class="flex space-x-3">
                                    <button class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium transition-colors duration-200">
                                        <i class="fas fa-truck mr-1"></i> Track
                                    </button>
                                    <button class="px-3 py-1.5 bg-[#FF3366] hover:bg-[#E62E5C] text-white rounded-md text-sm font-medium transition-colors duration-200">
                                        <i class="fas fa-shopping-bag mr-1"></i> View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Card 4 -->
                <div class="order-card bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden transition-all duration-200 animate-fade-in" style="animation-delay: 0.4s">
                    <div class="p-5">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div class="mb-4 md:mb-0">
                                <div class="flex items-center">
                                    <h3 class="text-lg font-semibold text-gray-800">Order #HL-2023-0872</h3>
                                    <span class="status-badge status-cancelled ml-3">Cancelled</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Placed on June 5, 2023 at 2:30 PM</p>
                            </div>
                            <div class="flex items-center">
                                <span class="text-lg font-bold text-gray-900">$67.20</span>
                                <button class="ml-4 text-[#FF3366] hover:text-[#E62E5C]">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <div class="flex -space-x-2">
                                    <img src="https://via.placeholder.com/40/FF3D00" alt="Product" class="w-10 h-10 rounded-full border-2 border-white">
                                </div>
                                <div class="flex space-x-3">
                                    <button class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium transition-colors duration-200">
                                        <i class="fas fa-truck mr-1"></i> Track
                                    </button>
                                    <button class="px-3 py-1.5 bg-[#FF3366] hover:bg-[#E62E5C] text-white rounded-md text-sm font-medium transition-colors duration-200">
                                        <i class="fas fa-shopping-bag mr-1"></i> View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-8 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Showing <span class="font-medium">1</span> to <span class="font-medium">4</span> of <span class="font-medium">128</span> orders
                </div>
                <div class="flex space-x-1 pagination">
                    <button class="w-8 h-8 rounded-md flex items-center justify-center text-gray-500 hover:bg-gray-100">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="w-8 h-8 rounded-md flex items-center justify-center bg-[#FF3366] text-white">1</button>
                    <button class="w-8 h-8 rounded-md flex items-center justify-center text-gray-700 hover:bg-gray-100">2</button>
                    <button class="w-8 h-8 rounded-md flex items-center justify-center text-gray-700 hover:bg-gray-100">3</button>
                    <span class="w-8 h-8 flex items-center justify-center">...</span>
                    <button class="w-8 h-8 rounded-md flex items-center justify-center text-gray-700 hover:bg-gray-100">8</button>
                    <button class="w-8 h-8 rounded-md flex items-center justify-center text-gray-500 hover:bg-gray-100">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Simple animation for order cards
        document.addEventListener('DOMContentLoaded', function() {
            const orderCards = document.querySelectorAll('.order-card');
            
            orderCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
            
            // Status filter functionality (example)
            const statusFilter = document.querySelector('select:nth-of-type(1)');
            statusFilter.addEventListener('change', function() {
                const selectedStatus = this.value.toLowerCase();
                orderCards.forEach(card => {
                    const cardStatus = card.querySelector('.status-badge').textContent.toLowerCase();
                    if (selectedStatus === 'all status' || cardStatus === selectedStatus) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
            
            // Time filter functionality (example)
            const timeFilter = document.querySelector('select:nth-of-type(2)');
            timeFilter.addEventListener('change', function() {
                // In a real app, this would filter orders by date
                console.log(`Filtering by: ${this.value}`);
            });
            
            // Export button functionality
            const exportBtn = document.querySelector('button:last-of-type');
            exportBtn.addEventListener('click', function() {
                alert('Exporting order data...');
                // In a real app, this would trigger a download
            });
        });
    </script>
</body>
</html>