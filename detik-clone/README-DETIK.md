# DetikClone - News Portal Application

A comprehensive news portal application built with Laravel 11, Inertia.js, Vue 3, and Tailwind CSS. This is a clone of the popular Indonesian news website Detik.com.

## 🚀 Features

### Core Features
- **Multi-Category News System** - Organize news with unlimited nested categories
- **Rich Content Management** - Support for articles, photo galleries, videos, and infographics
- **Advanced Article Editor** - Rich text editing with media embedding
- **SEO Optimization** - Complete meta tags and social media optimization
- **Comment System** - Threaded comments with moderation
- **User Management** - Role-based access control (Admin, Editor, Author, Subscriber)
- **Real-time Breaking News** - Dynamic breaking news banner
- **Article Scheduling** - Schedule articles for future publication
- **Tag System** - Flexible tagging with trending support

### Technical Features
- **Modern Tech Stack** - Laravel 11 + Inertia.js + Vue 3 + TypeScript
- **Responsive Design** - Mobile-first responsive layout
- **Admin Panel** - Filament 3.x for content management
- **Database Optimization** - Indexed queries and relationship optimization
- **Asset Management** - Vite 5.x for fast builds and HMR
- **Type Safety** - Full TypeScript support

## 🛠 Tech Stack

### Backend
- **Laravel 11.x** - PHP framework
- **MySQL 8.0** - Primary database
- **Redis 7.x** - Caching and sessions (optional)
- **Filament 3.x** - Admin panel

### Frontend
- **Inertia.js 2.0** - SPA without API
- **Vue 3** - Frontend framework with Composition API
- **TypeScript** - Type safety
- **Tailwind CSS 3.4** - Utility-first CSS framework
- **Vite 5.x** - Build tool

### Development Tools
- **Laravel Pint** - PHP code formatting
- **Laravel Sail** - Docker development environment (optional)
- **NPM** - Package management

## 📋 Requirements

- PHP 8.2+
- Node.js 20.x LTS
- Composer 2.x
- MySQL 8.0+ or SQLite (for development)

## ⚡ Quick Start

### 1. Clone the Repository
```bash
git clone https://github.com/your-username/detik-clone.git
cd detik-clone
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

# Configure database in .env file
DB_CONNECTION=sqlite
# OR for MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=detik_clone
# DB_USERNAME=your_username
# DB_PASSWORD=your_password
```

### 4. Database Setup
```bash
# Run migrations
php artisan migrate

# Seed sample data
php artisan db:seed --class=CategorySeeder
```

### 5. Create Admin User
```bash
php artisan make:filament-user
```

### 6. Build Assets
```bash
# For development
npm run dev

# For production
npm run build
```

### 7. Start Development Servers
```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server (for HMR)
npm run dev
```

## 🎯 Usage

### Accessing the Application
- **Frontend**: http://localhost:8000
- **Admin Panel**: http://localhost:8000/admin

### Default Login
Use the credentials you created with `php artisan make:filament-user`

## 📁 Project Structure

```
detik-clone/
├── app/
│   ├── Filament/          # Admin panel resources
│   ├── Http/
│   │   ├── Controllers/   # HTTP controllers
│   │   └── Middleware/    # Custom middleware
│   └── Models/           # Eloquent models
├── database/
│   ├── migrations/       # Database migrations
│   └── seeders/         # Database seeders
├── resources/
│   ├── css/             # Stylesheets
│   ├── js/              # Vue.js components and TypeScript
│   │   ├── Components/  # Reusable components
│   │   ├── Layouts/     # Layout components
│   │   ├── Pages/       # Page components
│   │   └── types/       # TypeScript definitions
│   └── views/           # Blade templates
├── routes/
│   └── web.php          # Web routes
└── public/              # Public assets
```

## 🗄 Database Schema

### Core Tables
- `users` - System users with roles
- `categories` - News categories (supports nesting)
- `articles` - Main content with rich metadata
- `tags` - Article tagging system
- `comments` - Threaded comment system
- `newsletters` - Email subscription management

### Key Relationships
- Articles belong to Categories and Users (author)
- Articles have many Tags (many-to-many)
- Articles have many Comments (threaded)
- Categories support parent-child relationships
- Users have role-based permissions

## 🎨 Design System

### Colors
```css
--detik-red: #C4171D
--detik-dark: #1a1a1a
--detik-gray: #6B7280
```

### Typography
- Primary Font: Inter
- Fallback: Roboto, system fonts

## 🔧 Configuration

### Environment Variables
```env
APP_NAME="DetikClone"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite

# Cache (Optional)
CACHE_DRIVER=file
SESSION_DRIVER=database

# Mail (Optional)
MAIL_MAILER=smtp
```

## 🚀 Deployment

### Production Build
```bash
# Install dependencies
composer install --optimize-autoloader --no-dev
npm ci

# Build assets
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Server Requirements
- PHP 8.2+ with required extensions
- Web server (Apache/Nginx)
- MySQL 8.0+
- Redis (recommended for caching)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - The PHP framework
- [Inertia.js](https://inertiajs.com) - Modern monolith approach  
- [Vue.js](https://vuejs.org) - Progressive JavaScript framework
- [Tailwind CSS](https://tailwindcss.com) - Utility-first CSS framework
- [Filament](https://filamentphp.com) - Laravel admin panel
- [Detik.com](https://detik.com) - Design inspiration

## 📞 Support

For support and questions, please open an issue on GitHub or contact the development team.

---

**Built with ❤️ by the DetikClone Team**