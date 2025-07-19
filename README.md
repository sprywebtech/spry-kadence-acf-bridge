# Spry Kadence ACF Bridge

A WordPress plugin that seamlessly connects Kadence Forms to Advanced Custom Fields with automatic webhook generation and field mapping.

![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/License-GPL%20v2-green)
![Version](https://img.shields.io/badge/Version-1.0.0-orange)

## 🌟 Features

- 🎯 **No Code Required** - Simple admin interface for creating webhooks
- 🔗 **Automatic Webhook Generation** - Copy-paste ready URLs for Kadence forms
- 📝 **Easy Field Mapping** - Just list your ACF field names, plugin handles the rest
- 📁 **File Upload Support** - Automatic attachment handling and media library import
- 🏷️ **Category Mapping** - Auto-assign categories based on form field values
- 🔄 **Multiple Post Types** - Works with any custom post type
- 📊 **Multiple Webhooks** - Create unlimited webhooks for different forms
- 🛡️ **Secure Processing** - Built-in validation and sanitization

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- [Advanced Custom Fields (ACF)](https://wordpress.org/plugins/advanced-custom-fields/) plugin
- [Kadence Blocks](https://wordpress.org/plugins/kadence-blocks/) plugin (recommended)

## 🚀 Installation

### Method 1: Manual Installation
1. Download the plugin files from this repository
2. Upload the `spry-kadence-acf-bridge` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to **Settings → Spry Kadence ACF Bridge**

### Method 2: ZIP Upload
1. Download this repository as a ZIP file
2. Go to **Plugins → Add New → Upload Plugin** in your WordPress admin
3. Upload the ZIP file and activate
4. Go to **Settings → Spry Kadence ACF Bridge**

## 📖 Usage

### Quick Start Guide

1. **Create a webhook:**
   - Go to Settings → Spry Kadence ACF Bridge
   - Click "Create New Webhook"
   - Enter webhook name (e.g., "Contact Form")
   - Select target post type
   - List your ACF field names (one per line)

2. **Configure your Kadence form:**
   - Copy the generated webhook URL
   - In your Kadence form, go to Actions → Webhook
   - Paste the URL
   - Map your form fields to match your ACF field names

3. **Test it:**
   - Submit your form
   - Check your WordPress admin to see the new post with ACF data

### Example Configuration

**ACF Field Names:**
```
client_name
client_email
support_priority
support_description
support_attachments
```

**Kadence Form Field Mapping:**
- Name field → `client_name`
- Email field → `client_email`
- Priority dropdown → `support_priority`
- Message textarea → `support_description`
- File upload → `support_attachments`

### Advanced Features

**Category Mapping:**
Automatically assign categories based on form values:
```
urgent:urgent
high:high-priority
medium:medium-priority
low:low-priority
```

**Custom Post Titles:**
Use any ACF field for automatic post title generation.

## 🔧 Configuration Options

| Setting | Description | Example |
|---------|-------------|---------|
| Webhook Name | Descriptive name for the webhook | "Support Ticket Form" |
| Target Post Type | Where posts will be created | "support-ticket" |
| ACF Field Names | List of field names (one per line) | client_name<br>client_email |
| Title Field | Field to use for post title | "subject" |
| Category Field | Field to use for category mapping | "priority" |
| Category Mapping | value:category-slug pairs | urgent:urgent |
| Taxonomy Name | Target taxonomy for categories | "support-ticket-category" |

## 🛠️ Technical Details

### How It Works

1. **Webhook Creation:** Plugin generates unique webhook URLs for each configuration
2. **Data Processing:** Receives form data via POST request, handles multiple formats (JSON, form-encoded)
3. **Post Creation:** Creates WordPress posts in specified post type
4. **Field Mapping:** Maps form fields to ACF fields automatically
5. **File Handling:** Downloads and imports attachments to WordPress media library
6. **Category Assignment:** Maps field values to taxonomy terms

### Supported Data Formats

- JSON POST data
- URL-encoded form data
- Mixed content types

### Security Features

- Nonce verification for admin actions
- Data sanitization and validation
- User capability checks
- Unique webhook URLs

## 🤝 Contributing

Contributions are welcome! Here's how you can help:

1. **Fork the repository**
2. **Create a feature branch:** `git checkout -b feature/amazing-feature`
3. **Commit your changes:** `git commit -m 'Add amazing feature'`
4. **Push to the branch:** `git push origin feature/amazing-feature`
5. **Open a Pull Request**

### Development Setup

```bash
# Clone the repository
git clone https://github.com/yourusername/spry-kadence-acf-bridge.git

# Create a symlink in your WordPress plugins directory
ln -s /path/to/spry-kadence-acf-bridge /path/to/wordpress/wp-content/plugins/
```

## 📝 Changelog

### 1.0.0 (2025-07-19)
- Initial release
- Basic webhook functionality
- ACF field mapping
- File attachment support
- Category mapping
- Multi-post type support

## 🐛 Bug Reports

Found a bug? Please [open an issue](https://github.com/yourusername/spry-kadence-acf-bridge/issues) with:

- WordPress version
- Plugin version
- Steps to reproduce
- Expected vs actual behavior

## 💡 Feature Requests

Have an idea? [Open an issue](https://github.com/yourusername/spry-kadence-acf-bridge/issues) with the "enhancement" label.

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [Advanced Custom Fields](https://www.advancedcustomfields.com/) for the amazing field management
- [Kadence Blocks](https://www.kadencewp.com/) for powerful form capabilities
- WordPress community for inspiration and support

## 📞 Support

- **Documentation:** This README
- **Issues:** [GitHub Issues](https://github.com/yourusername/spry-kadence-acf-bridge/issues)
- **Website:** [Spry Web Tech](https://sprywebtech.com)

---

**Made with ❤️ by [Spry Web Tech](https://sprywebtech.com)**