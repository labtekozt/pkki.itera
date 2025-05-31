# PKKI ITERA - Pusat Kelola Kekayaan Intelektual Institut Teknologi Sumatera

<div align="center">

<img src="https://i.postimg.cc/4djrcJXx/logo.png" alt="PKKI ITERA logo" width="200"/>

[![Laravel](https://img.shields.io/badge/Laravel-11.9%2B-red?style=for-the-badge&logo=laravel)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-3.2%2B-orange?style=for-the-badge&logo=filament)](https://filamentphp.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue?style=for-the-badge&logo=php)](https://php.net)
[![React](https://img.shields.io/badge/React-18%2B-blue?style=for-the-badge&logo=react)](https://reactjs.org)
[![Inertia](https://img.shields.io/badge/Inertia.js-1.0%2B-purple?style=for-the-badge&logo=inertia)](https://inertiajs.com)

**Modern Laravel application for intellectual property management with Filament admin panel and React frontend**

[Features](#-features) • [Installation](#-quick-start) • [Documentation](#-documentation) • [Contributing](#-contributing) • [License](#-license)

</div>

---

## 🌟 Project Overview

PKKI ITERA is a comprehensive intellectual property management system designed for Institut Teknologi Sumatera. The application facilitates the submission, review, and management of various types of intellectual property including patents, trademarks, copyrights, and industrial designs.

### 🎯 Key Features

- **📋 Intellectual Property Management**: Handle patents, trademarks, copyrights, and industrial designs
- **👥 User Management**: Role-based access control with multiple user types (super_admin, admin, civitas, non-civitas)
- **📊 Admin Dashboard**: Comprehensive Filament-based admin panel with analytics
- **📱 Responsive UI**: Modern React frontend with Inertia.js integration
- **📄 Document Management**: Secure file upload and management with Spatie Media Library
- **🔄 Workflow System**: Customizable approval workflows for different IP types
- **📈 Reporting & Analytics**: Detailed statistics and reports with visual charts
- **🔐 Security**: Multi-layer authentication, authorization, and data protection
- **🌐 Multi-language Support**: Available in Indonesian and English
- **📧 Notifications**: Email and system notifications for status updates
- **🔍 Advanced Search**: Full-text search across submissions and documents
- **📱 Mobile Optimized**: Responsive design for all device types

## 🚀 Technology Stack

### Backend
- **Laravel 11.9+**: Modern PHP framework with latest features
- **Filament 3.2+**: Admin panel and dashboard with advanced components
- **Spatie Permission**: Role and permission management system
- **Spatie Media Library**: File and media handling with conversions
- **Laravel Sanctum**: API authentication and SPA protection
- **MySQL**: Primary database with full-text search capabilities

### Frontend
- **Inertia.js**: Modern monolith approach linking Laravel and React
- **React 18+**: Component-based UI with hooks and context
- **Tailwind CSS**: Utility-first styling with custom components
- **Alpine.js**: Lightweight JavaScript framework for interactions
- **Vite**: Fast build tool and development server

### Development & Deployment
- **Composer**: PHP dependency management
- **NPM/Yarn**: Node.js package management
- **PHPUnit**: Testing framework
- **Laravel Pint**: Code styling and formatting
- **Docker**: Containerization support
- **GitHub Actions**: CI/CD pipeline integration

## 🏗 Architecture

The application follows modern Laravel architecture principles:

- **Domain-Driven Design (DDD)**: Organized by business domains
- **Repository Pattern**: Data access abstraction
- **Service Layer**: Business logic separation
- **Event-Driven**: Decoupled components with events/listeners
- **SOLID Principles**: Clean, maintainable code structure

## ⚡ Requirements

- **PHP** 8.2 or higher
- **Composer** 2.0+
- **Node.js** 18+ and NPM/Yarn
- **MySQL** 8.0+ or **PostgreSQL** 13+
- **Redis** (optional, for caching and queues)
- **Web Server** (Apache/Nginx)

## 🚀 Quick Start

### 1. Clone Repository
```bash
git clone https://github.com/labtekozt/pkki.itera.git
cd pkki.itera
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 3. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env file
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=pkki_itera
# DB_USERNAME=your_username
# DB_PASSWORD=your_password
```

### 4. Database Setup
```bash
# Run migrations
php artisan migrate

# Seed the database with initial data
php artisan db:seed

# Generate Filament Shield permissions
php artisan shield:generate --all
```

### 5. Storage Setup
```bash
# Create storage link
php artisan storage:link

# Set permissions (Linux/Mac)
chmod -R 775 storage bootstrap/cache
```

### 6. Build Assets
```bash
# Development build
npm run dev

# Production build
npm run build
```

### 7. Start Development Server
```bash
# Start Laravel development server
php artisan serve

# In another terminal, start Vite dev server
npm run dev
```

### 8. Access the Application

- **Frontend**: http://localhost:8000
- **Admin Panel**: http://localhost:8000/admin

**Default Admin Credentials:**
- Email: `admin@pkki.itera.ac.id`
- Password: `password`

> ⚠️ **Security Note**: Change default credentials immediately in production!

## 📋 Project Structure

```
pkki_itera/
├── app/
│   ├── Filament/           # Filament admin panel resources
│   ├── Http/               # Controllers, middleware, requests
│   ├── Models/             # Eloquent models
│   ├── Services/           # Business logic services
│   ├── Repositories/       # Data access layer
│   └── Policies/           # Authorization policies
├── database/
│   ├── migrations/         # Database schema migrations
│   ├── seeders/           # Database seeders
│   └── factories/         # Model factories for testing
├── resources/
│   ├── js/                # React components and pages
│   ├── css/               # Stylesheets
│   └── views/             # Blade templates
├── routes/
│   ├── web.php            # Web routes
│   ├── api.php            # API routes
│   └── channels.php       # Broadcast channels
├── storage/
│   ├── app/               # Application files
│   ├── framework/         # Framework cache/sessions
│   └── logs/              # Application logs
├── tests/                 # PHPUnit tests
├── public/                # Web root directory
└── docs/                  # Documentation files
```

### Database & Storage
- **MySQL/MariaDB**: Primary database
- **Redis**: Caching and sessions (optional)
- **Local/S3**: File storage options

## 📋 Requirements

- **PHP**: 8.2 or higher
- **Composer**: Latest version
- **Node.js**: 18+ with npm
- **MySQL/MariaDB**: 5.7+ / 10.3+
- **Web Server**: Nginx/Apache with SSL support

### PHP Extensions Required
```
- BCMath, Ctype, cURL, DOM, Fileinfo
- JSON, Mbstring, OpenSSL, PCRE, PDO
- Tokenizer, XML, GD or Imagick, Zip
```

## ⚡ Quick Start

### 1. Clone Repository
```bash
git clone https://github.com/labtekozt/pkki.itera.git
cd pkki.itera
```

### 2. Install Dependencies
```bash
# PHP dependencies
composer install

# Node.js dependencies
npm install
```

### 3. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your .env file with database credentials
```

### 4. Database Setup
```bash
# Run migrations
php artisan migrate

# Seed database with initial data
php artisan db:seed

# Generate Filament Shield permissions
php artisan shield:generate --all
```

### 5. Build Assets
```bash
# Development
npm run dev

# Production
npm run build
```

### 6. Start Development Server
```bash
php artisan serve
```

Visit `http://localhost:8000` to access the application and `http://localhost:8000/admin` for the admin panel.

### 7. Default Admin Access
```bash
Email: superadmin@hki.itera.ac.id
Password: superadmin
```
**⚠️ Important: Change these credentials immediately in production!**

## 📚 Documentation

### Quick Links
- [📖 Full Documentation](docs/README.md)
- [🚀 Deployment Guide](docs/DEPLOYMENT_GUIDE.md)
- [🐳 Docker Deployment](docs/DOCKER_DEPLOYMENT.md)
- [🖥️ VPS Deployment](docs/VPS_DEPLOYMENT.md)
- [🌐 cPanel Deployment](docs/CPANEL_DEPLOYMENT.md)
- [⚡ Quick Commands](docs/QUICK_DEPLOYMENT_COMMANDS.md)

### Architecture
- [🏗️ Database Schema](docs/database-schema.md)
- [📋 Testing Plan](docs/TESTING_PLAN.md)
- [🎨 UI/UX Guidelines](docs/UI_UX_Improvements_Summary.md)

## 🔧 Development

### Local Development Setup
```bash
# Start development server
php artisan serve

# Watch for file changes (separate terminal)
npm run dev

# Run tests
php artisan test

# Code formatting
./vendor/bin/pint
```

### Creating Admin User
```bash
# Create admin user via Artisan
php artisan make:filament-user

# Or assign super admin role
php artisan shield:super-admin --user=admin@example.com
```

### Language Support
Generate translations easily:
```bash
# Single language translation
php artisan superduper:lang-translate en id

# Multiple languages
php artisan superduper:lang-translate en id ar fr

# For JSON translation files
php artisan superduper:lang-translate en id --json
```

### Performance Optimization
For production optimization:
```bash
php artisan icons:cache
php artisan route:cache
php artisan view:cache
php artisan config:cache
```

## 🏗️ Project Structure

```
pkki_itera/
├── app/
│   ├── Filament/           # Filament admin resources
│   ├── Http/               # Controllers, middleware
│   ├── Models/             # Eloquent models
│   ├── Services/           # Business logic
│   └── Policies/           # Authorization policies
├── database/
│   ├── migrations/         # Database migrations
│   ├── seeders/           # Database seeders
│   └── factories/         # Model factories
├── docs/                  # Documentation
├── resources/
│   ├── js/                # React components
│   ├── css/               # Stylesheets
│   └── views/             # Blade templates
├── routes/                # Application routes
├── storage/               # File storage
└── tests/                 # Test suites
```

## 🔌 Included Plugins

This project leverages several powerful Filament plugins:

| **Plugin** | **Purpose** |
|:-----------|:------------|
| [Filament Shield](https://github.com/bezhanSalleh/filament-shield) | Role-based permissions management |
| [Filament Breezy](https://github.com/jeffgreco13/filament-breezy) | User profile management & authentication |
| [Spatie Media Library](https://github.com/filamentphp/spatie-laravel-media-library-plugin) | Advanced media management |
| [Spatie Settings](https://github.com/filamentphp/spatie-laravel-settings-plugin) | Dynamic site configuration |
| [Filament Logger](https://github.com/z3d0x/filament-logger) | System activity logging |
| [Menu Builder](https://github.com/datlechin/filament-menu-builder) | Custom navigation menus |

## 🌍 Deployment Options

### 1. VPS Deployment
Complete server setup with Nginx, PHP-FPM, and SSL certificates.
[📖 VPS Deployment Guide](docs/VPS_DEPLOYMENT.md)

### 2. Shared Hosting (cPanel)
Deploy to shared hosting providers with cPanel interface.
[📖 cPanel Deployment Guide](docs/CPANEL_DEPLOYMENT.md)

### 3. Docker Container
Containerized deployment with Docker Compose.
[📖 Docker Deployment Guide](docs/DOCKER_DEPLOYMENT.md)

### 4. Cloud Platforms
- **AWS**: EC2, RDS, S3 integration
- **DigitalOcean**: Droplets with managed databases
- **Google Cloud**: Compute Engine and Cloud SQL

## 🤝 Contributing

We welcome contributions to improve PKKI ITERA! Please read our [Contributing Guidelines](CONTRIBUTING.md) before submitting pull requests.

### Development Workflow
1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes and test thoroughly
4. Commit with conventional commits: `git commit -m "feat: add amazing feature"`
5. Push to your fork: `git push origin feature/amazing-feature`
6. Create a Pull Request

### Code Standards
- Follow PSR-12 coding standards
- Use meaningful commit messages
- Write tests for new features
- Update documentation as needed

## 🆘 Support

### Getting Help
- **Documentation**: Check our comprehensive [docs](docs/)
- **Issues**: [GitHub Issues](https://github.com/labtekozt/pkki.itera/issues)
- **Discussions**: [GitHub Discussions](https://github.com/labtekozt/pkki.itera/discussions)

### Contact
- **PKKI ITERA**: [https://pkki.itera.ac.id](https://pkki.itera.ac.id)
- **Institut Teknologi Sumatera**: Project sponsor
- **Development Team**: [labtekozt](https://github.com/labtekozt)

## 📄 License

This project is proprietary software for PKKI ITERA.
© 2025 Institut Teknologi Sumatera. All rights reserved.

---

<div align="center">

**Made with ❤️ by PKKI ITERA Team**

[![MIT License](https://img.shields.io/badge/License-Proprietary-red.svg)](LICENSE)
[![GitHub Stars](https://img.shields.io/github/stars/labtekozt/pkki.itera?style=social)](https://github.com/labtekozt/pkki.itera/stargazers)
[![GitHub Forks](https://img.shields.io/github/forks/labtekozt/pkki.itera?style=social)](https://github.com/labtekozt/pkki.itera/network/members)

[⬆ Back to Top](#pkki-itera---pusat-kelola-kekayaan-intelektual-institut-teknologi-sumatera)

</div>