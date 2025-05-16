<?php
// Basic PHP variables for dynamic content
$site_title = "HealthCare Pro - Appointment System";
$current_year = date('Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 25% 25%, rgba(0, 123, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(0, 191, 255, 0.1) 0%, transparent 50%);
            z-index: -1;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 123, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
            transition: all 0.3s ease;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #007bff, #00bfff, #87cefa);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
            text-shadow: 2px 2px 4px rgba(0, 123, 255, 0.3);
            position: relative;
        }

        .logo::after {
            content: '⚕️';
            margin-left: 10px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            position: relative;
        }

        .nav-links li {
            position: relative;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-links a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 123, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .nav-links a:hover::before {
            left: 100%;
        }

        .nav-links a:hover {
            color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        .btn {
            background: linear-gradient(135deg, #007bff, #00bfff);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            background: linear-gradient(135deg, #0056b3, #007bff);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.5);
        }

        .btn-outline {
            background: transparent;
            color: #007bff;
            border: 2px solid #007bff;
            box-shadow: inset 0 0 0 0 #007bff;
            transition: all 0.3s ease;
        }

        .btn-outline:hover {
            background: #007bff;
            color: white;
            box-shadow: inset 300px 0 0 0 #007bff;
        }

        /* Hero Section */
        .hero {
            background:
                linear-gradient(135deg, #007bffcc 0%, #00bfffcc 50%, #87cefacc 100%),
                url('imageshero-bg.jpg') center center/cover no-repeat;
            color: white;
            min-height: 100vh;
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
            padding: 0;
        }

        .hero .container {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .hero h1 {
            font-size: 4vw;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: slideInUp 1s ease;
        }

        .hero p {
            font-size: 1.5vw;
            margin-bottom: 2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
            animation: slideInUp 1s ease 0.3s both;
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            margin-top: 2rem;
            animation: slideInUp 1s ease 0.6s both;
        }

        .hero-decoration {
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .hero-decoration:nth-child(1) {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .hero-decoration:nth-child(2) {
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }

        /* Features Section */
        .features {
            padding: 5rem 0;
            background: linear-gradient(180deg, #f8fef8 0%, #ffffff 100%);
            position: relative;
        }

        .features::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120"><path d="M600,112.77C268.63,112.77,0,65.52,0,12.5S268.63,0,600,0s600,47.77,600,100.5S931.37,112.77,600,112.77Z" fill="rgba(0,123,255,0.1)"/></svg>');
            background-size: cover;
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #007bff;
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #007bff, #00bfff);
            border-radius: 2px;
        }

        .section-subtitle {
            text-align: center;
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 4rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 123, 255, 0.15);
            text-align: center;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 123, 255, 0.1);
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 123, 255, 0.05), transparent);
            transition: left 0.6s ease;
        }

        .feature-card:hover::before {
            left: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 123, 255, 0.25);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e6f3ff, #cce5ff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: #007bff;
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.2);
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: rotate(360deg) scale(1.1);
            background: linear-gradient(135deg, #007bff, #00bfff);
            color: white;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
            font-weight: 600;
        }

        .feature-card p {
            color: #666;
            line-height: 1.7;
            font-size: 1rem;
        }

        /* About Section */
        .about {
            padding: 5rem 0;
            background: white;
            position: relative;
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .about-text h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: #007bff;
            line-height: 1.2;
        }

        .about-text p {
            margin-bottom: 1.5rem;
            color: #666;
            line-height: 1.8;
            font-size: 1.1rem;
        }

        .about-image {
            position: relative;
        }

        .about-image::before {
            content: '';
            position: absolute;
            top: -20px;
            left: -20px;
            right: 20px;
            bottom: 20px;
            background: linear-gradient(135deg, #007bff, #00bfff);
            border-radius: 20px;
            z-index: -1;
            opacity: 0.1;
        }

        .about-image-content {
            background: linear-gradient(135deg, #e6f3ff, #cce5ff);
            height: 400px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #007bff;
            font-size: 1.5rem;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(0, 123, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .about-image-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(0,123,255,0.1)"/><circle cx="80" cy="40" r="2" fill="rgba(0,123,255,0.1)"/><circle cx="40" cy="80" r="2" fill="rgba(0,123,255,0.1)"/><circle cx="60" cy="60" r="2" fill="rgba(0,123,255,0.1)"/></svg>');
            animation: float 8s ease-in-out infinite reverse;
        }

        /* Contact Section */
        .contact {
            background: linear-gradient(135deg, #f0f8ff 0%, #e6f3ff 100%);
            padding: 5rem 0;
            position: relative;
        }

        .contact::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120"><path d="M600,112.77C268.63,112.77,0,65.52,0,12.5S268.63,0,600,0s600,47.77,600,100.5S931.37,112.77,600,112.77Z" fill="white"/></svg>');
            background-size: cover;
            transform: rotate(180deg);
        }

        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            margin-top: 3rem;
        }

        .contact-form {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 123, 255, 0.15);
            position: relative;
            overflow: hidden;
        }

        .contact-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #007bff, #00bfff, #87cefa);
        }

        .form-group {
            margin-bottom: 2rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.7rem;
            color: #333;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e6f3ff;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafffe;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            background: white;
        }

        .form-group textarea {
            height: 130px;
            resize: vertical;
        }

        .contact-info {
            position: relative;
        }

        .contact-info h3 {
            color: #007bff;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            color: #666;
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.1);
            transition: all 0.3s ease;
        }

        .contact-item:hover {
            transform: translateX(10px);
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.15);
        }

        .contact-item span:first-child {
            margin-right: 1rem;
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #e6f3ff, #cce5ff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #0056b3, #007bff);
            color: white;
            text-align: center;
            padding: 3rem 0;
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="80" r="1" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: float 10s ease-in-out infinite;
        }

        .footer-content {
            position: relative;
            z-index: 2;
        }

        .footer p {
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .footer a {
            color: #87cefa;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer a:hover {
            color: #00bfff;
            text-shadow: 0 0 10px rgba(0, 191, 255, 0.5);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero {
                min-height: 80vh;
            }
            .hero h1 {
                font-size: 2rem;
            }
            .hero p {
                font-size: 1rem;
            }
            .hero .container {
                height: 80vh;
            }
            .about-content,
            .contact-content {
                grid-template-columns: 1fr;
            }

            .stats {
                justify-content: space-around;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 2rem;
            }
        }

        /* Loading Animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #007bff, #00bfff);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>
    <!-- Navbar -->
    <nav class="bg-white shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="landingpage.php" class="flex items-center">
                        <i class="fas fa-hospital text-[#007bff] text-2xl mr-2"></i>
                        <span class="text-xl font-bold text-[#007bff]">VitalHealth</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="landingpage.php" class="text-[#007bff] font-medium hover:text-[#00bfff] transition-colors duration-300">Home</a>
                    <a href="guest/index.php" class="text-gray-600 hover:text-[#007bff] transition-colors duration-300">Doctors</a>
                    <a href="index.php" class="bg-gradient-to-r from-[#007bff] to-[#00bfff] text-white px-4 py-2 rounded-lg hover:from-[#0056b3] hover:to-[#007bff] transition-all duration-300 shadow-md hover:shadow-lg">Login</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Add padding to body to account for fixed navbar -->
    <div class="pt-16">
        <!-- Loading Screen -->
        <div class="loading" id="loading">
            <div class="loading-spinner"></div>
        </div>

        <!-- Hero Section -->
        <section id="home" class="hero">
            <div class="hero-decoration"></div>
            <div class="hero-decoration"></div>
            <div class="container">
                <div class="hero-content">
                    <h1>Book Your Healthcare Appointments Online</h1>
                    <p>Experience seamless appointment scheduling with our advanced healthcare management system. Connect with top doctors and specialists in your area with our state-of-the-art platform.</p>
                    <div class="hero-buttons">
                        <a href="#book-now" class="btn">Get Started</a>
                        <a href="#features" class="btn btn-outline">Learn More</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features">
            <div class="container">
                <h2 class="section-title">Why Choose Our System?</h2>
                <p class="section-subtitle">Comprehensive features designed for modern healthcare management with cutting-edge technology</p>
                
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">📅</div>
                        <h3>Easy Scheduling</h3>
                        <p>Book appointments instantly with our user-friendly interface. View available slots and choose the perfect time for your visit with real-time availability.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">👨‍⚕️</div>
                        <h3>Qualified Doctors</h3>
                        <p>Access a network of certified healthcare professionals and specialists across various medical fields with verified credentials and reviews.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">📋</div>
                        <h3>Medical Records</h3>
                        <p>Secure access to your medical history and appointment records. Share information easily with healthcare providers through encrypted channels.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="about">
            <div class="container">
                <div class="about-content">
                    <div class="about-text">
                        <h2>Revolutionizing Healthcare Access</h2>
                        <p>Our Healthcare Appointment System has been serving patients and healthcare providers, making medical care more accessible and efficient through innovative technology solutions.</p>
                        <p>We believe that quality healthcare should be easily accessible to everyone. Our platform connects patients with healthcare providers seamlessly, reducing wait times and improving the overall healthcare experience with AI-powered matching and smart scheduling.</p>
                    </div>
                    <div class="about-image">
                        <div class="about-image-content">
                            <img src="images/healthcare-innovation.jpg" alt="Healthcare Innovation" style="max-width:80%; max-height:80%; border-radius: 15px; box-shadow: 0 4px 16px rgba(0,123,255,0.08);">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="contact">
            <div class="container">
                <h2 class="section-title">Get In Touch</h2>
                <p class="section-subtitle">Have questions or feedback? We're here to help you get started with our comprehensive support team</p>
                
                <div class="contact-content">
                    <form class="contact-form" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" placeholder="How can we help you?" required></textarea>
                        </div>
                        <button type="submit" class="btn" style="width: 100%;">Send Message</button>
                    </form>
                    
                    <div class="contact-info">
                        <h3>Contact Information</h3>
                        <div class="contact-item">
                            <span>📍</span>
                            <span>123 Medical Center Drive, Lipa City, HC 12345</span>
                        </div>
                        <div class="contact-item">
                            <span>📞</span>
                            <span>(555) 123-4567</span>
                        </div>
                        <div class="contact-item">
                            <span>✉️</span>
                            <span>info@healthcarepro.com</span>
                        </div>
                        <div class="contact-item">
                            <span>🕒</span>
                            <span>24/7 Support Available</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <div class="footer-content">
                    <p>&copy; <?php echo $current_year; ?> HealthCare Pro. All rights reserved.</p>
                    <p>Designed for better healthcare management.</p>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Loading screen
        window.addEventListener('load', function() {
            const loading = document.getElementById('loading');
            setTimeout(() => {
                loading.style.opacity = '0';
                setTimeout(() => {
                    loading.style.display = 'none';
                }, 500);
            }, 1000);
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>

    <?php
    // Enhanced form processing
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = htmlspecialchars($_POST['name'] ?? '');
        $email = htmlspecialchars($_POST['email'] ?? '');
        $phone = htmlspecialchars($_POST['phone'] ?? '');
        $message = htmlspecialchars($_POST['message'] ?? '');
        
        // In a real application, you would save this to a database or send an email
        echo "<script>
            alert('Thank you for your message, $name! We will get back to you within 24 hours.');
            setTimeout(() => {
                document.querySelector('.contact-form').reset();
            }, 500);
        </script>";
    }
    ?>
</body>
</html> 