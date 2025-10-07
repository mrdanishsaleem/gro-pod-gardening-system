# GRO Pod Gardening System

A comprehensive WordPress plugin that transforms your website into an interactive hydroponic gardening experience. Users can manage virtual plant pods, track growth progress, earn achievements, and engage with the community.

## 🌱 Features

### Virtual Garden Management
- **12-Pod System**: Manage up to 12 virtual plant pods
- **Plant Library**: Configurable plant database with growth cycles
- **Real-time Tracking**: Monitor plant growth and harvest times
- **Interactive Dashboard**: Beautiful, responsive garden interface

### Gamification & Achievements
- **Badge System**: Earn badges for various gardening activities
- **Streak Tracking**: Daily and weekly care streaks
- **Progress Rewards**: Unlock achievements as you grow
- **GamiPress Integration**: Full compatibility with GamiPress plugin

### Community Features
- **Social Engagement**: Create posts, comments, and share tips
- **Community Badges**: Earn rewards for community participation
- **Activity Tracking**: Monitor your gardening journey

### Admin Management
- **Plant Configuration**: Add, edit, and manage plant varieties
- **User Statistics**: Track user engagement and progress
- **Customizable Settings**: Flexible configuration options

## 🚀 Installation

1. Upload the plugin files to `/wp-content/plugins/gro-pod-gardening-system/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your plants in the admin area
4. Add the `[gro_pod_dashboard]` shortcode to any page or post

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- (Optional) GamiPress plugin for enhanced gamification

## 🎯 Usage

### For Users
1. Visit the page with the `[gro_pod_dashboard]` shortcode
2. Click on empty pods to plant your first seeds
3. Log daily care activities to maintain streaks
4. Harvest plants when they're ready
5. Earn badges and participate in the community

### For Administrators
1. Go to **GRO Pod Garden** in your WordPress admin menu
2. Configure plant varieties, growth cycles, and images
3. Monitor user activity and engagement
4. Customize settings as needed

## 🔧 Configuration

### Plant Setup
- Navigate to **GRO Pod Garden > Plant Configuration**
- Add new plants with custom growth cycles
- Upload plant icons and action images
- Set growth duration in days

### Badge Configuration
- Install and activate GamiPress plugin
- Create custom badges for gardening achievements
- Configure badge requirements and rewards

## 📱 Shortcodes

### Main Dashboard
```
[gro_pod_dashboard]
```
Displays the complete garden dashboard with pods, streaks, and badges.

## 🎨 Customization

### CSS Classes
- `.dashboard` - Main dashboard container
- `.pod` - Individual pod styling
- `.plants-grid` - Plant selection grid
- `.badges-section` - Badge display area

### Hooks & Filters
- `tpgs_before_dashboard` - Before dashboard content
- `tpgs_after_dashboard` - After dashboard content
- `tpgs_plant_harvested` - When a plant is harvested

## 🔌 Integration

### GamiPress
Full integration with GamiPress for:
- Custom achievement types
- Point systems
- User rankings
- Advanced gamification

### BuddyBoss
Community features integration:
- Activity tracking
- Social engagement
- User interactions

## 📊 Database

The plugin creates the following user meta fields:
- `tpgs_pod_1` to `tpgs_pod_12` - Individual pod data
- `tpgs_gamification_stats` - User statistics
- `tpgs_intro_completed` - Tutorial completion status

## 🛠️ Development

### File Structure
```
gro-pod-gardening-system/
├── gro-pod-gardening-system.php    # Main plugin file
├── includes/                       # Core classes
│   ├── class-pod-manager.php      # Pod management
│   ├── class-plant-manager.php    # Plant configuration
│   ├── class-gamipress-integration.php
│   └── ...
├── templates/                      # Frontend templates
│   ├── frontend/
│   └── admin/
├── assets/                         # CSS, JS, images
└── uninstall.php                  # Cleanup script
```

### AJAX Actions
- `tpgs_plant_plant` - Plant a seed
- `tpgs_harvest_pod` - Harvest a plant
- `tpgs_log_streak` - Log care activities
- `tpgs_refresh_badges` - Update badge display

## 🐛 Troubleshooting

### Common Issues
1. **Pods not displaying**: Check if shortcode is properly added
2. **Images not loading**: Verify image URLs in plant configuration
3. **Badges not working**: Ensure GamiPress is installed and activated

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📈 Performance

- Optimized database queries
- Cached plant data
- Minified CSS and JavaScript
- Lazy loading for images

## 🔒 Security

- Nonce verification for all AJAX requests
- Capability checks for admin functions
- Sanitized user inputs
- Escaped output data

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

## 🤝 Support

For support, feature requests, or bug reports, please contact the plugin developer.

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🙏 Credits

- **Developer**: Danish Saleem
- **Version**: 2.1.1
- **Last Updated**: 2024

---

**GRO Pod Gardening System** - Growing communities, one pod at a time! 🌱
