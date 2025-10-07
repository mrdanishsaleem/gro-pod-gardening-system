# Installation Guide

## Quick Start

1. **Upload Plugin**
   - Upload the `gro-pod-gardening-system` folder to `/wp-content/plugins/`
   - Or install via WordPress admin > Plugins > Add New > Upload Plugin

2. **Activate Plugin**
   - Go to WordPress Admin > Plugins
   - Find "GRO Pod Gardening System" and click "Activate"

3. **Configure Plants**
   - Go to **GRO Pod Garden** in your admin menu
   - Click **Plant Configuration**
   - Add your first plant with growth cycle and images

4. **Add Dashboard to Page**
   - Create a new page or edit existing page
   - Add the shortcode: `[gro_pod_dashboard]`
   - Publish the page

## Detailed Setup

### Step 1: Plugin Installation

#### Method A: Manual Upload
1. Download the plugin files
2. Extract the ZIP file
3. Upload the `gro-pod-gardening-system` folder to `/wp-content/plugins/`
4. Ensure proper file permissions (755 for folders, 644 for files)

#### Method B: WordPress Admin
1. Go to WordPress Admin > Plugins > Add New
2. Click "Upload Plugin"
3. Choose the plugin ZIP file
4. Click "Install Now"
5. Click "Activate Plugin"

### Step 2: Initial Configuration

1. **Access Admin Panel**
   - Navigate to **GRO Pod Garden** in your WordPress admin menu
   - You'll see the main management dashboard

2. **Configure Plants**
   - Click **Plant Configuration**
   - Add your first plant:
     - **Name**: e.g., "Tomato"
     - **Icon URL**: Link to plant icon image
     - **Main Image URL**: Link to main plant image
     - **Action Images**: URLs for care action images
     - **Growth Duration**: Days to harvest (e.g., 90)

3. **Test the System**
   - Visit the page with `[gro_pod_dashboard]` shortcode
   - Try planting a seed in an empty pod
   - Log some care activities
   - Test the harvest process

### Step 3: Customization (Optional)

#### GamiPress Integration
1. Install and activate GamiPress plugin
2. Create custom badges for gardening achievements
3. Configure point systems and rewards

#### BuddyBoss Integration
1. Install and activate BuddyBoss plugin
2. Enable community features
3. Configure activity tracking

#### Custom Styling
1. Add custom CSS to your theme
2. Override default styles as needed
3. Use the provided CSS classes for targeting

### Step 4: User Onboarding

1. **Create User Guide**
   - Write instructions for your users
   - Explain the pod system and growth cycles
   - Show how to earn badges and streaks

2. **Set Up Community**
   - Enable community features if using BuddyBoss
   - Create community guidelines
   - Encourage user engagement

## Troubleshooting

### Common Issues

#### Plugin Not Activating
- Check PHP version (requires 7.4+)
- Check WordPress version (requires 5.0+)
- Check file permissions
- Check for plugin conflicts

#### Shortcode Not Working
- Ensure shortcode is correctly added: `[gro_pod_dashboard]`
- Check if page is published
- Verify user is logged in (if required)

#### Images Not Displaying
- Verify image URLs are correct and accessible
- Check image file permissions
- Ensure images are in supported formats (PNG, JPG, GIF)

#### Badges Not Working
- Install and activate GamiPress plugin
- Check badge configuration
- Verify user permissions

### Debug Mode

Enable WordPress debug mode for detailed error messages:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### File Permissions

Ensure proper file permissions:
- Folders: 755
- Files: 644
- wp-content/plugins/: 755

## Performance Optimization

### Caching
- Enable WordPress caching
- Use a caching plugin like WP Rocket or W3 Total Cache
- Configure object caching if available

### Images
- Optimize plant images for web
- Use appropriate image sizes
- Consider lazy loading for better performance

### Database
- Regular database optimization
- Clean up old user data if needed
- Monitor database size

## Security Considerations

### User Permissions
- Review user capabilities
- Limit admin access appropriately
- Use strong passwords

### Data Protection
- Regular backups
- Secure file uploads
- Input validation

## Support

If you encounter issues:

1. Check this installation guide
2. Review the README.md file
3. Check the CHANGELOG.md for known issues
4. Contact the plugin developer

## Next Steps

After successful installation:

1. **Test All Features**
   - Plant management
   - Care logging
   - Harvest process
   - Badge system
   - Community features

2. **Customize for Your Needs**
   - Add more plants
   - Configure badges
   - Set up community features
   - Customize styling

3. **Train Your Users**
   - Create user documentation
   - Provide tutorials
   - Set up support channels

4. **Monitor and Maintain**
   - Regular updates
   - Performance monitoring
   - User feedback collection

---

**Happy Gardening!** 🌱
