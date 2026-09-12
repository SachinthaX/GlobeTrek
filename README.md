# GlobeTrek Adventures

GlobeTrek Adventures is a dynamic travel and tourism web application developed for the CSE 5009 Web Application Development coursework.

## Technologies

- Core PHP
- MySQL
- PDO
- HTML5
- CSS3
- Vanilla JavaScript
- XAMPP

## User Roles

### Customer
- Register and login
- Browse travel packages
- Search and filter packages
- View package details
- Make bookings
- Make simulated payments
- View bookings
- Submit customer queries
- Edit profile

### Staff
- Staff dashboard
- Manage travel packages
- Manage accommodation and transportation information
- Manage bookings
- Respond to customer queries

### Administrator
- Administrator dashboard
- Manage staff accounts
- Oversee bookings
- Generate sales and customer reports

## Local Setup

1. Install XAMPP.
2. Start Apache and MySQL.
3. Create the `globetrek` MySQL database.
4. Import the project database/schema.
5. Copy:

   `config/db.example.php`

   to:

   `config/db.php`

6. Update the database credentials in `config/db.php`.
7. Place the project inside:

   `C:\xampp\htdocs\globetrek`

8. Open:

   `http://localhost/globetrek/`

## Important

This project uses a simulated payment process for coursework purposes. No real financial transaction is performed.

Database credentials and other local configuration files should not be committed to the repository.