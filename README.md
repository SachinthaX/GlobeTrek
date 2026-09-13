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
- PHP Sessions

## User Roles

### Customer
- Register and login
- Browse travel packages
- Search and filter packages
- View package details
- Make bookings
- Make simulated payments
- View and manage bookings
- Edit profile
- Submit customer queries

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
- Manage booking status
- Generate sales and customer reports

## Security

- Session-based authentication
- Role-based access control
- Password hashing and verification
- PDO prepared statements
- CSRF protection for protected forms
- Server-side validation
- Safe output using `htmlspecialchars()`
- Error logging and handling

## Local Setup

1. Install XAMPP.
2. Start Apache and MySQL.
3. Create the `globetrek` MySQL database.
4. Import `globetrek.sql`.
5. Copy:

   `config/db.example.php`

   to:

   `config/db.php`

6. Update the database credentials in `config/db.php`.
7. Place the project inside:

   `C:\xampp\htdocs\globetrek`

8. Open:

   `http://localhost/globetrek/`

## Database

The project includes the following tables:

- `users`
- `packages`
- `accommodations`
- `transportation`
- `bookings`
- `payments`
- `queries`

Sample development data is included in `globetrek.sql`.

## UI

The application uses custom CSS with:

- CSS Grid
- Flexbox
- Responsive media queries
- Reusable cards and forms
- Status badges
- Custom navigation and layouts

No Bootstrap or other front-end CSS framework is used.

## Important

This project uses a simulated payment process for coursework purposes. No real financial transaction is performed.

Database credentials and other local configuration files should not be committed to the repository.