# Woolocat - WooCommerce Order Location Analytics

Version: 1.0.0  
Last Updated: January 2, 2025  
Requires WordPress: 5.0 or higher  
Requires WooCommerce: 3.0 or higher  
License: GPL v2 or later

## Description

Woolocat is a powerful WooCommerce plugin that provides advanced order location analytics and visualization tools. It helps store owners understand their order distribution, delivery patterns, and customer concentrations through an interactive map interface.

## Features

### Map Visualization
- Interactive Google Maps integration
- Three view modes:
  - Standard Map View: Shows individual order locations
  - Heat Map View: Visualizes order density
  - Customer Clusters View: Groups nearby orders

### Order Analytics
- Distance calculation from store location
- Estimated delivery times
- Order count per location
- Total revenue per location
- Weather impact analysis

### Order Management
- View orders by location
- Quick access to order details including:
  - Order ID
  - Order Date
  - Customer Name
  - Order Status
  - Total Amount
- Direct links to edit orders in WooCommerce admin

### Data Export
- Export location data for external analysis
- Includes all order metrics and analytics

## Recent Updates

### Version 1.0.0 (January 2, 2025)
- Initial release with core features
- Added heatmap visualization
- Implemented customer clustering
- Added order details modal
- Fixed distance calculation issues
- Improved Google Maps integration
- Added weather impact analysis
- Enhanced security with proper CSP headers

## Known Issues
- Weather data may occasionally take longer to load
- Clustering might need manual refresh in some cases

## Installation

1. Upload the plugin files to `/wp-content/plugins/woolocat`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Woolocat settings and enter your:
   - Google Maps API key
   - Store location
   - Other preferences

## Configuration

### Required API Keys
- Google Maps API key with the following APIs enabled:
  - Maps JavaScript API
  - Places API
  - Geocoding API
  - Distance Matrix API

### Recommended Settings
- Set your store location accurately for proper distance calculations
- Enable all necessary Google Maps libraries in your API key
- Configure map zoom level based on your delivery area

## Troubleshooting

### Common Issues
1. Map not loading:
   - Verify Google Maps API key is valid
   - Check if all required APIs are enabled
   - Clear browser cache

2. Distance calculation errors:
   - Verify store address is correctly set
   - Ensure customer addresses are complete

3. View Orders button not working:
   - Clear browser cache
   - Deactivate and reactivate plugin

## Security

- All AJAX requests are nonce-protected
- Data sanitization on input/output
- CSP headers for secure script loading
- XSS protection implemented

## Future Plans

### Upcoming Features
- Multiple store location support
- Advanced analytics dashboard
- Route optimization
- Delivery zone management
- Customer demographic analysis

### Planned Improvements
- Enhanced clustering algorithms
- More detailed weather impact analysis
- Improved performance for large order volumes
- Additional export formats

## Support

For support, please:
1. Check the documentation
2. Review known issues
3. Contact plugin support with:
   - WordPress version
   - WooCommerce version
   - Error messages (if any)
   - Steps to reproduce issues
