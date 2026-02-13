# Credit Management System

A comprehensive web-based credit management application designed for wholesale/distribution businesses to track customer credit, sales, and recovery operations.

## Features

### 📊 Dashboard & Analytics
- Real-time overview of total outstanding, monthly sales, and recovery
- Sales vs Recovery trend analysis with interactive charts
- Top collectors and salesman efficiency metrics
- Debt aging analysis and at-risk customer identification
- Sector-wise outstanding debt visualization (Dead Zones)
- Top defaulters tracking

### 📝 Daily Sales Report (DSR)
- Quick DSR entry with auto-calculation
- Customer credit tracking with invoice details
- Return and scheme/discount management
- Bulk bill processing
- Salesman-wise sales summaries

### 👥 Customer Management
- Customer database with sector assignment
- Recovery day scheduling
- Customer ledger with chronological transaction history
- Bill-wise tracking with status updates
- CSV import for bulk customer data

### 💰 Bill Issuance & Recovery
- Issue bills to recovery salesmen
- Filter by recovery day or source salesman
- Bulk bill selection and processing
- Recovery sheet generation
- Merge with existing recovery sheets
- Detailed recovery tracking and processing

### 📈 Reports
- Full credit reports with filtering options
- Customer-specific ledger views
- Recovery sheet details
- Salesman performance reports
- Sector-wise analysis

## Technology Stack

- **Frontend**: HTML, CSS, JavaScript, Bootstrap 5
- **Backend**: PHP
- **Database**: MySQL
- **Charts**: Chart.js
- **Data Tables**: DataTables.js
- **Fonts**: Google Fonts (Outfit)

## UI/UX Features

✨ **v3 Update - Optimized for Laptop Screens**
- Compact, responsive design optimized for laptop displays
- Reduced font sizes and padding for better screen utilization
- Enhanced table layouts with improved data density
- Modern card-based interface with smooth animations
- Color-coded status indicators
- Glassmorphism and gradient effects

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/WAMP (for local development)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/IamTayyab2000/Credit.git
   cd Credit
   ```

2. **Database Setup**
   - Import the database schema from `database/` directory
   - Update database connection settings in `functionality/components/condb.php`

3. **Configure Database Connection**
   ```php
   // Edit functionality/components/condb.php
   $servername = "localhost";
   $username = "your_username";
   $password = "your_password";
   $dbname = "credit_db";
   ```

4. **Web Server Setup**
   - Place the project in your web server directory (e.g., `htdocs` for XAMPP)
   - Ensure PHP is properly configured
   - Enable required PHP extensions: `mysqli`, `json`

5. **Access the Application**
   - Navigate to `http://localhost/Credit/` in your browser
   - Login with admin credentials

## Project Structure

```
Credit/
├── css/                      # Stylesheets
│   ├── main.css             # Main custom styles
│   └── bootstrap.css        # Bootstrap framework
├── js/                       # JavaScript files
│   ├── admin_panel.js       # Dashboard logic
│   ├── dsr.js              # DSR functionality
│   └── issueBills.js       # Bill issuance logic
├── functionality/           # Backend PHP logic
│   └── components/         # Reusable components
│       ├── condb.php       # Database connection
│       ├── crud.php        # CRUD operations
│       └── session_chk_admin.php
├── database/               # Database schemas and migrations
├── adminpanel.php         # Dashboard
├── insertDSR.php          # DSR entry
├── IssueBills.php         # Bill issuance
├── insertCustomers.php    # Customer management
├── generate_recovery_sheet.php
├── see_customer_ledger.php
└── README.md
```

## Key Pages

- **`adminpanel.php`** - Main dashboard with analytics
- **`insertDSR.php`** - Daily Sales Report entry
- **`IssueBills.php`** - Bill issuance to recovery salesmen
- **`insertCustomers.php`** - Customer database management
- **`generate_recovery_sheet.php`** - Recovery sheet generation
- **`see_customer_ledger.php`** - Customer ledger view
- **`creditReport.php`** - Comprehensive credit reports

## Database Views

The system uses several database views for efficient data retrieval:
- `vw_customer_bill_ledger` - Chronological customer transaction history
- Additional views for reporting and analytics

## Version History

### v3 (Latest) - UI Optimization
- Optimized for laptop screen sizes
- Reduced font sizes and padding throughout
- Enhanced table responsiveness
- Improved data density
- Better screen space utilization

### Previous Versions
- v2 - Dashboard analytics implementation
- v1 - Initial release with core functionality

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/YourFeature`)
3. Commit your changes (`git commit -m 'Add YourFeature'`)
4. Push to the branch (`git push origin feature/YourFeature`)
5. Open a Pull Request

## License

This project is proprietary software. All rights reserved.

## Support

For issues, questions, or contributions, please contact the repository maintainer.

## Author

**IamTayyab2000**  
GitHub: [@IamTayyab2000](https://github.com/IamTayyab2000)

---

**Note**: This is a business-specific application designed for credit management in wholesale/distribution operations. Customize as needed for your specific business requirements.
