## Testy-WHOesTY — Elevating Boredom to an Entertaining Experience
Testy-WHOesTY is a simple, secure web application where users can register, manage a profile (including avatar upload), participate in a forum, and complete interactive tests with results (text/images and optional redirects).  

[![WHAT BAKERY PRODUCT ARE YOU???](https://img.shields.io/badge/WHAT%20BAKERY%20PRODUCT%20ARE%20YOU%3F%3F%3F-ff69b4?style=for-the-badge&labelColor=2b2b2b&color=ff69b4&logoColor=ff69b4)](https://zwa.toad.cz/~grechsof/progzektik/login.php)

### Features
#### Authentication & Accounts  
User registration (username, email, password)  
Password requirements: min 8 characters   
Login with CSRF protection  
Secure sessions and error messages for invalid login 

#### User Profile  
View and edit profile information  
Upload avatar (PNG/JPG/JPEG) with validation & secure storage  
Update username and password   
TestY Product Documentation

#### Forum
Everyone can read posts  
Only logged-in users can create posts  
Admins can add official comments  
Posts displayed chronologically 

#### Tests & Results
Choose from available tests  
Multiple questions per test  
Answers determine the final result  
Results may include text/images  
Some results may redirect to internal or external pages 

#### Admin Panel
Manage users (admin-only)  
Promote users to admin  
Comment on forum posts as an admin 

### Roles
Regular User: can register and log in  
Logged-in User: profile edits, avatar upload, create forum posts, complete tests  
Administrator: all features + admin panel, promote users, admin comments 

### Tech Stack  
Backend: PHP  
Database: MySQL  
Frontend: HTML5, CSS3, JavaScript  
Documentation: Doxygen (PHPDoc-style) 

### Security
CSRF tokens for form submissions  
Password hashing via password_hash() / password_verify()  
SQL injection prevention via prepared statements (mysqli_prepare)  
XSS prevention via escaping output (htmlspecialchars())  
Role-based access control (admin-only pages protected) 

### Project Structure
```bash
/connection.php      Database connection
/functions.php       Shared helper functions
/login.php           User authentication
/signup.php          User registration
/logout.php          Logout handler
/profile.php         User profile & avatar upload
/forum.php           Forum posts and comments
/admin_users.php     Admin user management
/tests/              Tests and results logic
```
