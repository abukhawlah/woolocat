jQuery(document).ready(function($) {
    console.log('Woolocat plugin initialized');

    let map, markers = [], heatmap, markerCluster;
    let locations = [];

    function initializeMap() {
        console.log('Initializing map...');

        // Check if Google Maps API is loaded correctly
        if (typeof google === 'undefined' || !google.maps) {
            console.error('Google Maps failed to load');
            $('.distance-cell, .delivery-time-cell').text('API Error').addClass('error');
            return;
        }

        const mapOptions = {
            center: { lat: -29.8587, lng: 31.0218 },
            zoom: 12,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };

        map = new google.maps.Map(document.getElementById('map'), mapOptions);
        const bounds = new google.maps.LatLngBounds();
        const geocoder = new google.maps.Geocoder();

        // Get store address
        const storeAddress = $('#store-address').val();
        console.log('Store address:', storeAddress);

        if (!storeAddress) {
            console.error('Store address not found');
            $('.distance-cell, .delivery-time-cell').text('No store address').addClass('error');
            return;
        }

        // First geocode the store location
        geocoder.geocode({ 
            address: storeAddress
        }, function(storeResults, storeStatus) {
            console.log('Store geocoding status:', storeStatus);

            if (storeStatus !== 'OK') {
                console.error('Store geocoding failed:', storeStatus);
                $('.distance-cell, .delivery-time-cell').text('Location error').addClass('error');
                return;
            }

            const storeLocation = storeResults[0].geometry.location;
            console.log('Store location:', storeLocation.toString());
            
            // Add store marker
            new google.maps.Marker({
                map: map,
                position: storeLocation,
                title: 'Store Location',
                icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
            });
            
            bounds.extend(storeLocation);

            // Process each delivery address
            $('.orders-table-wrap tbody tr').each(function() {
                const $row = $(this);
                const $distanceCell = $row.find('.distance-cell');
                const $deliveryTimeCell = $row.find('.delivery-time-cell');
                const address = $row.find('td:first').text().trim();

                if (!address || address === 'Location') {
                    return; // Skip header row and invalid addresses
                }

                console.log('Processing address:', address);

                geocoder.geocode({
                    address: address
                }, function(results, status) {
                    console.log('Delivery location geocoding status:', status, 'for address:', address);

                    if (status === 'OK') {
                        const location = results[0].geometry.location;
                        console.log('Delivery location:', location.toString());
                        
                        // Store location for heatmap
                        locations.push(location);
                        
                        // Add delivery location marker
                        const marker = new google.maps.Marker({
                            map: map,
                            position: location,
                            title: address
                        });
                        
                        markers.push(marker);
                        bounds.extend(location);

                        // Calculate distance and time
                        const service = new google.maps.DistanceMatrixService();
                        
                        service.getDistanceMatrix({
                            origins: [storeLocation],
                            destinations: [location],
                            travelMode: google.maps.TravelMode.DRIVING,
                            unitSystem: google.maps.UnitSystem.METRIC
                        }, function(response, status) {
                            console.log('Distance Matrix status:', status);
                            console.log('Distance Matrix response:', response);

                            if (status === 'OK' && response.rows[0].elements[0].status === 'OK') {
                                const element = response.rows[0].elements[0];
                                $distanceCell
                                    .text(element.distance.text)
                                    .removeClass('loading')
                                    .attr('title', 'Actual driving distance');

                                $deliveryTimeCell
                                    .text(element.duration.text)
                                    .removeClass('loading')
                                    .attr('title', 'Estimated driving time');
                            } else {
                                console.error('Distance Matrix failed:', status);
                                // Fallback to straight-line calculation
                                const distance = google.maps.geometry.spherical.computeDistanceBetween(location, storeLocation);
                                const kmDistance = (distance / 1000).toFixed(2);
                                const estimatedMinutes = Math.round(kmDistance * 2); // Rough estimate: 2 min per km
                                const hours = Math.floor(estimatedMinutes / 60);
                                const minutes = estimatedMinutes % 60;
                                const timeText = hours > 0 ? 
                                    `${hours} hr ${minutes} min` : 
                                    `${minutes} min`;

                                $distanceCell
                                    .text(kmDistance + ' km')
                                    .removeClass('loading')
                                    .attr('title', 'Straight-line distance');

                                $deliveryTimeCell
                                    .text(timeText + ' (est.)')
                                    .removeClass('loading')
                                    .attr('title', 'Estimated based on straight-line distance');
                            }
                            
                            // Fit map to show all markers
                            if (markers.length > 0) {
                                map.fitBounds(bounds);
                            }
                        });
                    } else {
                        console.error('Geocoding failed:', status, address);
                        $distanceCell.text('Geocoding failed').addClass('error').removeClass('loading');
                        $deliveryTimeCell.text('Geocoding failed').addClass('error').removeClass('loading');
                    }
                });
            });
        });
    }

    // Initialize map when document is ready
    if (typeof google !== 'undefined' && google.maps) {
        initializeMap();
    } else {
        console.error('Google Maps not loaded');
        $('.distance-cell, .delivery-time-cell').text('API Error').addClass('error');
    }

    // Add error handling for map initialization
    if (typeof google !== 'undefined' && google.maps) {
        window.gm_authFailure = function() {
            console.error('Google Maps authentication failed');
            $('.distance-cell, .delivery-time-cell').text('Maps API Error').addClass('error');
        };
    }

    // Handle weather data
    $('.weather-impact').each(function() {
        const $cell = $(this);
        const location = $cell.data('location');
        
        if (!location) {
            $cell.find('span').text('No location data').removeClass('loading').addClass('error');
            return;
        }
        
        $.ajax({
            url: woolocat_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_weather_data',
                nonce: woolocat_ajax.nonce,
                location: location
            },
            success: function(response) {
                if (response.success && response.data.weather) {
                    $cell.find('span').text(response.data.weather).removeClass('loading');
                } else {
                    $cell.find('span').text('Weather data unavailable').removeClass('loading');
                }
            },
            error: function(xhr, status, error) {
                console.error('Weather API error:', error);
                $cell.find('span').text('Error fetching weather').removeClass('loading').addClass('error');
            }
        });
    });

    // Handle view toggle buttons
    $('.view-toggles button').click(function() {
        const $btn = $(this);
        const view = $btn.data('view');
        
        $('.view-toggles button').removeClass('active');
        $btn.addClass('active');
        
        // Hide all markers first
        markers.forEach(marker => marker.setMap(null));
        if (heatmap) heatmap.setMap(null);
        if (markerCluster) markerCluster.clearMarkers();
        
        switch(view) {
            case 'map':
                // Show regular markers
                markers.forEach(marker => marker.setMap(map));
                break;
                
            case 'heatmap':
                // Create heatmap layer
                heatmap = new google.maps.visualization.HeatmapLayer({
                    data: locations.map(location => ({
                        location: location,
                        weight: 1
                    })),
                    map: map,
                    radius: 20,
                    opacity: 0.8
                });
                break;
                
            case 'clusters':
                // Create marker clusters
                markerCluster = new markerClusterer.MarkerClusterer({
                    map,
                    markers,
                    algorithm: new markerClusterer.SuperClusterAlgorithm({
                        radius: 100,
                        maxZoom: 16
                    })
                });
                break;
        }
    });

    // Handle view orders button
    $(document).on('click', '.view-orders', function(e) {
        e.preventDefault();
        console.log('View orders clicked');
        
        try {
            const ordersData = $(this).attr('data-orders');
            console.log('Raw orders data:', ordersData);
            
            if (!ordersData) {
                throw new Error('No orders data found');
            }
            
            const orders = JSON.parse(ordersData);
            console.log('Parsed orders:', orders);
            
            if (!Array.isArray(orders)) {
                throw new Error('Orders data is not an array');
            }
            
            const $modal = $('#orders-modal');
            const $modalBody = $('#modal-orders-list');
            
            // Clear previous orders
            $modalBody.empty();
            
            // Add each order to the modal
            orders.forEach(order => {
                const status = order.status.replace('wc-', '');
                const row = `
                    <tr>
                        <td>#${order.id}</td>
                        <td>${order.date}</td>
                        <td>${order.customer}</td>
                        <td><span class="badge">${status}</span></td>
                        <td>${order.total}</td>
                        <td>
                            <a href="/wp-admin/post.php?post=${order.id}&action=edit" class="button button-small" target="_blank">
                                <span class="dashicons dashicons-edit"></span>
                                Edit
                            </a>
                        </td>
                    </tr>
                `;
                $modalBody.append(row);
            });
            
            // Show modal
            $modal.css('display', 'block');
            
            // Handle close button
            $('.close').off('click').on('click', function() {
                $modal.css('display', 'none');
            });
            
            // Close modal when clicking outside
            $(window).off('click.modal').on('click.modal', function(event) {
                if (event.target === $modal[0]) {
                    $modal.css('display', 'none');
                }
            });

            // Close on ESC key
            $(document).off('keyup.modal').on('keyup.modal', function(e) {
                if (e.key === "Escape") {
                    $modal.css('display', 'none');
                }
            });
        } catch (error) {
            console.error('Error processing orders:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack
            });
        }
    });
});
