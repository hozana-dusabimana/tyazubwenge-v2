<?php
require_once 'config/config.php';
// Don't require login - this is a public page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Tyazubwenge Training Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --dark-color: #212529;
            --light-color: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }
        
        html {
            overflow-x: hidden;
            width: 100%;
        }
        
        * {
            box-sizing: border-box;
        }
        
        /* Container max-width constraints for better large screen display */
        .container-lg {
            max-width: 1320px;
            margin: 0 auto;
            padding-left: 15px;
            padding-right: 15px;
        }
        
        @media (min-width: 1400px) {
            .container-lg {
                max-width: 1320px;
            }
        }
        
        @media (min-width: 1920px) {
            .container-lg {
                max-width: 1600px;
            }
        }
        
        @media (min-width: 2560px) {
            .container-lg {
                max-width: 1800px;
            }
        }
        
        /* Prevent horizontal scroll on all devices */
        .row {
            margin-left: 0;
            margin-right: 0;
        }
        
        .row > * {
            padding-left: 15px;
            padding-right: 15px;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Roboto', sans-serif;
            font-weight: 700;
        }
        
        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            color: white !important;
            letter-spacing: 1px;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        /* Hero Section */
        .hero-section {
            background: transparent;
            color: white;
            padding: 0;
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            margin-top: 0;
            margin-bottom: 0;
            width: 100%;
            max-width: 100vw;
            display: flex;
            align-items: center;
        }
        
        @media (min-width: 1400px) {
            .hero-section {
                min-height: 100vh;
                height: 100vh;
            }
        }
        
        @media (min-width: 1920px) {
            .hero-section {
                min-height: 100vh;
                height: 100vh;
            }
        }
        
        @media (min-width: 2560px) {
            .hero-section {
                min-height: 100vh;
                height: 100vh;
            }
        }
        
        @media (max-width: 768px) {
            .hero-section {
                margin-top: 0;
                min-height: 100vh;
            }
        }
        
        /* Medium screens (laptops) - 768px to 1399px */
        @media (min-width: 769px) and (max-width: 1399px) {
            .hero-section {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding-top: 100px;
            }
            
            .hero-content {
                padding: 150px 0 20px 0;
                text-align: center;
                margin: 0 auto;
                width: 100%;
            }
            
            .hero-title {
                font-size: 3rem;
                text-align: center;
                margin-left: auto;
                margin-right: auto;
            }
            
            .hero-subtitle {
                font-size: 1.15rem;
                text-align: center;
                margin-left: auto;
                margin-right: auto;
                max-width: 900px;
                margin-bottom: 1rem;
            }
            
            .hero-section .container-fluid {
                width: 100%;
                max-width: 100%;
                padding-left: 30px;
                padding-right: 30px;
            }
            
            .hero-section .row {
                justify-content: center;
                margin-left: 0;
                margin-right: 0;
            }
            
            .hero-stats {
                margin-top: 0px;
                margin-bottom: 0;
            }
            
            .hero-stats.row {
                margin-top: 0 !important;
            }
            
            .hero-stats .stat-card {
                margin-bottom: 0;
            }
        }
        
        .hero-video-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        @media (min-width: 1400px) {
            .hero-video-container {
                width: 100vw;
                height: 100vh;
            }
        }
        
        @media (max-width: 768px) {
            .hero-video-container {
                top: 0;
                height: 100%;
            }
        }
        
        .hero-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            min-width: 100%;
            min-height: 100%;
        }
        
        @media (min-width: 1400px) {
            .hero-video {
                width: 100vw;
                height: 100vh;
                min-width: 100vw;
                min-height: 100vh;
                object-fit: cover;
            }
        }
        
        @media (min-width: 1920px) {
            .hero-video {
                width: 100vw;
                height: 100vh;
                min-width: 100vw;
                min-height: 100vh;
            }
        }
        
        @media (min-width: 2560px) {
            .hero-video {
                width: 100vw;
                height: 100vh;
                min-width: 100vw;
                min-height: 100vh;
            }
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.75) 0%, rgba(118, 75, 162, 0.75) 100%);
            z-index: 1;
            pointer-events: none;
        }
        
        @media (min-width: 1400px) {
            .hero-section::before {
                width: 100vw;
                height: 100vh;
            }
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            padding: 80px 0;
            width: 100%;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .hero-content {
                padding: 60px 0;
            }
        }
        
        
        /* Animated floating elements */
        .hero-animations {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
            overflow: hidden;
        }
        
        .floating-icon {
            position: absolute;
            color: rgba(255, 255, 255, 0.3);
            font-size: 2rem;
            animation: float 15s infinite ease-in-out;
        }
        
        .floating-icon:nth-child(1) {
            left: 10%;
            top: 20%;
            animation-delay: 0s;
            font-size: 2.5rem;
        }
        
        .floating-icon:nth-child(2) {
            left: 80%;
            top: 30%;
            animation-delay: 2s;
            font-size: 2rem;
        }
        
        .floating-icon:nth-child(3) {
            left: 20%;
            top: 70%;
            animation-delay: 4s;
            font-size: 1.8rem;
        }
        
        .floating-icon:nth-child(4) {
            left: 70%;
            top: 75%;
            animation-delay: 6s;
            font-size: 2.2rem;
        }
        
        .floating-icon:nth-child(5) {
            left: 50%;
            top: 15%;
            animation-delay: 1s;
            font-size: 1.5rem;
        }
        
        .floating-icon:nth-child(6) {
            left: 15%;
            top: 50%;
            animation-delay: 3s;
            font-size: 2rem;
        }
        
        .floating-icon:nth-child(7) {
            left: 85%;
            top: 60%;
            animation-delay: 5s;
            font-size: 1.8rem;
        }
        
        .floating-icon:nth-child(8) {
            left: 40%;
            top: 85%;
            animation-delay: 7s;
            font-size: 2.3rem;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0) rotate(0deg);
                opacity: 0.3;
            }
            25% {
                transform: translateY(-30px) translateX(20px) rotate(5deg);
                opacity: 0.5;
            }
            50% {
                transform: translateY(-60px) translateX(-15px) rotate(-5deg);
                opacity: 0.4;
            }
            75% {
                transform: translateY(-30px) translateX(10px) rotate(3deg);
                opacity: 0.5;
            }
        }
        
        @keyframes floatSlow {
            0%, 100% {
                transform: translateY(0) translateX(0);
                opacity: 0.2;
            }
            50% {
                transform: translateY(-40px) translateX(30px);
                opacity: 0.4;
            }
        }
        
        .floating-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: floatSlow 20s infinite ease-in-out;
        }
        
        .floating-circle:nth-child(9) {
            width: 80px;
            height: 80px;
            left: 5%;
            top: 10%;
            animation-delay: 0s;
        }
        
        .floating-circle:nth-child(10) {
            width: 60px;
            height: 60px;
            left: 90%;
            top: 80%;
            animation-delay: 4s;
        }
        
        .floating-circle:nth-child(11) {
            width: 100px;
            height: 100px;
            left: 60%;
            top: 5%;
            animation-delay: 8s;
        }
        
        .floating-circle:nth-child(12) {
            width: 50px;
            height: 50px;
            left: 30%;
            top: 90%;
            animation-delay: 2s;
        }
        
        @media (max-width: 768px) {
            .floating-icon {
                font-size: 1.5rem;
            }
            
            .floating-icon:nth-child(1) {
                font-size: 1.8rem;
            }
            
            .floating-circle {
                display: none;
            }
        }
        
        /* Hero Statistics */
        .hero-stats {
            position: relative;
            z-index: 2;
            margin-top: 60px;
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            animation: fadeInUp 1s ease-out;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            color: rgba(255, 255, 255, 1) !important;
            margin-bottom: 10px;
            display: block;
            line-height: 1;
            position: relative;
            z-index: 10;
        }
        
        .stat-icon i {
            display: inline-block !important;
            color: rgba(255, 255, 255, 1) !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-card:nth-child(1) {
            animation-delay: 0.1s;
        }
        
        .stat-card:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .stat-card:nth-child(3) {
            animation-delay: 0.3s;
        }
        
        .stat-card:nth-child(4) {
            animation-delay: 0.4s;
        }
        
        @media (max-width: 768px) {
            .hero-stats {
                margin-top: 40px;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-icon {
                font-size: 2rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .stat-label {
                font-size: 0.8rem;
            }
        }
        
        /* Ensure hero section container allows full width video */
        .hero-section .container-fluid {
            width: 100%;
            max-width: 100%;
            padding-left: 15px;
            padding-right: 15px;
        }
        
        
        @media (min-width: 1400px) {
            .hero-section .container-fluid {
                max-width: 100vw;
            }
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }
        
        .btn-hero {
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        /* Features Section */
        .features-section {
            padding: 80px 0;
            background: #f8f9fa;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1rem;
            color: #212529;
        }
        
        .section-subtitle {
            text-align: center;
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 4rem;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        @media (min-width: 1400px) {
            .feature-card {
                padding: 3rem;
            }
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            color: white;
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #212529;
        }
        
        .feature-text {
            color: #6c757d;
            line-height: 1.8;
        }
        
        /* Image Gallery Section */
        .gallery-section {
            padding: 80px 0;
            background: white;
        }
        
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .gallery-item:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .gallery-item {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .gallery-item img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: all 0.3s;
            display: block;
        }
        
        @media (min-width: 1400px) {
            .gallery-item img {
                height: 350px;
            }
        }
        
        .gallery-item:hover img {
            transform: scale(1.1);
        }
        
        .gallery-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
            padding: 2rem;
            transform: translateY(100%);
            transition: all 0.3s;
        }
        
        .gallery-item:hover .gallery-overlay {
            transform: translateY(0);
        }
        
        /* Contact Section */
        .contact-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .contact-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .contact-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .contact-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .contact-text {
            opacity: 0.9;
        }
        
        .contact-form {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .form-label {
            color: #212529;
            margin-bottom: 0.5rem;
            font-weight: 500;
            display: block;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-control::placeholder {
            color: #adb5bd;
        }
        
        /* Footer */
        .footer {
            background: #212529;
            color: white;
            padding: 3rem 0 1rem;
        }
        
        .footer h5 {
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .footer a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer a:hover {
            color: white;
        }
        
        .developer-link {
            color: rgba(255,255,255,0.8) !important;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .developer-link:hover {
            color: white !important;
            text-decoration: underline;
        }
        
        /* Custom Notification Toast */
        .notification-toast {
            position: fixed;
            top: 100px;
            right: 20px;
            background: white;
            border-radius: 15px;
            padding: 20px 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            z-index: 10000;
            min-width: 350px;
            max-width: 450px;
            transform: translateX(500px);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border-left: 5px solid #28a745;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .notification-toast.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .notification-toast.error {
            border-left-color: #dc3545;
        }
        
        .notification-toast.success {
            border-left-color: #28a745;
        }
        
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .notification-toast.success .notification-icon {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .notification-toast.error .notification-icon {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #212529;
            margin-bottom: 5px;
        }
        
        .notification-message {
            font-size: 0.95rem;
            color: #6c757d;
            line-height: 1.5;
        }
        
        .notification-close {
            background: transparent;
            border: none;
            font-size: 1.5rem;
            color: #adb5bd;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
            flex-shrink: 0;
        }
        
        .notification-close:hover {
            background: #f8f9fa;
            color: #212529;
        }
        
        @media (max-width: 768px) {
            .notification-toast {
                right: 10px;
                left: 10px;
                min-width: auto;
                max-width: none;
                transform: translateY(-100px);
            }
            
            .notification-toast.show {
                transform: translateY(0);
            }
        }
        
        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: #667eea;
            transform: translateY(-3px);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .btn-hero {
                padding: 12px 30px;
                font-size: 1rem;
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .hero-section {
                margin-top: 70px;
                padding: 120px 0 60px 0;
            }
        }
        
        /* Large screens optimization - 1200px to 1399px */
        @media (min-width: 1200px) and (max-width: 1399px) {
            .container {
                max-width: 1140px;
            }
            
            .hero-title {
                font-size: 3.2rem;
                text-align: center;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
                text-align: center;
            }
        }
        
        /* Extra large screens */
        @media (min-width: 1400px) {
            .container {
                max-width: 1320px;
            }
            
            .hero-title {
                font-size: 3.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.3rem;
            }
            
            .section-title {
                font-size: 2.8rem;
            }
        }
        
        /* Ultra wide screens */
        @media (min-width: 1920px) {
            .container {
                max-width: 1600px;
            }
            
            .hero-title {
                font-size: 4rem;
            }
            
            .hero-subtitle {
                font-size: 1.4rem;
            }
        }
        
        
        /* Prevent content from being too wide on very large screens */
        @media (min-width: 1600px) {
            .container {
                max-width: 1600px;
                margin: 0 auto;
            }
        }
        
        /* Ensure proper spacing on all screen sizes */
        @media (min-width: 992px) {
            .features-section,
            .gallery-section,
            .contact-section {
                padding: 100px 0;
            }
        }
        
        /* Better image handling on large screens */
        @media (min-width: 1400px) {
            .hero-image {
                max-height: 600px !important;
            }
        }
        
        @media (min-width: 1920px) {
            .hero-image {
                max-height: 700px !important;
            }
        }
        
        /* Improve form layout on large screens */
        @media (min-width: 1200px) {
            .contact-form {
                padding: 3rem;
            }
        }
        
        /* Better feature card spacing on large screens */
        @media (min-width: 1400px) {
            .feature-card {
                padding: 3rem 2.5rem;
            }
        }
        
        /* Fix navbar on large screens */
        @media (min-width: 992px) {
            .navbar {
                padding: 1.2rem 0;
            }
        }
        
        /* Ensure text doesn't get too large */
        @media (min-width: 2560px) {
            .hero-title {
                font-size: 4.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.5rem;
            }
            
            .section-title {
                font-size: 3.2rem;
            }
            
            .container {
                max-width: 1800px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid container-lg">
            <a class="navbar-brand" href="home.php">
                <i class="bi bi-mortarboard"></i> Tyazubwenge Training Center
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Our Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#gallery">Products & Training</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light btn-sm text-dark ms-2" href="login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <!-- Background Video -->
        <div class="hero-video-container">
            <video class="hero-video" autoplay muted loop playsinline>
                <source src="home/video/Animation_For_Tyazubwenge_Training_Center.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
        
        <!-- Animated Floating Elements -->
        <div class="hero-animations">
            <i class="bi bi-flask floating-icon"></i>
            <i class="bi bi-mortarboard floating-icon"></i>
            <i class="bi bi-gear floating-icon"></i>
            <i class="bi bi-award floating-icon"></i>
            <i class="bi bi-lightbulb floating-icon"></i>
            <i class="bi bi-graph-up floating-icon"></i>
            <i class="bi bi-people floating-icon"></i>
            <i class="bi bi-star floating-icon"></i>
            <div class="floating-circle"></div>
            <div class="floating-circle"></div>
            <div class="floating-circle"></div>
            <div class="floating-circle"></div>
        </div>
        
        <div class="container-fluid container-lg">
            <div class="row align-items-center">
                <div class="col-lg-12 hero-content text-center">
                    <h1 class="hero-title">Welcome to Tyazubwenge Training Center</h1>
                    <p class="hero-subtitle">
                        Your trusted partner for quality chemical products and professional manufacturing training. 
                        We supply premium chemicals and empower entrepreneurs through hands-on training in soap making and product manufacturing.
                    </p>
                    <div class="d-flex flex-column flex-sm-row gap-2 gap-sm-3 justify-content-center">
                        <a href="login.php" class="btn btn-light btn-hero">
                            <i class="bi bi-box-arrow-in-right"></i> Get Started
                        </a>
                        <div class="btn-group" role="group">
                            <a href="desktop-app/dist/Tyazubwenge Desktop Setup 1.0.0.exe" class="btn btn-primary btn-hero" download style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                                <i class="bi bi-download"></i> Download Desktop App
                            </a>
                            <button type="button" class="btn btn-primary btn-hero dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="desktop-app/dist/Tyazubwenge Desktop Setup 1.0.0.exe" download><i class="bi bi-windows"></i> Installer (Windows 64-bit)</a></li>
                                <li><a class="dropdown-item" href="desktop-app/dist/win-unpacked" download><i class="bi bi-folder"></i> Portable Version (Folder)</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="desktop-app/INSTALLATION_TROUBLESHOOTING.md" target="_blank"><i class="bi bi-question-circle"></i> Installation Help</a></li>
                            </ul>
                        </div>
                        <a href="#features" class="btn btn-outline-light btn-hero">
                            <i class="bi bi-info-circle"></i> Learn More
                        </a>
                    </div>
                    <p class="text-center mt-3 text-white" style="font-size: 0.9rem; opacity: 0.9;">
                        <i class="bi bi-info-circle"></i> Requires Windows 10/11 (64-bit). Having issues? 
                        <a href="desktop-app/INSTALLATION_TROUBLESHOOTING.md" target="_blank" class="text-white text-decoration-underline">See troubleshooting guide</a>
                    </p>
                </div>
            </div>
            
            <!-- Animated Statistics -->
            <div class="row hero-stats">
                <div class="col-md-3 col-6 mb-3 mb-md-0">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="stat-number" data-target="500" data-suffix="+">0+</div>
                        <div class="stat-label">Trained Students</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3 mb-md-0">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-droplet-fill" style="color: rgba(255, 255, 255, 1) !important; font-size: 2.5rem; display: block;"></i>
                        </div>
                        <div class="stat-number" data-target="100" data-suffix="+">0+</div>
                        <div class="stat-label">Chemical Products</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3 mb-md-0">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-award-fill"></i>
                        </div>
                        <div class="stat-number" data-target="15" data-suffix="+">0+</div>
                        <div class="stat-label">Years Experience</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3 mb-md-0">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <div class="stat-number" data-target="98">0</div>
                        <div class="stat-label">% Satisfaction</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container-fluid container-lg">
            <h2 class="section-title">What We Offer</h2>
            <p class="section-subtitle">Quality chemicals and professional training for your success</p>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-flask"></i>
                        </div>
                        <h3 class="feature-title">Chemical Products</h3>
                        <p class="feature-text">
                            Wide range of high-quality chemical products for manufacturing, 
                            including raw materials for soap making, detergents, and other industrial applications.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-mortarboard"></i>
                        </div>
                        <h3 class="feature-title">Manufacturing Training</h3>
                        <p class="feature-text">
                            Comprehensive hands-on training programs in soap making and product manufacturing. 
                            Learn from industry experts and start your own business.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-award"></i>
                        </div>
                        <h3 class="feature-title">Quality Assurance</h3>
                        <p class="feature-text">
                            All our chemical products meet international quality standards. 
                            We ensure purity, consistency, and safety in every batch.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h3 class="feature-title">Expert Guidance</h3>
                        <p class="feature-text">
                            Our experienced trainers provide personalized guidance to help you 
                            master the art of manufacturing and build a successful business.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-truck"></i>
                        </div>
                        <h3 class="feature-title">Reliable Supply</h3>
                        <p class="feature-text">
                            Consistent supply chain with competitive pricing. 
                            We serve both retail and wholesale customers with flexible payment options.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-hand-thumbs-up"></i>
                        </div>
                        <h3 class="feature-title">Customer Support</h3>
                        <p class="feature-text">
                            Dedicated customer support team ready to assist with product selection, 
                            technical queries, and training program inquiries.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section class="gallery-section" id="gallery">
        <div class="container-fluid container-lg">
            <h2 class="section-title">Our Business</h2>
            <p class="section-subtitle">Discover what makes Tyazubwenge your trusted partner</p>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="gallery-item">
                        <img src="home/images/products.jpg" alt="Chemical Products">
                        <div class="gallery-overlay">
                            <h4>Chemical Products</h4>
                            <p>Premium quality chemicals for manufacturing soap, detergents, and various industrial products. We stock a wide range of raw materials and finished products.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="gallery-item">
                        <img src="home/images/prodcy.jpg" alt="Production & Manufacturing">
                        <div class="gallery-overlay">
                            <h4>Production & Manufacturing</h4>
                            <p>Hands-on training in manufacturing processes including soap making, detergent production, and other chemical-based products.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="gallery-item">
                        <img src="home/images/teachiing.jpg" alt="Training Programs">
                        <div class="gallery-overlay">
                            <h4>Training Programs</h4>
                            <p>Professional training courses designed to equip entrepreneurs with practical skills in product manufacturing and business management.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section" id="contact">
        <div class="container-fluid container-lg">
            <h2 class="section-title text-white">Get In Touch</h2>
            <p class="section-subtitle text-white">We'd love to hear from you</p>
            
            <div class="row">
                <div class="col-lg-4">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <h4 class="contact-title">Address</h4>
                        <p class="contact-text">
                            Musanze, Rwanda<br>
                            East Africa
                        </p>
                    </div>
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="bi bi-telephone-fill"></i>
                        </div>
                        <h4 class="contact-title">Phone</h4>
                        <p class="contact-text">
                            +250 788 459 428<br>
                            +250 XXX XXX XXX
                        </p>
                    </div>
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="bi bi-envelope-fill"></i>
                        </div>
                        <h4 class="contact-title">Email</h4>
                        <p class="contact-text">
                            Infomitage@gmail.com<br>
                            info@tyazubwenge.com
                        </p>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="contact-form">
                        <h3 class="mb-4" style="color: #212529;">Send us a Message</h3>
                        <form id="contactForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="contactName" class="form-label fw-semibold">Your Name *</label>
                                    <input type="text" id="contactName" name="name" class="form-control" placeholder="Enter your full name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="contactEmail" class="form-label fw-semibold">Your Email *</label>
                                    <input type="email" id="contactEmail" name="email" class="form-control" placeholder="Enter your email address" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="contactSubject" class="form-label fw-semibold">Subject *</label>
                                <select id="contactSubject" name="subject" class="form-select" required>
                                    <option value="">Select a subject</option>
                                    <option value="chemical-products">Chemical Products Inquiry</option>
                                    <option value="training">Training Program Inquiry</option>
                                    <option value="soap-making">Soap Making Training</option>
                                    <option value="wholesale">Wholesale Orders</option>
                                    <option value="general">General Inquiry</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="contactMessage" class="form-label fw-semibold">Message *</label>
                                <textarea id="contactMessage" name="message" class="form-control" rows="5" placeholder="Enter your message here..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-send"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid container-lg">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>Tyazubwenge Training Center</h5>
                    <p style="color: rgba(255,255,255,0.7);">
                        Leading supplier of quality chemical products and provider of professional manufacturing training. 
                        Empowering entrepreneurs through education and quality products since our establishment.
                    </p>
                    <div class="social-links mt-3">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-twitter"></i></a>
                        <a href="#"><i class="bi bi-linkedin"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#gallery">Gallery</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="login.php">Login</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Contact Info</h5>
                    <p style="color: rgba(255,255,255,0.7);">
                        <i class="bi bi-geo-alt"></i> Musanze, Rwanda<br>
                        <i class="bi bi-telephone"></i> +250 788 459 428<br>
                        <i class="bi bi-envelope"></i> Infomitage@gmail.com
                    </p>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <div class="row">
                <div class="col-12 text-center">
                    <p style="color: rgba(255,255,255,0.7); margin: 0;">
                        &copy; <?php echo date('Y'); ?> Tyazubwenge Training Center. All rights reserved.
                    </p>
                    <p style="color: rgba(255,255,255,0.6); margin: 10px 0 0 0; font-size: 0.9rem;">
                        Developed by <a href="https://lanari.rw" target="_blank" rel="noopener noreferrer" class="developer-link">Lanari Tech Ltd</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animated counter for statistics
        function animateCounter(element, target, suffix = '') {
            const duration = 2000;
            let start = 0;
            const increment = target / (duration / 16);
            const timer = setInterval(() => {
                start += increment;
                if (start >= target) {
                    element.textContent = target + suffix;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(start) + suffix;
                }
            }, 16);
        }
        
        // Intersection Observer for statistics animation
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px'
        };
        
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumbers = entry.target.querySelectorAll('.stat-number');
                    statNumbers.forEach(stat => {
                        const target = parseInt(stat.getAttribute('data-target'));
                        const suffix = stat.getAttribute('data-suffix') || '';
                        const currentText = stat.textContent.trim();
                        if (currentText === '0' || currentText === '0+' || currentText === '0' + suffix) {
                            animateCounter(stat, target, suffix);
                        }
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        // Observe hero stats section
        const heroStats = document.querySelector('.hero-stats');
        if (heroStats) {
            statsObserver.observe(heroStats);
        }
        
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

        // Custom Notification Function
        function showNotification(message, type = 'success') {
            // Remove existing notifications
            const existing = document.querySelector('.notification-toast');
            if (existing) {
                existing.remove();
            }
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification-toast ${type}`;
            
            const icon = type === 'success' 
                ? '<i class="bi bi-check-circle-fill"></i>' 
                : '<i class="bi bi-x-circle-fill"></i>';
            
            const title = type === 'success' ? 'Success!' : 'Error!';
            
            notification.innerHTML = `
                <div class="notification-icon">${icon}</div>
                <div class="notification-content">
                    <div class="notification-title">${title}</div>
                    <div class="notification-message">${message}</div>
                </div>
                <button class="notification-close" onclick="this.parentElement.remove()">
                    <i class="bi bi-x"></i>
                </button>
            `;
            
            // Add to body
            document.body.appendChild(notification);
            
            // Trigger animation
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 400);
            }, 5000);
        }
        
        // Contact form submission
        document.getElementById('contactForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
            
            // Get form data
            const formData = new FormData(form);
            
            try {
                const response = await fetch('api/contact.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Get response text first
                const responseText = await response.text();
                
                // Check if response is empty
                if (!responseText || responseText.trim() === '') {
                    throw new Error('Empty response from server');
                }
                
                // Try to parse JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.error('Response text:', responseText);
                    throw new Error('Invalid JSON response from server');
                }
                
                if (result.success) {
                    // Show success notification
                    showNotification(
                        result.message || 'Thank you for your message! We will get back to you soon.',
                        'success'
                    );
                    form.reset();
                } else {
                    // Show error notification
                    showNotification(
                        result.message || 'Sorry, there was an error sending your message. Please try again.',
                        'error'
                    );
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification(
                    'Sorry, there was an error sending your message. Please try again later.',
                    'error'
                );
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });

        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(102, 126, 234, 0.95)';
                navbar.style.backdropFilter = 'blur(10px)';
            } else {
                navbar.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                navbar.style.backdropFilter = 'none';
            }
        });
    </script>
</body>
</html>

