<div align="center">
  <!-- Consider replacing with PKKI ITERA logo -->
  <img src="https://i.postimg.cc/4djrcJXx/logo.png" alt="PKKI ITERA logo" width="200"/>

  <h1>PKKI ITERA</h1>
</div>

<p align="center">

</p>

#### Features

- 🛡 Role-based access control using Filament Shield
- 👨🏻‍🦱 Customizable user profiles with Filament Breezy
- 🌌 Comprehensive media management with Filament Spatie Media
- 🖼 Customizable admin panel themes
- 💌 Dynamic mail configuration
- 🅻 Multi-language support with built-in language generator
- ⚙️ Complete website settings management
- 🔎 SEO optimization tools
- 📨 Contact form and inbox management
- 📱 Social media integration
- And more...

#### Getting Started

Clone the repository:

```bash
git clone https://github.com/labtekozt/pkki.itera.git
cd pkki_itera
```

Setup your environment:

```bash
cp .env.example .env
# Configure your database and other settings in .env
```

Install dependencies:

```bash
composer install
npm install
```

Run migrations & seeders:

```bash
php artisan migrate
php artisan db:seed
```

<p align="center">or</p>

```bash
php artisan migrate:fresh --seed
```

Generate permissions & policies:

```bash
php artisan shield:generate --all
```

Generate application key:

```bash
php artisan key:generate
```

Build assets:

```bash
npm run dev
# OR for production
npm run build
```

Start the development server:

```bash
php artisan serve
```

Access the admin panel at `/admin` using:

```bash
email: superadmin@hki.itera.ac.id
password: superadmin
```
# Make sure to change these credentials in production
```

#### Performance Optimization

For optimal performance in production:

```bash
php artisan icons:cache
php artisan route:cache
php artisan view:cache
php artisan config:cache
```

Learn more about [improving Filament panel performance](https://filamentphp.com/docs/3.x/panels/installation#improving-filament-panel-performance).

#### Language Support

This project includes a language generator for easy localization:

```bash
php artisan superduper:lang-translate [from] [to]
```

Example usage:

```bash
# Single language translation
php artisan superduper:lang-translate en id

# Multiple languages
php artisan superduper:lang-translate en id ar fr

# For JSON translation files
php artisan superduper:lang-translate en id --json
```

#### Included Plugins

This project leverages several Filament plugins:

| **Plugin**                                                                                          | **Purpose**                                         |
| :-------------------------------------------------------------------------------------------------- | :-------------------------------------------------- |
| [Filament Spatie Media Library](https://github.com/filamentphp/spatie-laravel-media-library-plugin) | Media management                                    |
| [Filament Spatie Settings](https://github.com/filamentphp/spatie-laravel-settings-plugin)           | Site configuration                                  |
| [Shield](https://github.com/bezhanSalleh/filament-shield)                                           | Permissions management                              |
| [Breezy](https://github.com/jeffgreco13/filament-breezy)                                            | User profile management                             |
| [Logger](https://github.com/z3d0x/filament-logger)                                                  | System activity logging                             |
| [Filament Menu Builder](https://github.com/datlechin/filament-menu-builder)                         | Custom navigation menus                             |
<!-- Keep other plugins that are actually used in your project -->

### License

This project is proprietary software for PKKI ITERA.
© Institut Teknologi Sumatera.

### Contact
For support or inquiries, please contact the PKKI ITERA team.
- [PKKI ITERA](https://pkki.itera.ac.id)
- [projeksainsdata](https://projeksainsdata.com/)
- [labtekozt](https://github.com/labtekozt)