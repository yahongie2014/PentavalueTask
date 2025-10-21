# 🚀 Real-Time Sales Dashboard

A modern PHP-based real-time sales tracking system with WebSocket support, dynamic pricing based on weather conditions, and live analytics.

## 📋 Table of Contents

- [Features](#features)
- [Architecture](#architecture)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Documentation](#api-documentation)
- [Troubleshooting](#troubleshooting)
- [Project Structure](#project-structure)

## ✨ Features

- **Real-Time Order Tracking**: WebSocket-based live updates for new orders
- **Dynamic Analytics Dashboard**: Live revenue tracking and product statistics
- **Weather-Based Pricing**: Automatic price adjustments based on current temperature
- **Dual Database Support**: Works with both SQLite and MySQL
- **Redis Caching**: Optional Redis integration for performance optimization
- **RESTful API**: Clean, well-documented API endpoints
- **Migration System**: Database version control with automatic migrations
- **Docker Support**: Containerized deployment option

## 🏗️ Architecture

### Tech Stack

- **Backend**: PHP 8.2+ with PDO
- **WebSocket**: Ratchet (PHP WebSocket library)
- **Database**: SQLite (default) or MySQL
- **Cache**: Redis (optional)
- **Frontend**: Vanilla JavaScript with Tailwind CSS
- **External APIs**: OpenWeatherMap for weather data

### Design Patterns

- **MVC Architecture**: Clean separation of concerns
- **Repository Pattern**: Data access abstraction
- **Service Layer**: Business logic encapsulation
- **Dependency Injection**: Flexible component composition
- **Factory Pattern**: Order creation and service instantiation

## 📦 Requirements

### Minimum Requirements

- PHP 7.2 or higher (PHP 8.2+ recommended)
- PDO extension
- Composer
- One of the following databases:
  - SQLite 3 (included, no setup needed)
  - MySQL 5.7+ / MariaDB 10.2+

### Optional Requirements

- Redis 5.0+ (for caching and real-time features)
- OpenWeatherMap API key (for weather-based recommendations)

## 🚀 Installation

### Quick Start (Windows)

```batch
# Clone the repository
git clone https://github.com/yahongie2014/PentavalueTask.git
cd PentavalueTask

# Run the installation script
Installation.bat
```

### Manual Installation

#### 1. Clone and Install Dependencies

```bash
# Clone repository
git clone https://github.com/yahongie2014/PentavalueTask.git
cd PentavalueTask

# Install PHP dependencies
composer install
```

#### 2. Environment Configuration

```bash
# Copy environment template
cp .env.example .env

# Edit .env file with your configuration
# nano .env  # Linux/Mac
# notepad .env  # Windows
```

#### 3. Database Setup

**Option A: SQLite (Default - No Setup Required)**

The application will automatically create `sales.sqlite` in the project root.

**Option B: MySQL**

```sql
-- Create database
CREATE DATABASE sales CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (optional)
CREATE USER 'sales_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON sales.* TO 'sales_user'@'localhost';
FLUSH PRIVILEGES;
```

Update `.env`:

```ini
DB_DRIVER=mysql
DB_HOST=localhost
DB_NAME=sales
DB_USERNAME=sales_user
DB_PASSWORD=your_password
DB_PORT=3306
```

#### 4. Run Migrations

```bash
# Run database migrations
php migrate.php

# Seed with demo data
curl http://localhost:8080/seed
```

#### 5. Start Services

**Terminal 1: WebSocket Server**

```bash
php server.php
```

**Terminal 2: HTTP Server**

```bash
php -S 127.0.0.1:8080
```

**Windows: Start Both at Once**

```batch
start-all.bat
```

### Docker Installation

```bash
# Build and start containers
docker-compose up --build

# Access the application
# http://localhost:8080
```

## ⚙️ Configuration

### Environment Variables

```ini
# Database
DB_DRIVER=sqlite                    # sqlite or mysql
DB_PATH=sales.sqlite                # SQLite path
DB_HOST=localhost                   # MySQL host
DB_NAME=sales                       # MySQL database
DB_USERNAME=root                    # MySQL username
DB_PASSWORD=                        # MySQL password
DB_PORT=3306                        # MySQL port

# External APIs
OPENAI_API_KEY=your_key_here        # Optional: AI recommendations
WATHER_API_KEY=your_key_here        # OpenWeatherMap API key
CITY=Cairo                          # City for weather data

# WebSocket
WS_HOST=127.0.0.1                   # WebSocket bind address
WS_PORT=8000                        # WebSocket port
WS_PROTOCOL=ws                      # ws or wss

# Redis (Optional)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Application
APP_ENV=local                       # local, production
APP_DEBUG=true                      # true or false
APP_URL=http://localhost:8080

# Security
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:8000
API_RATE_LIMIT=100

# Cache
CACHE_DRIVER=redis                  # redis or file
CACHE_TTL=55                        # Cache lifetime in seconds
```

### Weather API Setup

1. Sign up at [OpenWeatherMap](https://openweathermap.org/api)
2. Get your free API key
3. Add to `.env`:
   ```ini
   WATHER_API_KEY=your_api_key_here
   CITY=YourCity
   ```

## 🎯 Usage

### Web Dashboard

1. Open browser: `http://localhost:8080`
2. Click "Create Random Order" to test real-time updates
3. View live analytics and order stream

### API Testing Interface

1. Navigate to: `http://localhost:8080/test-api.php`
2. Select endpoint from dropdown
3. Fill in required fields
4. Submit and view response

### Command Line

```bash
# Seed database with demo data
curl http://localhost:8080/seed

# Get all products
curl http://localhost:8080/products

# Create an order
curl -X POST http://localhost:8080/create_order \
  -H "Content-Type: application/json" \
  -d '{"product_id":1,"quantity":3,"price":10.0}'

# Get analytics
curl http://localhost:8080/analytics

# Get AI recommendations
curl http://localhost:8080/recommendations
```

## 📚 API Documentation

### Endpoints

#### `GET /products`

Get all available products.

**Response:**

```json
{
  "data": [
    { "id": 1, "name": "Cola", "price": 10.0 },
    { "id": 2, "name": "Water", "price": 5.0 }
  ],
  "count": 2
}
```

#### `POST /create_order`

Create a new order.

**Request:**

```json
{
  "product_id": 1,
  "quantity": 2,
  "price": 10.0,
  "date": "2025-10-12 14:30:00"
}
```

**Response:**

```json
{
  "data": {
    "product_name": "Cola",
    "quantity": 2,
    "price": 10.0,
    "date": "2025-10-12 14:30:00"
  },
  "status": "order saved"
}
```

#### `GET /analytics`

Get real-time analytics (cached for 55 seconds).

**Response:**

```json
{
  "total_revenue": 1250.5,
  "top_products": [
    { "product_name": "Cola", "total_sold": 45 },
    { "product_name": "Water", "total_sold": 38 }
  ],
  "orders_last_minute": {
    "product_name": "Coffee",
    "total_sold": 5
  },
  "revenue_last_minute": 75.0,
  "count_orders_last_minute": 3
}
```

#### `GET /recommendations`

Get AI-powered product recommendations based on weather.

**Response:**

```json
{
  "current_temperature": 32.5,
  "recommendations": "Temperature: 32.5°C. Suggested promotion: cold drinks like Juice, Water, Cola. Pricing strategy: cold drinks prioritized.",
  "adjusted_prices": [
    {
      "product": "Cola",
      "original_price": "10 LE",
      "adjusted_price": "11 LE",
      "change": "10%"
    }
  ],
  "suggested_products": ["Cola", "Water", "Juice"]
}
```

#### `GET /seed`

Seed database with demo products and orders.

**Response:**

```json
{
  "message": "Database seeded with demo products and orders",
  "products_created": 5,
  "orders_created": 25
}
```

### Postman Collection

Download the Postman collection from `docs/api.json` to test all endpoints.

## 🔧 Troubleshooting

### WebSocket Connection Issues

**Problem**: "WebSocket connection failed"

**Solutions:**

1. Ensure `server.php` is running:
   ```bash
   php server.php
   ```
2. Check if port 8000 is available:
   ```bash
   netstat -ano | findstr :8000  # Windows
   lsof -i :8000                 # Linux/Mac
   ```
3. Verify firewall settings allow port 8000
4. Check browser console for detailed error messages

### Database Connection Errors

**Problem**: "Database connection failed"

**Solutions:**

**For SQLite:**

- Ensure PHP has write permissions to project directory
- Check if SQLite extension is enabled:
  ```bash
  php -m | grep sqlite
  ```

**For MySQL:**

- Verify credentials in `.env`
- Test connection:
  ```bash
  mysql -h localhost -u root -p sales
  ```
- Ensure MySQL service is running
- Check `DB_PORT` matches your MySQL configuration

### Migration Issues

**Problem**: Migrations fail to run

**Solutions:**

1. Check migration status:
   ```bash
   php migrate.php status
   ```
2. Drop tables and re-run (development only):
   ```sql
   DROP TABLE IF EXISTS orders;
   DROP TABLE IF EXISTS products;
   DROP TABLE IF EXISTS migrations;
   ```
3. Run migrations again:
   ```bash
   php migrate.php
   ```

### Cache Problems

**Problem**: Analytics not updating

**Solutions:**

1. Disable Redis caching temporarily (set to file-based):
   ```ini
   CACHE_DRIVER=file
   ```
2. Clear Redis cache:
   ```bash
   redis-cli FLUSHALL
   ```
3. Restart Redis service
4. Check Redis connection:
   ```bash
   redis-cli ping
   ```

### Weather API Errors

**Problem**: Recommendations return 503 error

**Solutions:**

1. Verify API key is valid
2. Check API key has not exceeded rate limits
3. Test API directly:
   ```bash
   curl "https://api.openweathermap.org/data/2.5/weather?q=Cairo&appid=YOUR_KEY&units=metric"
   ```
4. Ensure city name is correct in `.env`

### Performance Issues

**Problem**: Slow response times

**Solutions:**

1. Enable Redis caching
2. Increase `CACHE_TTL` in `.env`
3. Add database indexes (already included in migrations)
4. Use MySQL instead of SQLite for production
5. Monitor PHP error logs:
   ```bash
   tail -f /var/log/apache2/error.log  # Linux
   ```

## 📁 Project Structure

```
PentavalueTask/
├── App/
│   ├── Controllers/
│   │   ├── BaseController.php      # Base controller with common methods
│   │   └── SalesController.php     # Main sales endpoint controller
│   ├── Repositories/
│   │   ├── OrderRepository.php     # Order data access
│   │   └── ProductRepository.php   # Product data access
│   ├── Services/
│   │   ├── OrderService.php        # Order business logic
│   │   └── ProductService.php      # Product business logic
│   ├── Validators/
│   │   └── OrderValidator.php      # Input validation
│   ├── Helpers/
│   │   └── ResponseHelper.php      # JSON response helper
│   └── Events/
│       └── getAnalyticsData.php    # Analytics worker (optional)
├── Connectivity/
│   ├── DB.php                      # Database factory
│   ├── DBConnectionInterface.php   # Connection interface
│   ├── MySQLConnection.php         # MySQL implementation
│   ├── SQLiteConnection.php        # SQLite implementation
│   └── Migrator.php                # Migration system
├── MindMap/
│   └── Router.php                  # Simple routing system
├── public/
│   ├── index.php                   # Main dashboard
│   ├── test-api.php                # API testing interface
│   └── .htaccess                   # Apache rewrite rules
├── docs/
│   └── api.json                    # Postman collection
├── server.php                      # WebSocket server
├── index.php                       # Application entry point
├── migrate.php                     # Migration CLI tool
├── .env                            # Environment configuration
├── composer.json                   # PHP dependencies
├── docker-compose.yml              # Docker configuration
└── README.md                       # This file
```

## 🔐 Security Considerations

### Production Deployment

1. **Disable Debug Mode:**

   ```ini
   APP_DEBUG=false
   APP_ENV=production
   ```

2. **Secure Database Credentials:**

   - Never commit `.env` to version control
   - Use strong passwords
   - Restrict database user privileges

3. **Enable HTTPS:**

   - Use WSS (WebSocket Secure) for WebSocket connections
   - Configure SSL certificates
   - Update `WS_PROTOCOL=wss`

4. **Rate Limiting:**

   - Implement the rate limiting system in `BaseController.php`
   - Use Redis for distributed rate limiting

5. **CORS Configuration:**

   - Restrict `CORS_ALLOWED_ORIGINS` to specific domains
   - Remove wildcard (`*`) from production

6. **Input Validation:**
   - All inputs are validated through `OrderValidator`
   - SQL injection protection via PDO prepared statements
   - XSS protection via `sanitize()` method

## 🚢 Deployment

### Traditional Server (Apache/Nginx)

1. Upload files to web root
2. Configure `.env` for production
3. Set up supervisor for WebSocket:
   ```ini
   [program:websocket]
   command=php /path/to/server.php
   autostart=true
   autorestart=true
   ```
4. Configure web server virtual host
5. Run migrations: `php migrate.php`

### Docker Deployment

```bash
docker-compose -f docker-compose.yml up -d
```

### Cloud Platforms (Fly.io, Heroku)

Configuration files included:

- `fly.toml` - Fly.io configuration
- `Procfile` - Heroku configuration

## 🤝 Contributing

1. Fork the repository
2. Create feature branch: `git checkout -b feature-name`
3. Commit changes: `git commit -am 'Add feature'`
4. Push to branch: `git push origin feature-name`
5. Submit pull request

## 📄 License

This project is open-source and available under the MIT License.

## 👤 Author

**Ahmed Saeed**

- GitHub: [@yahongie2014](https://github.com/yahongie2014)
- Project: [PentavalueTask](https://github.com/yahongie2014/PentavalueTask)

## 🙏 Acknowledgments

- Ratchet for WebSocket implementation
- Predis for Redis client
- OpenWeatherMap for weather API
- Tailwind CSS for styling

---

**Need Help?** Open an issue on GitHub or contact the maintainer.
