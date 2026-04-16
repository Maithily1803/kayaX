var BodyMap = (function ($) {
    var state = {
        side:          'front',
        gender:        'male',
        selectedId:    null,
        selectedName:  null,
        muscles:       [],
        heatData:      {},
        mode:          'select',
    };

    function init(opts) {
        state.gender = opts.gender || 'male';
        state.mode   = opts.mode   || 'select';
        bindEvents();
        loadMuscles();
        if (state.mode === 'heatmap') {
            Heatmap.load(30);
        }
    }

    function bindEvents() {
        $('#btn-front').on('click', function () { switchSide('front'); });
        $('#btn-back').on('click',  function () { switchSide('back'); });
        $('#btn-male').on('click',   function () { switchGender('male'); });
        $('#btn-female').on('click', function () { switchGender('female'); });

        $(document).on('click', '.muscle-group', function () {
            var id   = $(this).data('muscle-id');
            var name = $(this).data('muscle-name');
            selectMuscle(id, name, $(this));
        });
    }

    function switchSide(side) {
        state.side = side;
        $('#btn-front, #btn-back').removeClass('active');
        $('#btn-' + side).addClass('active');
        loadMuscles();
    }

    function switchGender(gender) {
        state.gender = gender;
        $('#btn-male, #btn-female').removeClass('active');
        $('#btn-' + gender).addClass('active');
        loadMuscles();
    }

    function loadMuscles() {
        ajax('../api/muscles.php', { action: 'list', side: state.side, gender: state.gender })
            .done(function (r) {
                state.muscles = r.muscles || [];
                renderMuscleOverlays();
            })
            .fail(function () { toast('Failed to load muscle data.', 'error'); });
    }

    function renderMuscleOverlays() {
        $('.muscle-group').removeClass('selected heat-cold heat-low heat-medium heat-high');

        $.each(state.muscles, function (i, m) {
            var $el = $('#' + m.svg_id);
            if (!$el.length) return;
            $el.addClass('muscle-group')
               .attr('data-muscle-id', m.id)
               .attr('data-muscle-name', m.name);

            if (state.mode === 'heatmap' && state.heatData[m.svg_id] !== undefined) {
                var heat = state.heatData[m.svg_id];
                $el.removeClass('heat-none heat-cold heat-low heat-medium heat-high');
                if (heat === 0)       $el.addClass('heat-none');
                else if (heat < 30)   $el.addClass('heat-cold');
                else if (heat < 55)   $el.addClass('heat-low');
                else if (heat < 75)   $el.addClass('heat-medium');
                else                  $el.addClass('heat-high');
            }
        });

        if (state.selectedId) {
            var $sel = $('[data-muscle-id="' + state.selectedId + '"]');
            if ($sel.length) $sel.addClass('selected');
        }
    }

    function selectMuscle(id, name, $el) {
        if (state.mode === 'heatmap') return;
        state.selectedId   = id;
        state.selectedName = name;
        $('.muscle-group').removeClass('selected');
        $el.addClass('selected');
        $(document).trigger('muscle:selected', [id, name]);
    }

    function applyHeatData(data) {
        state.heatData = {};
        $.each(data, function (i, d) {
            state.heatData[d.svg_id] = d.heat;
        });
        renderMuscleOverlays();
    }

    function getSelected() {
        return { id: state.selectedId, name: state.selectedName };
    }

    return { init: init, applyHeatData: applyHeatData, getSelected: getSelected, switchSide: switchSide };

})(jQuery);