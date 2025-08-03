# 📈 Portfolio Tracker

A comprehensive PHP application for tracking your individual investment portfolio. Monitor stocks, bonds, cryptocurrencies, real estate, and other assets with detailed analytics and beautiful visualizations.

![Portfolio Tracker](https://img.shields.io/badge/PHP-8.0+-blue.svg)
![License](https://img.shields.io/badge/License-MIT-green.svg)
![Status](https://img.shields.io/badge/Status-Active-brightgreen.svg)

## ✨ Features

### 🎯 Core Functionality
- **Portfolio Management**: Add, edit, and delete investment items
- **Multi-Asset Support**: Stocks, bonds, ETFs, crypto, real estate, commodities, and more
- **Real-time Calculations**: Automatic gain/loss calculations and percentages
- **Categories & Tags**: Organize investments with custom categories and tags
- **Search & Filter**: Advanced search and sorting capabilities

### 📊 Analytics & Insights
- **Interactive Dashboard**: Overview of your entire portfolio
- **Visual Charts**: Pie charts for asset allocation, bar charts for performance
- **Performance Tracking**: Top and worst performers identification
- **Category Breakdown**: Detailed analysis by investment category
- **Historical Data**: Track value changes over time

### 🎨 User Experience
- **Modern UI**: Beautiful, responsive design with Bootstrap 5
- **Mobile-Friendly**: Works perfectly on all devices
- **Dark Mode Ready**: CSS prepared for future dark theme
- **Print Support**: Print-friendly portfolio reports
- **Keyboard Shortcuts**: Efficient navigation and actions

## 🚀 Quick Start

### Prerequisites
- PHP 8.0 or higher
- SQLite support (usually included with PHP)
- Web server (Apache, Nginx, or PHP built-in server)

### Installation

1. **Clone or Download**
   ```bash
   git clone <repository-url>
   cd portfolio-tracker
   ```

2. **Set Permissions**
   ```bash
   chmod 755 data/
   chmod 644 data/portfolio.db  # After first run
   ```

3. **Start the Application**
   
   **Option A: PHP Built-in Server (Quick Start)**
   ```bash
   php -S localhost:8000
   ```
   
   **Option B: Apache/Nginx**
   - Copy files to your web server document root
   - Ensure PHP is properly configured
   - Visit your domain or localhost

4. **Access the Application**
   Open your browser and navigate to:
   - Built-in server: `http://localhost:8000`
   - Web server: `http://your-domain.com` or `http://localhost`

## 📁 Project Structure

```
portfolio-tracker/
├── index.php              # Main application entry point
├── config/
│   └── database.php        # Database configuration and setup
├── classes/
│   ├── Portfolio.php       # Portfolio management class
│   └── PortfolioItem.php   # Individual item class
├── pages/
│   ├── dashboard.php       # Dashboard with overview
│   ├── portfolio.php       # Portfolio listing and management
│   ├── add.php            # Add new portfolio item
│   ├── edit.php           # Edit existing item
│   └── analytics.php      # Detailed analytics and charts
├── assets/
│   ├── css/
│   │   └── style.css       # Custom styles
│   └── js/
│       └── script.js       # JavaScript functionality
├── data/
│   └── portfolio.db        # SQLite database (auto-created)
├── README.md              # This file
└── LICENSE                # License information
```

## 🎮 Usage Guide

### Adding Your First Investment

1. **Navigate to "Add Item"** from the navigation menu
2. **Fill in the details**:
   - **Name**: e.g., "Apple Inc. Stock (AAPL)"
   - **Type**: Select from dropdown (Stock, Bond, ETF, etc.)
   - **Category**: Choose predefined category
   - **Quantity**: Number of shares/units
   - **Purchase Price**: Cost per unit when bought
   - **Current Value**: Current market value per unit
   - **Purchase Date**: When you bought it
   - **Description**: Additional notes
   - **Tags**: Comma-separated tags for organization

3. **Review the summary** showing total investment and gain/loss
4. **Click "Add to Portfolio"**

### Managing Your Portfolio

#### Viewing Items
- **Dashboard**: Quick overview with key metrics
- **Portfolio**: Detailed grid view of all items
- **Analytics**: Charts and performance analysis

#### Editing Items
- Click "Edit" on any portfolio item
- Update values, especially current prices
- Changes are tracked in the history

#### Organizing Items
- **Search**: Use the search bar to find specific items
- **Sort**: Sort by name, value, date, category
- **Filter**: Filter by categories or tags

### Understanding the Analytics

#### Dashboard Metrics
- **Total Value**: Current worth of entire portfolio
- **Total Invested**: Amount you've put in
- **Gain/Loss**: Profit or loss with percentage
- **Diversification**: Number of different categories

#### Performance Tracking
- **Top Performers**: Best-performing investments
- **Underperformers**: Investments losing value
- **Category Breakdown**: Performance by asset type
- **Allocation Charts**: Visual representation of portfolio distribution

## 🛠️ Configuration

### Database
The application uses SQLite by default, which requires no additional setup. The database file is automatically created in the `data/` directory.

### Categories
Default categories include:
- Stocks
- Bonds
- Real Estate
- Cryptocurrency
- Commodities
- Art & Collectibles
- Cash & Savings
- Other

You can modify these in `config/database.php`.

### Customization
- **Colors**: Modify CSS variables in `assets/css/style.css`
- **Categories**: Add/modify in the database configuration
- **Features**: Extend classes in the `classes/` directory

## 🔒 Security Considerations

- **Input Validation**: All user inputs are validated and sanitized
- **SQL Injection Protection**: Uses prepared statements
- **XSS Prevention**: Output is properly escaped
- **File Permissions**: Ensure proper permissions on data directory

## 📱 Mobile Support

The application is fully responsive and works great on:
- Desktop computers
- Tablets
- Smartphones
- All major browsers

## 🎨 Customization

### Themes
The application supports easy theming through CSS variables:

```css
:root {
    --primary-color: #007bff;
    --success-color: #28a745;
    --danger-color: #dc3545;
    /* ... */
}
```

### Adding Features
The modular structure makes it easy to add new features:

1. **New Pages**: Add PHP files to `pages/` directory
2. **New Classes**: Extend functionality in `classes/`
3. **Database Changes**: Modify schema in `config/database.php`

## 🐛 Troubleshooting

### Common Issues

**Database errors:**
- Ensure the `data/` directory is writable
- Check PHP SQLite extension is enabled

**Blank page:**
- Check PHP error logs
- Ensure PHP version is 8.0+
- Verify all files are uploaded correctly

**Styling issues:**
- Clear browser cache
- Check CSS file paths
- Verify Bootstrap CDN is accessible

**Performance:**
- For large portfolios, consider adding pagination
- Optimize images by resizing before upload

## 🤝 Contributing

We welcome contributions! Here's how you can help:

1. **Fork the repository**
2. **Create a feature branch**: `git checkout -b feature/amazing-feature`
3. **Commit your changes**: `git commit -m 'Add amazing feature'`
4. **Push to branch**: `git push origin feature/amazing-feature`
5. **Open a Pull Request**

### Development Guidelines
- Follow PSR-12 coding standards
- Add comments for complex logic
- Test changes thoroughly
- Update documentation as needed

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Bootstrap 5** for the beautiful UI framework
- **Font Awesome** for the comprehensive icon set
- **Chart.js** for interactive charts
- **PHP SQLite** for the lightweight database

## 📞 Support

If you encounter any issues or have questions:

1. **Check this README** for common solutions
2. **Search existing issues** in the repository
3. **Create a new issue** with detailed information
4. **Provide**: PHP version, browser, error messages, steps to reproduce

## 🚀 Future Enhancements

Planned features for future releases:
- **API Integration**: Real-time stock price updates
- **Import/Export**: CSV and JSON data exchange
- **Multiple Portfolios**: Separate portfolios for different goals
- **Advanced Charts**: More detailed analytics and trending
- **Mobile App**: Native mobile applications
- **Multi-Currency**: Support for international investments
- **Automated Backups**: Scheduled data backups
- **User Authentication**: Multi-user support

---

Made with ❤️ for investment tracking and portfolio management.

**Happy Investing! 📈**