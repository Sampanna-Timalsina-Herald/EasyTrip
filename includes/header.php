<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include_once 'config/database.php';

// Fetch user name if logged in
$user_name = '';
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_query = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $user_result = $user_query->get_result();
    
    if ($user_result && $user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $user_name = $user_data['name'];
    }
    $user_query->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Booking System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <i class="fas fa-paper-plane"></i> EasyTrip
                </a>
            </div>
            
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <nav class="main-nav" id="mainNav">
                <ul>
                    <li><a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="hotels.php" class="nav-link"><i class="fas fa-hotel"></i> Hotels & Buses</a></li>
                    <li><a href="about.php" class="nav-link"><i class="fas fa-info-circle"></i> About Us</a></li>
                    <li><a href="contact.php" class="nav-link"><i class="fas fa-envelope"></i> Contact</a></li>
                    <li><a href="request-advice.php" class="nav-link"><i class="fas fa-question-circle"></i> Get Advice</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
            <li><a href="my-bookings.php" class="nav-link"><i class="fas fa-ticket-alt"></i> My Bookings</a></li>
        <?php endif; ?>

                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="user-dropdown">
                            <a href="#" class="dropdown-toggle">
                                <div class="user-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span>Hi, <?php echo htmlspecialchars($user_name ?: 'User'); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if(isset($_SESSION['user_role'])): ?>
                                    <?php if($_SESSION['user_role'] == 'admin'): ?>
                                        <li><a href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a></li>
                                    <?php elseif($_SESSION['user_role'] == 'agent'): ?>
                                        <li><a href="travel-agent-dashboard.php"><i class="fas fa-briefcase"></i> Agent Dashboard</a></li>
                                    <?php elseif($_SESSION['user_role'] == 'hotel_owner'): ?>
                                        <li><a href="hotel-owner/dashboard.php"><i class="fas fa-hotel"></i> Hotel Dashboard</a></li>
                                    <?php elseif($_SESSION['user_role'] == 'bus_operator'): ?>
                                        <li><a href="bus-operator/dashboard.php"><i class="fas fa-bus"></i> Bus Dashboard</a></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="auth-buttons">
                            <a href="login.php" class="btn btn-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                            <a href="register.php" class="btn btn-register"><i class="fas fa-user-plus"></i> Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main>
