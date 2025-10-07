# Changelog

All notable changes to the GRO Pod Gardening System plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.1] - 2024-12-19

### Added
- Comprehensive README.md documentation
- Detailed CHANGELOG.md file
- GRO Pod branding throughout the plugin
- Plant-based terminology (replaced "vegetables" with "plants")
- Enhanced admin page titles and organization

### Changed
- **BREAKING**: Renamed main plugin file from `twelve-pod-gardening-system.php` to `gro-pod-gardening-system.php`
- **BREAKING**: Renamed class from `Twelve_Pod_Gardening_System` to `GRO_Pod_Gardening_System`
- **BREAKING**: Renamed `class-vegetable-manager.php` to `class-plant-manager.php`
- **BREAKING**: Renamed `TPGS_Vegetable_Manager` to `TPGS_Plant_Manager`
- **BREAKING**: Renamed `dashboard-v3.php` to `gro-dashboard.php`
- **BREAKING**: Renamed `dashboard-v3.css` to `gro-dashboard.css`
- **BREAKING**: Renamed `vegetables-config.php` to `plant-config.php`
- **BREAKING**: Updated shortcode from `[tpgs_garden_dashboard]` to `[gro_pod_dashboard]`
- **BREAKING**: Updated AJAX action from `tpgs_plant_vegetable` to `tpgs_plant_plant`
- **BREAKING**: Updated database option from `tpgs_vegetables` to `tpgs_plants`
- **BREAKING**: Updated admin page slug from `tpgs_vegetables_config` to `tpgs_plants_config`
- Updated all user-facing text from "vegetables" to "plants"
- Updated admin menu titles for better clarity
- Updated CSS classes from `vegetables-grid` to `plants-grid`
- Updated CSS classes from `vegetable-item` to `plant-item`
- Updated variable names from `vegetable_id` to `plant_id`
- Updated function names from `get_vegetables()` to `get_plants()`
- Updated function names from `get_vegetable()` to `get_plant()`
- Updated text domain from `twelve-pod-gardening` to `gro-pod-gardening`
- Updated all "12-Pod" references to "GRO Pod"
- Updated admin page titles for better branding
- Improved code organization and structure

### Fixed
- Fixed tutorial skip issue for new users across different accounts
- Fixed streak reset inconsistencies on page refresh
- Fixed Community Cultivator badge unlocking logic
- Fixed gamification stats initialization
- Fixed real community action tracking
- Fixed AJAX URL inconsistencies in frontend JavaScript
- Fixed duplicate AJAX handlers
- Fixed missing badge notification styles
- Fixed syntax errors in JavaScript files
- Fixed lock icon display on locked badges
- Fixed cross-account tutorial completion issues

### Removed
- Removed unused `dashboard.php` template file
- Removed all debug logs (`error_log` and `console.log` statements)
- Removed commented-out code blocks
- Removed duplicate AJAX handlers
- Removed lock icons from locked badges
- Removed embedded CSS and JavaScript from templates
- Removed old file references and unused functions

### Security
- Enhanced nonce verification for all AJAX requests
- Improved input sanitization
- Better capability checks for admin functions

### Performance
- Moved all CSS to dedicated files
- Moved all JavaScript to dedicated files
- Optimized asset loading
- Improved database query efficiency
- Removed redundant code and functions

## [2.0.0] - 2024-12-18

### Added
- Complete rewrite of the plugin architecture
- New dashboard interface (v3)
- Enhanced gamification system
- Community engagement features
- Badge notification system
- Streak tracking system
- Plant care activity logging
- Harvest confirmation modals
- Intro tutorial for new users
- Admin plant configuration interface
- GamiPress integration
- BuddyBoss community integration

### Changed
- Migrated from basic pod system to full gardening experience
- Updated UI/UX with modern design
- Improved responsive design
- Enhanced user experience flow

## [1.0.0] - 2024-12-17

### Added
- Initial release
- Basic 12-pod system
- Simple plant management
- Basic user interface
- WordPress integration

---

## Version History Summary

- **v2.1.1** - Major rebranding and code cleanup
- **v2.0.0** - Complete feature overhaul and modernization
- **v1.0.0** - Initial plugin release

## Migration Notes

### From v2.0.0 to v2.1.1
- Update any custom shortcodes from `[tpgs_garden_dashboard]` to `[gro_pod_dashboard]`
- Update any custom CSS classes that reference `vegetable-*` to `plant-*`
- Update any custom AJAX calls to use new action names
- Update database references from `tpgs_vegetables` to `tpgs_plants`

### Database Changes
- No database schema changes in v2.1.1
- All existing user data is preserved
- Plant data is migrated from `tpgs_vegetables` to `tpgs_plants` option

## Support

For questions about this changelog or migration assistance, please contact the plugin developer.
