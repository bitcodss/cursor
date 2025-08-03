# Portfolio Tracker

A comprehensive PHP-based web application for tracking personal stock and ETF portfolios. Monitor real-time stock prices, track transactions, analyze performance, and manage multiple investment strategies.

## Features

### 🏦 Portfolio Management
- Create and manage multiple portfolios (by strategy, account, etc.)
- Track cash positions and uninvested funds
- Compare performance between portfolios
- Asset allocation visualization

### 📊 Real-time Stock Data
- Automatic price updates via Yahoo Finance API
- Historical price charts and trends
- Support for US stocks and ETFs
- Market status monitoring

### 📝 Transaction Tracking
- Record buy/sell transactions with fees
- Automatic gain/loss calculations (FIFO method)
- Dividend payment tracking
- Stock split handling
- Cash deposits and withdrawals

### 📈 Portfolio Analytics
- Real-time portfolio valuation
- Unrealized and realized gains/losses
- Total return calculations (including dividends)
- Performance vs. S&P 500 benchmark
- Sector and asset allocation breakdowns

### 🔔 Alerts & Monitoring
- Price change notifications
- Dividend payment alerts
- Cash allocation warnings
- Portfolio performance monitoring

### 📤 Import/Export
- CSV transaction import
- Excel/PDF report exports
- Broker data integration support

## Technology Stack

- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: Bootstrap 5
- **Charts**: Chart.js
- **Tables**: DataTables
- **APIs**: Yahoo Finance (unofficial)

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer (for dependency management)
- Web server (Apache/Nginx)
- cURL extension enabled

### Quick Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/portfolio-tracker.git
   cd portfolio-tracker
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` file with your database credentials:
   ```env
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=portfolio_tracker
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

4. **Set up the database**
   - Create a MySQL database named `portfolio_tracker`
   - Visit `http://yourdomain.com/setup` to initialize tables
   - Or manually run: `mysql -u username -p portfolio_tracker < database/schema.sql`

5. **Configure web server**
   Point your web server document root to the `public/` directory.

   **Apache (.htaccess example)**
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php [QSA,L]
   ```

   **Nginx (example)**
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

6. **Set up automated price updates (optional)**
   ```bash
   # Add to crontab (crontab -e)
   */15 9-16 * * 1-5 /usr/bin/php /path/to/portfolio-tracker/cron/update-prices.php
   ```

## Initial Setup

1. **Access the setup page**
   Visit `http://yourdomain.com/setup` and follow the setup wizard.

2. **Test database connection**
   Verify your database credentials are correct.

3. **Create database tables**
   Initialize all required tables with sample data.

4. **Test API connection**
   Ensure stock data can be retrieved from Yahoo Finance.

5. **Access the application**
   Default login: `admin` / `changeme123`

## Usage Guide

### Creating Your First Portfolio

1. Navigate to **Portfolios** in the sidebar
2. Click **"Create New Portfolio"**
3. Enter a name (e.g., "Long-term Growth") and description
4. Set initial cash amount
5. Save the portfolio

### Adding Transactions

1. Go to **Transactions** or click **"Add Transaction"** in a portfolio
2. Select transaction type:
   - **Buy**: Purchase stocks/ETFs
   - **Sell**: Sell positions
   - **Dividend**: Record dividend payments
   - **Deposit**: Add cash to portfolio
   - **Withdrawal**: Remove cash from portfolio
3. Fill in details (symbol, shares, price, fees, date)
4. Save the transaction

### Monitoring Performance

The dashboard provides:
- **Total portfolio value** across all portfolios
- **Gain/loss calculations** with percentages
- **Asset allocation** pie charts
- **Recent transaction** history
- **Portfolio comparison** metrics

### Stock Price Updates

Prices can be updated:
- **Manually**: Click "Update Prices" button
- **Automatically**: Via cron job every 15 minutes during market hours
- **API**: Call `/api/update-prices` endpoint

## API Endpoints

### Public APIs

- `GET /api/search-stocks?q={query}` - Search for stocks
- `POST /api/update-prices` - Update stock prices
- `GET /api/portfolio-summary?id={portfolio_id}` - Get portfolio summary

### Example Usage

```javascript
// Search for stocks
fetch('/api/search-stocks?q=AAPL')
  .then(response => response.json())
  .then(data => console.log(data.results));

// Update prices
fetch('/api/update-prices', { method: 'POST' })
  .then(response => response.json())
  .then(data => console.log(`Updated ${data.updated} stocks`));
```

## Configuration

### Environment Variables

```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=portfolio_tracker
DB_USERNAME=root
DB_PASSWORD=

# API Keys (optional)
ALPHA_VANTAGE_API_KEY=your_key_here
POLYGON_API_KEY=your_key_here
FINNHUB_API_KEY=your_key_here

# Application
APP_NAME="Portfolio Tracker"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=America/New_York

# Cache
CACHE_ENABLED=true
CACHE_DURATION=900

# Market Hours (Eastern Time)
MARKET_OPEN_HOUR=9
MARKET_OPEN_MINUTE=30
MARKET_CLOSE_HOUR=16
MARKET_CLOSE_MINUTE=0
```

### Cron Jobs

Set up automated tasks for optimal performance:

```bash
# Update prices every 15 minutes during market hours
*/15 9-16 * * 1-5 /usr/bin/php /path/to/cron/update-prices.php

# Daily historical data update
0 6 * * 1-5 /usr/bin/php /path/to/cron/update-historical.php

# Weekly cleanup and maintenance
0 2 * * 0 /usr/bin/php /path/to/cron/maintenance.php
```

## File Structure

```
portfolio-tracker/
├── public/                 # Web root
│   ├── index.php          # Main entry point
│   ├── setup.php          # Setup wizard
│   ├── views/             # Page templates
│   └── api/               # API endpoints
├── src/                   # Application source
│   ├── Config/            # Configuration classes
│   ├── Models/            # Database models
│   └── Services/          # Business logic
├── database/              # Database files
│   └── schema.sql         # Database schema
├── cron/                  # Scheduled tasks
├── logs/                  # Application logs
├── vendor/                # Composer dependencies
├── .env.example           # Environment template
├── composer.json          # Dependencies
└── README.md             # This file
```

## Security Considerations

1. **Change default password** immediately after setup
2. **Use HTTPS** in production environments
3. **Secure .env file** - ensure it's not web accessible
4. **Regular backups** of your database
5. **Update dependencies** regularly with `composer update`
6. **Limit API access** if exposing endpoints publicly

## Troubleshooting

### Common Issues

**Database Connection Failed**
- Verify database credentials in `.env`
- Ensure MySQL service is running
- Check database exists and user has permissions

**API Calls Failing**
- Yahoo Finance API is unofficial and may change
- Check internet connectivity
- Consider implementing API key rotation

**Prices Not Updating**
- Verify cron job is set up correctly
- Check application logs in `logs/app.log`
- Ensure market hours are configured properly

**Performance Issues**
- Enable database indexing
- Implement caching for frequently accessed data
- Consider CDN for static assets

### Log Files

Check these locations for debugging:
- `logs/app.log` - Application events and errors
- Web server error logs
- Database slow query logs

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Disclaimer

This application is for personal portfolio tracking only. It is not intended for:
- Professional investment advice
- Commercial trading operations
- Real-time trading decisions
- Financial reporting for tax purposes

Always consult with qualified financial professionals for investment decisions and verify all data independently.

## Support

For support and questions:
- Create an issue on GitHub
- Check the troubleshooting section
- Review application logs for errors

---

**Happy Investing! 📈**