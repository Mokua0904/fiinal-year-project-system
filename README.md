# Eckose Techs - Point of Sale (POS) System

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)

A comprehensive, web-based Point of Sale (POS) and E-commerce management system designed specifically for **Eckose Techs**, a multi-branch electronics retailer. 

This project was developed to transition the business from manual, paper-based operations to a fully digitized, automated platform.

👨‍💻 Developer Information
*   **Name:** Job Mokua
*   **Registration Number:** BIT/2021/93882
*   **Course:** Bachelor of Science in Information Technology
*   **Institution:** Department of Information Technology, School of Computing and Informatics, Mount Kenya University

## 🚀 Key Features

*   **🛒 Point of Sale (POS) Terminal**: Lightning-fast checkout interface with barcode/search support, dynamic cart, and multi-payment handling (Cash, M-Pesa, Card).
*   **📱 M-Pesa Daraja API Integration**: Seamless STK Push integration for automated mobile payments directly from the POS and online storefront.
*   **🌐 Online Store & Self-Checkout**: A customer-facing storefront where clients can browse products, add to cart, and pay online.
*   **📦 Online Orders Dashboard**: A dedicated hub where admins/cashiers can view pending online orders, push them directly to the POS cart, and mark them as collected.
*   **🏢 Multi-Branch Management**: Support for multiple branches (e.g., Nairobi HQ and Lamu), with data segregation and admin oversight.
*   **📊 Advanced Reporting**: Real-time sales reports with filtering by date, branch, and payment method, featuring a clean, printable layout for auditing.
*   **👥 Role-Based Access Control (RBAC)**: Secure login system separating Admin privileges from Cashier capabilities.
*   **📦 Inventory & Supplier Management**: Track stock levels, receive low-stock alerts, and manage supplier details.

## 🛠️ Technology Stack

*   **Backend**: PHP 8.x (Vanilla)
*   **Database**: MySQL (PDO for secure database interactions)
*   **Frontend**: HTML5, Tailwind CSS, Vanilla JavaScript (AJAX/Fetch API)
*   **Payment Gateway**: Safaricom M-Pesa Daraja API (Sandbox/Production)
*   **Environment**: XAMPP / Apache

## ⚙️ Installation & Setup (Local Development)

To run this project locally, follow these steps:

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/Mokua0904/final-year-project.git
2.  **Move to XAMPP `htdocs` folder:**
    Move the cloned folder into your XAMPP `htdocs` directory (e.g., `C:\xampp\htdocs\eckose_pos`).
3.  **Set up the Database:**
    *   Open **phpMyAdmin**.
    *   Create a new database named `eckose_pos_db`.
    *   Import the provided SQL file: `eckose_pos_db.sql`.
4.  **Configure Database Connection:**
    *   Open `config/database.php`.
    *   Update the credentials if necessary (default is usually user: `root`, password: empty).
5.  **Configure M-Pesa API:**
    *   You will need your own Safaricom Daraja API credentials.
    *   Update the `consumer_key`, `consumer_secret`, and `passkey` in `mpesa_api.php`.
6.  **Run the Application:**
    *   Start **Apache** and **MySQL** in XAMPP.
    *   Open your browser and navigate to: `http://localhost/eckose_pos/`
