jQuery(document).ready(function($) {

    $(document).on('click', '#add_vegetable', function(e) {
        e.preventDefault();

        // Get form values
        var name = $('#new_vegetable_name').val();
        var icon = $('#new_vegetable_icon').val();
        var mainImage = $('#new_vegetable_main_image').val();
        var duration = $('#new_vegetable_duration').val();

        // Get action images
        var actionImages = {};
        $('.tpgs-action-row input').each(function() {
            var actionKey = $(this).data('action');
            var actionValue = $(this).val();
            actionImages[actionKey] = actionValue;
        });


        if (!name || !icon || !mainImage || !duration) {
            alert('Please fill all required fields');
            return;
        }

        // Get next ID
        var nextId = 1;
        $('#plants-list tr').each(function() {
            var id = parseInt($(this).find('input[name*="[id]"]').val()) || 0;
            if (id >= nextId) nextId = id + 1;
        });

        // Create action images HTML
        var actionImagesHtml = '';
        var actionKeys = ['watered', 'fed-nutrients', 'checked-plant-health', 'took-progress-photo'];
        actionKeys.forEach(function(action) {
            var actionName = action.replace('-', ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
            actionImagesHtml += '<div class="tpgs-action-input">' +
                '<label>' + actionName + '</label>' +
                '<input type="text" name="tpgs_plants[' + nextId + '][action_images][' + action + ']" value="' + (actionImages[action] || '') + '" class="regular-text">' +
                '</div>';
        });

        // Create new row
        var newRow = '<tr>' +
            '<td><input type="hidden" name="tpgs_plants[' + nextId + '][id]" value="' + nextId + '">' +
            '<input type="text" name="tpgs_plants[' + nextId + '][name]" value="' + name + '" class="regular-text"></td>' +
            '<td><input type="text" name="tpgs_plants[' + nextId + '][icon]" value="' + icon + '" class="regular-text"></td>' +
            '<td><input type="text" name="tpgs_plants[' + nextId + '][main_image]" value="' + mainImage + '" class="regular-text"></td>' +
            '<td>' + actionImagesHtml + '</td>' +
            '<td><input type="number" name="tpgs_plants[' + nextId + '][growth_duration]" value="' + duration + '" min="1" class="small-text"></td>' +
            '<td><button type="button" class="button button-secondary remove-vegetable">Remove</button></td>' +
            '</tr>';

        $('#plants-list').append(newRow);

        // Clear form
        $('#new_vegetable_name, #new_vegetable_icon, #new_vegetable_main_image, #new_vegetable_duration, .tpgs-action-row input').val('');

    });

    $(document).on('click', '.remove-vegetable', function() {
        if (confirm('Remove this vegetable?')) {
            $(this).closest('tr').remove();
        }
    });
});