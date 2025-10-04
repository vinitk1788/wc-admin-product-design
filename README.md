# WooCommerce Admin Product Design

A WordPress plugin that adds admin-only product-specific design image fields to WooCommerce product edit screens. Perfect for storing custom design files, mockups, or reference images that are only visible to administrators and shop managers.

## Features

- 🖼️ **Media Library Integration** - Upload images directly through WordPress Media Library
- 🔗 **URL Support** - Alternative option to use external image URLs
- 📥 **Download Original Files** - One-click download of full-resolution design images
- 🔒 **Admin-Only Access** - Secure access control with configurable user capabilities
- 🎨 **Clean UI** - Intuitive interface with color-coded buttons and image previews
- ⚡ **AJAX Powered** - Smooth user experience with asynchronous operations

## Screenshots

The plugin adds a new "Admin design image" section to your WooCommerce product edit screen with:
- Image preview with bordered container
- Blue "Upload / Select Image" button
- Green "Download Original File" button
- Red "Remove image" button
- Optional URL input field

## Installation

1. Download or clone this repository into your WordPress plugins directory:
   ```
   wp-content/plugins/wc-admin-product-design/
   ```

2. Activate the plugin through the WordPress admin:
   - Go to `Plugins` → `Installed Plugins`
   - Find "WooCommerce Admin Product Design"
   - Click `Activate`

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.4 or higher

## Usage

### Accessing the Feature

1. **Login Requirements**: You must be logged in as an Administrator or user with `edit_products` capability
2. **Navigate to Products**: Go to `WooCommerce` → `Products`
3. **Edit Product**: Click on any product to edit
4. **Find Design Section**: Look for "Admin design image" in the Product Data section

### Uploading Images

**Method 1: Media Library Upload**
1. Click the "Upload / Select Image" button
2. Choose "Upload files" to upload new images or "Media Library" to select existing ones
3. Select your image and click "Use this image"
4. The image preview will appear with download and remove options

**Method 2: URL Input**
1. Enter a direct image URL in the "Admin design image URL" field
2. The image will automatically appear in the preview
3. Download and remove buttons will become available

### Downloading Original Files

1. Once an image is uploaded or URL is entered, the green "Download Original File" button appears
2. Click the button to download the full-resolution original image
3. For uploaded files: Downloads from WordPress media library
4. For URL images: Downloads directly from the source URL

## Configuration

### User Permissions

By default, the plugin requires `edit_products` capability. To change this:

```php
// In the plugin file, modify line 26:
const REQUIRED_CAPABILITY = 'manage_options'; // Admin only
const REQUIRED_CAPABILITY = 'edit_products';  // Shop managers + admins
```

### Custom Styling

The plugin includes built-in CSS styling. To customize:

```css
/* Upload button */
.wcpd_upload_button {
    color: #0073aa !important;
    border-color: #0073aa !important;
}

/* Download button */
.wcpd_download_button {
    color: #00a32a !important;
    border-color: #00a32a !important;
}

/* Remove button */
.wcpd_remove_button {
    color: #d63638 !important;
    border-color: #d63638 !important;
}
```

## API Reference

### Public Methods

#### `WCPD::get_design_image($product)`
Retrieve the design image URL for a product.

**Parameters:**
- `$product` (WC_Product|int) - WooCommerce product object or product ID

**Returns:**
- `string` - Image URL or empty string if no image

**Example:**
```php
$product_id = 123;
$design_image_url = WCPD::get_design_image($product_id);
if ($design_image_url) {
    echo '<img src="' . esc_url($design_image_url) . '" alt="Design Image">';
}
```

### Hooks and Filters

The plugin uses standard WordPress/WooCommerce hooks:

- `woocommerce_product_options_general_product_data` - Adds the admin fields
- `woocommerce_admin_process_product_object` - Saves the product meta
- `admin_enqueue_scripts` - Loads JavaScript and CSS
- `wp_ajax_wcpd_download_original` - Handles AJAX download requests

## Database Schema

The plugin stores data in WordPress post meta:

| Meta Key | Description | Example |
|----------|-------------|---------|
| `_wcpd_design_image_id` | WordPress attachment ID | `142` |
| `_wcpd_design_image_url` | External image URL | `https://example.com/image.jpg` |

## Security Features

- **Nonce Verification** - All AJAX requests are nonce-verified
- **Capability Checking** - User permissions verified on every action
- **Input Sanitization** - All inputs are properly sanitized and escaped
- **URL Validation** - External URLs are validated before saving

## Troubleshooting

### Upload Not Working
- Ensure you're logged in as Administrator or Shop Manager
- Check WordPress file upload permissions
- Verify `upload_max_filesize` and `post_max_size` in PHP settings

### Download Not Working
- Check browser console for JavaScript errors
- Verify user has proper capabilities
- Ensure image file exists and is accessible

### Images Not Appearing
- Check if WooCommerce is active
- Verify user permissions
- Look for JavaScript conflicts with other plugins

## Changelog

### Version 1.0.0
- Initial release
- Media library integration
- URL input support
- Download original file functionality
- Admin-only access control
- Responsive UI design

## Development

### File Structure
```
wc-admin-product-design/
├── wc-admin-product-design.php  # Main plugin file
└── README.md                    # This documentation
```

### Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the GPL v2 or later - see the WordPress.org plugin guidelines for details.

## Support

For support, feature requests, or bug reports:
1. Check the troubleshooting section above
2. Review WordPress and WooCommerce documentation
3. Create an issue in the repository

## Credits

- Built for WooCommerce compatibility
- Uses WordPress Media Library API
- Follows WordPress coding standards
- Responsive design principles

---

**Made with ❤️ for WooCommerce merchants who need to manage product design files efficiently.**