<style>
    /* Modern Header Styles */
    .main-header {
        background-color: #fff;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        position: sticky;
        top: 0;
        z-index: 1000;
        padding: 0;
        transition: all 0.3s ease;
    }
    
    .main-header .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    /* Logo Styles */
    .logo a {
        font-size: 1.8rem;
        font-weight: 700;
        color: #3a86ff;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
    }
    
    .logo a i {
        margin-right: 8px;
        font-size: 1.6rem;
    }
    
    .logo a:hover {
        transform: translateY(-2px);
        color: #2a75e6;
    }
    
    /* Navigation Styles */
    .main-nav ul {
        display: flex;
        align-items: center;
        margin: 0;
        padding: 0;
    }
    
    .main-nav ul li {
        margin: 0 5px;
        list-style: none;
    }
    
    .nav-link {
        color: #555;
        font-weight: 500;
        padding: 10px 15px;
        border-radius: 8px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
    }
    
    .nav-link i {
        margin-right: 6px;
        font-size: 0.9rem;
    }
    
    .nav-link:hover {
        background-color: rgba(58, 134, 255, 0.1);
        color: #3a86ff;
        transform: translateY(-2px);
    }
    
    /* Button Styles */
    .auth-buttons {
        display: flex;
        gap: 10px;
    }
    
    .btn {
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
    }
    
    .btn i {
        margin-right: 6px;
    }
    
    .btn-login {
        background-color: transparent;
        color: #3a86ff;
        border: 2px solid #3a86ff;
    }
    
    .btn-login:hover {
        background-color: rgba(58, 134, 255, 0.1);
        transform: translateY(-2px);
    }
    
    .btn-register {
        background-color: #3a86ff;
        color: white;
    }
    
    .btn-register:hover {
        background-color: #2a75e6;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(58, 134, 255, 0.2);
    }
    
    /* User Dropdown Styles */
    .user-dropdown {
        position: relative;
    }
    
    .dropdown-toggle {
        display: flex;
        align-items: center;
        color: #555 !important;
        font-weight: 500;
        cursor: pointer;
        padding: 8px 15px;
        border-radius: 8px;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
        border: 1px solid #eaeaea;
    }
    
    .dropdown-toggle:hover {
        background-color: #f1f3f5;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #3a86ff;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 8px;
    }
    
    .dropdown-toggle span {
        margin: 0 8px;
        font-weight: 600;
    }
    
    .dropdown-toggle i.fa-chevron-down {
        font-size: 12px;
        transition: transform 0.3s;
    }
    
    .user-dropdown.active .dropdown-toggle i.fa-chevron-down {
        transform: rotate(180deg);
    }
    
    .dropdown-menu {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        background-color: #fff;
        box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        border-radius: 12px;
        width: 220px;
        z-index: 100;
        display: none;
        overflow: hidden;
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: all 0.3s ease;
        border: 1px solid #eaeaea;
    }
    
    .user-dropdown.active .dropdown-menu {
        display: block;
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .dropdown-menu li {
        margin: 0;
        border-bottom: 1px solid #f1f1f1;
    }
    
    .dropdown-menu li:last-child {
        border-bottom: none;
    }
    
    .dropdown-menu li a {
        padding: 12px 18px;
        display: flex;
        align-items: center;
        color: #555;
        transition: all 0.2s ease;
    }
    
    .dropdown-menu li a:hover {
        background-color: #f8f9fa;
        color: #3a86ff;
    }
    
    .dropdown-menu li a i {
        margin-right: 12px;
        width: 16px;
        text-align: center;
        color: #3a86ff;
    }
    
    /* Mobile Menu Toggle */
    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        color: #3a86ff;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 5px;
    }
    
    /* Responsive Styles */
    @media (max-width: 1024px) {
        .nav-link {
            padding: 8px 12px;
            font-size: 0.9rem;
        }
        
        .nav-link i {
            margin-right: 4px;
        }
        
        .btn {
            padding: 8px 14px;
            font-size: 0.85rem;
        }
    }
    
    @media (max-width: 900px) {
        .main-nav ul li {
            margin: 0 2px;
        }
        
        .nav-link {
            padding: 8px 10px;
        }
        
        .dropdown-toggle span {
            display: none;
        }
        
        .user-avatar {
            margin-right: 0;
        }
    }
    
    @media (max-width: 768px) {
        .mobile-menu-toggle {
            display: block;
        }
        
        .main-nav {
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            background-color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 20px;
            transform: translateY(-100%);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            height: auto;
            max-height: calc(100vh - 70px);
            overflow-y: auto;
        }
        
        .main-nav.active {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }
        
        .main-nav ul {
            flex-direction: column;
            width: 100%;
        }
        
        .main-nav ul li {
            margin: 8px 0;
            width: 100%;
        }
        
        .nav-link {
            padding: 12px 15px;
            width: 100%;
            justify-content: flex-start;
        }
        
        .auth-buttons {
            flex-direction: column;
            width: 100%;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
        }
        
        .user-dropdown {
            width: 100%;
        }
        
        .dropdown-toggle {
            width: 100%;
            justify-content: flex-start;
        }
        
        .dropdown-toggle span {
            display: block;
        }
        
        .dropdown-menu {
            position: static;
            box-shadow: none;
            width: 100%;
            margin-top: 10px;
            border-radius: 8px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // User dropdown functionality
        const userDropdown = document.querySelector('.user-dropdown');
        const dropdownToggle = document.querySelector('.dropdown-toggle');
        
        if (dropdownToggle && userDropdown) {
            // Make sure dropdown is closed initially
            userDropdown.classList.remove('active');
            
            dropdownToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                userDropdown.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (userDropdown && !userDropdown.contains(e.target)) {
                    userDropdown.classList.remove('active');
                }
            });
        }
        
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mainNav = document.getElementById('mainNav');
        
        if (mobileMenuToggle && mainNav) {
            mobileMenuToggle.addEventListener('click', function() {
                mainNav.classList.toggle('active');
                
                // Change icon based on menu state
                const icon = this.querySelector('i');
                if (mainNav.classList.contains('active')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
            
            // Close mobile menu when clicking on a link
            const navLinks = mainNav.querySelectorAll('a');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    // Only close if it's not a dropdown toggle
                    if (!this.classList.contains('dropdown-toggle')) {
                        mainNav.classList.remove('active');
                        const icon = mobileMenuToggle.querySelector('i');
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });
            });
        }
        
        // Add active class to current page link
        const currentLocation = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const linkPath = link.getAttribute('href');
            if (currentLocation.includes(linkPath) && linkPath !== 'index.php') {
                link.classList.add('active');
            } else if (currentLocation.endsWith('/') && linkPath === 'index.php') {
                link.classList.add('active');
            }
        });
    });
</script>
</main>
