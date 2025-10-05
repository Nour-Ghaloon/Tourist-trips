Nour Abo Ghaloon
Travel Management System (Laravel API)
 Project Description
A complete travel booking and management system API built using Laravel 10, designed to automate and simplify trip planning, booking, and management.
The system allows users to browse trips, make reservations, and manage their wallet balance seamlessly.
It supports both general and custom trips, with integrations for hotels, restaurants, and other travel entities.
Key Features
Manage both public and custom trips
Full reservation system (trips, hotels, restaurants)
Integrated wallet system – balance deducted automatically upon booking
Cloudinary image upload with precise image updates via media_id
Complete media management (upload, update, delete)
Request validation using Form Request classes
Real-time notifications for bookings (instead of emails)
Supports advanced relationships (Polymorphic, One-to-Many, Many-to-Many)
Tech Stack
Backend: Laravel 10
Database: MySQL
Storage: Cloudinary
Auth: Laravel Sanctum
Notifications: Laravel Notifications
Validation: Form Requests
Architecture: RESTful API
Project Structure
app/
 ├── Http/
 │   ├── Controllers/
 │   ├── Requests/
 │   ├── Resources/
 │   └── Middleware/
 ├── Models/
 ├── Services/
 └── Notifications/
 
 Installation & Setup
# Clone the repository
git clone https://github.com/username/repository-name.git
# Navigate to the project folder
cd repository-name
# Install dependencies
composer install
# Copy environment file
cp .env.example .env
# Generate app key
php artisan key:generate
# Run migrations and seeders
php artisan migrate --seed
# Serve the project
php artisan serve

API Endpoints Examples
Method Endpoint Description
GET /api/trips Fetch all available trips
POST /api/reservations Create a new reservation
POST /api/wallet/deposit Add funds to wallet
PUT /api/media/{media_id} Update a specific image
DELETE /api/media/{media_id} Delete a specific image

Future Improvements

Add electronic payment gateway (e.g., Stripe / PayPal)

Add trip rating and review system

Create a dashboard for admin analytics

Add filtering and recommendation system

Developer
[Nour Abo Ghaloon]
Backend Developer | Laravel API Specialist
 Email: nouraboghaloon@gmail.com
 LinkedIn: [linkedin.com/in/yourprofile](https://www.linkedin.com/in/nour-abo-ghaloon-5735a9387?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app)
 GitHub: github.com/Nour-Ghaloon

🇸🇾 نظام تنظيم الرحلات السياحية (Laravel API)

 وصف المشروع
نظام متكامل لتنظيم وحجز الرحلات السياحية تم تطويره باستخدام Laravel 10 (API).
يهدف إلى تسهيل عملية إدارة الرحلات والحجوزات والمحفظة الإلكترونية للمستخدمين، ويتيح دمج الفنادق والمطاعم والأنشطة ضمن الرحلة الواحدة.
 الميزات الأساسية
 إدارة الرحلات العامة والمخصصة
 نظام حجوزات يشمل الرحلات، الفنادق، والمطاعم
 محفظة إلكترونية تُخصم منها المبالغ تلقائيًا عند الحجز
 رفع الصور إلى Cloudinary مع إمكانية تعديل الصور عبر media_id
 إدارة الوسائط (رفع – تعديل – حذف)
 تحقق من صحة البيانات عبر Form Requests
 إشعارات فورية عند تنفيذ الحجوزات
 علاقات مرنة بين الجداول (Polymorphic – One to Many – Many to Many)

 التقنيات المستخدمة
الجانب الخلفي (Backend): Laravel 10
قاعدة البيانات: MySQL
تخزين الصور: Cloudinary
نظام الدخول: Laravel Sanctum
الإشعارات: Laravel Notifications
التحقق من البيانات: Form Requests
نمط العمل: RESTful API
 خطوات التشغيل

git clone https://github.com/username/repository-name.git
cd repository-name
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve

 أمثلة على نقاط النهاية (Endpoints)
الطريقة الرابط الوظيفة

GET /api/trips عرض جميع الرحلات
POST /api/reservations إنشاء حجز جديد
POST /api/wallet/deposit إضافة رصيد إلى المحفظة
PUT /api/media/{media_id} تعديل صورة معينة
DELETE /api/media/{media_id} حذف صورة معينة


التحسينات المستقبلية
إضافة نظام تقييم الرحلات
دعم بوابات الدفع الإلكتروني
تطوير لوحة تحكم للمسؤولين
تحسين واجهات عرض البيانات

 المطوّرة

[نورأبوغالون]
مطوّرة Backend متخصصة في Laravel API
 البريد الإلكتروني: nouraboghaloon@gmail.com
 لينكدإن:
 https://www.linkedin.com/in/nour-abo-ghaloon-5735a9387?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app
 GitHub: github.com/Nour-Ghaloon
