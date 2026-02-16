var my_handlers = {
    // Fill province
    fill_provinces: function() {
        var region_code = $(this).val();
        var region_text = $(this).find("option:selected").text();
        $('#region-text').val(region_text);

        // Reset dependent fields
        $('#province-text, #city-text, #barangay-text').val('');
        $('#province').empty().append('<option selected="true" disabled>Choose Province</option>');
        $('#city').empty().append('<option selected="true" disabled>Choose City</option>');
        $('#barangay').empty().append('<option selected="true" disabled>Choose Barangay</option>');

        // Fetch provinces if a valid region is selected
        if (region_code) {
            $.getJSON('assets/theme/dist/ph-json/province.json', function(data) {
                var filtered = data.filter(value => value.region_code == region_code);
                filtered.sort((a, b) => a.province_name.localeCompare(b.province_name));

                filtered.forEach(entry => {
                    $('#province').append(
                        $('<option></option>').attr('value', entry.province_code).text(entry.province_name)
                    );
                });

                update_full_address();
            });
        }
    },

    // Fill city
    fill_cities: function() {
        var province_code = $(this).val();
        var province_text = $(this).find("option:selected").text();
        $('#province-text').val(province_text);

        // Reset dependent fields
        $('#city-text, #barangay-text').val('');
        $('#city').empty().append('<option selected="true" disabled>Choose City</option>');
        $('#barangay').empty().append('<option selected="true" disabled>Choose Barangay</option>');

        // Fetch cities if a valid province is selected
        if (province_code) {
            $.getJSON('assets/theme/dist/ph-json/city.json', function(data) {
                var filtered = data.filter(value => value.province_code == province_code);
                filtered.sort((a, b) => a.city_name.localeCompare(b.city_name));

                filtered.forEach(entry => {
                    $('#city').append(
                        $('<option></option>').attr('value', entry.city_code).text(entry.city_name)
                    );
                });

                update_full_address();
            });
        }
    },

    // Fill barangay
    fill_barangays: function() {
        var city_code = $(this).val();
        var city_text = $(this).find("option:selected").text();
        $('#city-text').val(city_text);

        // Reset dependent fields
        $('#barangay-text').val('');
        $('#barangay').empty().append('<option selected="true" disabled>Choose Barangay</option>');

        // Fetch barangays if a valid city is selected
        if (city_code) {
            $.getJSON('assets/theme/dist/ph-json/barangay.json', function(data) {
                var filtered = data.filter(value => value.city_code == city_code);
                filtered.sort((a, b) => a.brgy_name.localeCompare(b.brgy_name));

                filtered.forEach(entry => {
                    $('#barangay').append(
                        $('<option></option>').attr('value', entry.brgy_code).text(entry.brgy_name)
                    );
                });

                update_full_address();
            });
        }
    },

    // On change barangay
    onchange_barangay: function() {
        var barangay_text = $(this).find("option:selected").text();
        $('#barangay-text').val(barangay_text);
        update_full_address();
    },
};

// Function to enable/disable fields based on conditions
function toggle_fields() {
    const region = $('#region').val();
    const province = $('#province').val();
    const city = $('#city').val();
    const barangay = $('#barangay').val();

    // Enable/Disable province, city, barangay dropdowns
    $('#province').prop('disabled', !region);
    $('#city').prop('disabled', !province);
    $('#barangay').prop('disabled', !city);

    // Enable/Disable Street input and make it required based on barangay selection
    if (barangay) {
        $('#Street').prop('readonly', false).attr('required', true);
    } else {
        $('#Street').prop('readonly', true).removeAttr('required');
    }
}

// Function to update the full address
function update_full_address() {
    const region = $('#region option:selected').text();
    const province = $('#province option:selected').text();
    const city = $('#city option:selected').text();
    const barangay = $('#barangay option:selected').text();
    const street = $('#Street').val() || '';

    // Exclude "Choose" values from the full address
    const address_parts = [region, province, city, barangay, street].filter(
        part => part && !part.startsWith("Choose")
    );

    // Join valid parts to form the full address
    const full_address = address_parts.join(', ');
    $('#full-address').val(full_address);
}

// Ensure the function is called in all relevant handlers
$(function() {
    $('#region').on('change', function() {
        my_handlers.fill_provinces.call(this);
        toggle_fields();
    });

    $('#province').on('change', function() {
        my_handlers.fill_cities.call(this);
        toggle_fields();
    });

    $('#city').on('change', function() {
        my_handlers.fill_barangays.call(this);
        toggle_fields();
    });

    $('#barangay').on('change', function() {
        my_handlers.onchange_barangay.call(this);
        toggle_fields();
    });

    $('#Street').on('input', update_full_address);

    // Initialize fields on page load
    $('#province, #city, #barangay').prop('disabled', true);
    $('#Street').prop('readonly', true);

    // Populate regions
    const regionDropdown = $('#region');
    regionDropdown.empty().append('<option selected="true" disabled>Choose Region</option>');

    $.getJSON('assets/theme/dist/ph-json/region.json', function(data) {
        data.forEach(entry => {
            regionDropdown.append(
                $('<option></option>').attr('value', entry.region_code).text(entry.region_name)
            );
        });
    });
});
