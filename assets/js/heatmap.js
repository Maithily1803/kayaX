var Heatmap = (function ($) {

    var _days = 30;

    function load(days) {
        _days = days || 30;
        ajax('../api/heatmap.php', { days: _days })
            .done(function (r) {
                if (r.heatmap) {
                    BodyMap.applyHeatData(r.heatmap);
                    renderLegend();
                }
            })
            .fail(function () { toast('Failed to load heatmap.', 'error'); });
    }

    function renderLegend() {
        var html =
            '<div style="display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap;margin-top:0.75rem;font-size:0.78rem;">' +
            '<span style="color:var(--text-muted)">Activity:</span>' +
            '<span><span style="display:inline-block;width:12px;height:12px;background:#1e1e3f;border-radius:2px;margin-right:4px;"></span>None</span>' +
            '<span><span style="display:inline-block;width:12px;height:12px;background:#0d4f8c;border-radius:2px;margin-right:4px;"></span>Low</span>' +
            '<span><span style="display:inline-block;width:12px;height:12px;background:#1d9e75;border-radius:2px;margin-right:4px;"></span>Moderate</span>' +
            '<span><span style="display:inline-block;width:12px;height:12px;background:#ef9f27;border-radius:2px;margin-right:4px;"></span>High</span>' +
            '<span><span style="display:inline-block;width:12px;height:12px;background:#e24b4a;border-radius:2px;margin-right:4px;"></span>Peak</span>' +
            '</div>';
        $('#heatmap-legend').html(html);
    }

    return { load: load };

})(jQuery);

var ProgressChart = (function ($) {

    var _chart = null;

    function init(canvasId) {
        ajax('../api/progress.php', { action: 'chart' })
            .done(function (r) { render(canvasId, r.chart || []); })
            .fail(function () { toast('Could not load chart data.', 'error'); });
    }

    function render(canvasId, data) {
        var labels    = data.map(function (d) { return d.day; });
        var intensity = data.map(function (d) { return parseInt(d.avg_intensity) || 0; });
        var muscles   = data.map(function (d) { return parseInt(d.muscles_worked) || 0; });

        var ctx = document.getElementById(canvasId);
        if (!ctx) return;

        if (_chart) { _chart.destroy(); }

        _chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Avg Intensity',
                        data:  intensity,
                        borderColor:     '#6366f1',
                        backgroundColor: 'rgba(99,102,241,0.12)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointBackgroundColor: '#6366f1',
                    },
                    {
                        label: 'Muscles Worked',
                        data:  muscles,
                        borderColor:     '#22d3ee',
                        backgroundColor: 'rgba(34,211,238,0.08)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointBackgroundColor: '#22d3ee',
                        yAxisID: 'y2',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        labels: { color: '#a0a0c0', font: { size: 12 } },
                    },
                    tooltip: {
                        backgroundColor: '#1a1a35',
                        borderColor: 'rgba(99,102,241,0.3)',
                        borderWidth: 1,
                        titleColor: '#f1f0fb',
                        bodyColor: '#a0a0c0',
                    },
                },
                scales: {
                    x: {
                        ticks: { color: '#6b6b8a', font: { size: 11 } },
                        grid:  { color: 'rgba(99,102,241,0.07)' },
                    },
                    y: {
                        ticks: { color: '#6b6b8a', font: { size: 11 } },
                        grid:  { color: 'rgba(99,102,241,0.07)' },
                        min: 0, max: 100,
                    },
                    y2: {
                        position: 'right',
                        ticks: { color: '#22d3ee', font: { size: 11 } },
                        grid:  { display: false },
                        min: 0,
                    },
                },
            },
        });
    }

    return { init: init };

})(jQuery);