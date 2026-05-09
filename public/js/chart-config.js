/**
 * Industrias Salcom — Chart.js Global Configuration
 * Estilo profesional unificado para todas las gráficas
 */

// Paleta de colores corporativa
window.SALCOM_COLORS = {
    purple:     '#6B3FA0',
    purpleMid:  '#9C6DD0',
    purpleLight:'rgba(107,63,160,.12)',
    green:      '#059669',
    greenLight: 'rgba(5,150,105,.12)',
    blue:       '#2563eb',
    blueLight:  'rgba(37,99,235,.12)',
    amber:      '#d97706',
    amberLight: 'rgba(217,119,6,.12)',
    red:        '#dc2626',
    redLight:   'rgba(220,38,38,.12)',
    gray:       '#6b7280',
    grayLight:  '#f3f4f6',
};

// Configuración global de Chart.js
Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.color = '#6b7280';
Chart.defaults.responsive = true;
Chart.defaults.maintainAspectRatio = false;

// Animaciones suaves
Chart.defaults.animation = {
    duration: 800,
    easing: 'easeOutQuart',
};

// Tooltips elegantes
Chart.defaults.plugins.tooltip = {
    backgroundColor: 'rgba(26,26,46,.92)',
    titleFont: { family: "'Inter'", size: 12, weight: '600' },
    bodyFont: { family: "'Inter'", size: 11, weight: '400' },
    padding: { top: 10, bottom: 10, left: 14, right: 14 },
    cornerRadius: 10,
    displayColors: true,
    boxWidth: 8,
    boxHeight: 8,
    boxPadding: 4,
    usePointStyle: true,
    borderColor: 'rgba(255,255,255,.1)',
    borderWidth: 1,
};

// Leyenda
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
Chart.defaults.plugins.legend.labels.padding = 16;
Chart.defaults.plugins.legend.labels.font = { family: "'Inter'", size: 11, weight: '500' };

// Escalas
Chart.defaults.scales.linear = {
    ...Chart.defaults.scales.linear,
    grid: { color: 'rgba(0,0,0,.04)', drawBorder: false },
    ticks: { font: { size: 10, weight: '500' }, padding: 8 },
    border: { display: false },
};
Chart.defaults.scales.category = {
    ...Chart.defaults.scales.category,
    grid: { display: false },
    ticks: { font: { size: 10, weight: '500' }, padding: 6 },
    border: { display: false },
};

// Plugin para texto central en donas
window.centerTextPlugin = {
    id: 'centerText',
    afterDraw(chart) {
        if (!chart.config.options.plugins?.centerText) return;
        const { text, subtext, color } = chart.config.options.plugins.centerText;
        const { ctx, chartArea: { left, right, top, bottom } } = chart;
        const cx = (left + right) / 2, cy = (top + bottom) / 2;
        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        if (text) {
            ctx.font = "bold 32px 'Inter'";
            ctx.fillStyle = color || '#1d1d1f';
            ctx.fillText(text, cx, subtext ? cy - 8 : cy);
        }
        if (subtext) {
            ctx.font = "500 11px 'Inter'";
            ctx.fillStyle = '#86868b';
            ctx.fillText(subtext, cx, cy + 16);
        }
        ctx.restore();
    }
};
Chart.register(window.centerTextPlugin);

// Helpers para crear gráficas rápido
window.salcomChart = {
    line(canvas, labels, data, opts = {}) {
        const color = opts.color || SALCOM_COLORS.purple;
        return new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    data,
                    borderColor: color,
                    backgroundColor: color.replace(')', ',.06)').replace('rgb', 'rgba'),
                    tension: .4,
                    fill: true,
                    pointRadius: 5,
                    pointBackgroundColor: color,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2.5,
                    pointHoverRadius: 7,
                    borderWidth: 3,
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: opts.yFormat || (v => v) } },
                },
                ...(opts.extra || {}),
            }
        });
    },

    bar(canvas, labels, data, opts = {}) {
        const color = opts.color || SALCOM_COLORS.purple;
        const bgColor = opts.bgColors || color.replace(')', ',.12)').replace('rgb', 'rgba');
        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: bgColor,
                    borderColor: opts.borderColors || color,
                    borderWidth: 2,
                    borderRadius: 10,
                    barPercentage: opts.barWidth || .55,
                    borderSkipped: false,
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: opts.yFormat || (v => v), stepSize: opts.stepSize } },
                },
                ...(opts.extra || {}),
            }
        });
    },

    doughnut(canvas, labels, data, colors, opts = {}) {
        return new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 8,
                    spacing: 2,
                }]
            },
            options: {
                cutout: opts.cutout || '68%',
                plugins: {
                    legend: opts.legend !== false ? {
                        position: opts.legendPos || 'bottom',
                    } : { display: false },
                    centerText: opts.centerText || undefined,
                },
                ...(opts.extra || {}),
            }
        });
    },
};
