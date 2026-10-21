<?php
// Turn on errors so we don't get white screens
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// If the user is already logged in, send them straight to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Terminal - Eckose POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        /* Subtle background pattern for the terminal feel */
        .bg-terminal {
            background-color: #111827;
            background-image: radial-gradient(#1f2937 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="bg-terminal flex items-center justify-center h-screen text-gray-200">

    <!-- Login Card -->
    <div class="w-full max-w-md bg-[#1A1F2B] rounded-2xl shadow-2xl border border-gray-700 overflow-hidden relative">
        
        <!-- Top Orange Accent Bar -->
        <div class="h-2 w-full bg-orange-500"></div>

        <div class="p-8">
            <!-- Logo & Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-800 border border-gray-600 mb-4 shadow-inner">
                    <i class="fas fa-cash-register text-3xl text-orange-500"></i>
                </div>
                <h2 class="text-2xl font-bold text-white tracking-wide">Eckose POS</h2>
                <p class="text-sm text-gray-400 mt-1 uppercase tracking-widest font-semibold">Staff Terminal</p>
            </div>

            <!-- Error Message -->
            <?php if(isset($_GET['error'])): ?>
                <div class="bg-red-900 border border-red-700 text-red-300 p-3 mb-6 rounded-lg text-sm text-center flex items-center justify-center gap-2">
                    <i class="fas fa-exclamation-triangle"></i> Invalid credentials. Try again.
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form action="actions/auth_process.php" method="POST" class="space-y-5">
                <div>
                    <label for="username" class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-wide">Staff ID / Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-500"></i>
                        </div>
                        <input id="username" name="username" type="text" required autofocus 
                            class="appearance-none block w-full pl-10 pr-3 py-3 bg-[#111827] border border-gray-600 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition sm:text-sm">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-wide">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-500"></i>
                        </div>
                        <input id="password" name="password" type="password" required 
                            class="appearance-none block w-full pl-10 pr-3 py-3 bg-[#111827] border border-gray-600 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition sm:text-sm">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" name="login_btn" 
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-bold text-white bg-orange-500 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-orange-500 transition-all duration-150 ease-in-out">
                        <i class="fas fa-sign-in-alt mr-2 mt-0.5"></i> Authorize Login
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Footer Link -->
        <div class="bg-[#111827] p-4 text-center border-t border-gray-700">
            <a href="index.php" class="text-xs text-gray-500 hover:text-orange-400 transition">
                <i class="fas fa-arrow-left mr-1"></i> Return to Storefront
            </a>
        </div>
    </div>

</body>
</html>