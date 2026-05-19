/**
 * Dona OTIF (misma lógica que portal cliente): pista gris → arco faltante
 * (rojo si ≤95%, naranja si >95%) → arco cumplido (verde). lineCap butt.
 */
(function () {
    function formatOtifPct(p) {
        var n = Math.round(p * 10) / 10;
        return (n % 1 === 0 ? String(Math.round(n)) : n.toFixed(1)) + '%';
    }

    window.salcomDrawOtifDonut = function (canvasId, percent, percentElId, css, colors) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        css = css || 180;
        colors = colors || {};
        var dpr = window.devicePixelRatio || 1;
        canvas.width = css * dpr;
        canvas.height = css * dpr;
        canvas.style.width = css + 'px';
        canvas.style.height = css + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        var scale = css / 180;
        var center = css / 2;
        var radius = 70 * scale;
        var lineWidth = 16 * scale;
        var startAngle = -Math.PI / 2;
        var p = Math.min(100, Math.max(0, Number(percent)));
        var sweep = (2 * Math.PI * p) / 100;
        var endAngle = startAngle + sweep;
        var fullEnd = startAngle + 2 * Math.PI;
        var trackColor = colors.track || '#e8e8ed';
        var fillColor = colors.fill || '#34c759';
        var gapColor = colors.gap != null
            ? (typeof colors.gap === 'function' ? colors.gap(p) : colors.gap)
            : (p > 95 ? '#ff9500' : '#ff3b30');

        ctx.clearRect(0, 0, css, css);
        ctx.lineWidth = lineWidth;
        ctx.lineCap = 'butt';

        ctx.beginPath();
        ctx.arc(center, center, radius, 0, 2 * Math.PI);
        ctx.strokeStyle = trackColor;
        ctx.stroke();

        if (p < 100) {
            ctx.beginPath();
            ctx.arc(center, center, radius, endAngle, fullEnd);
            ctx.strokeStyle = gapColor;
            ctx.stroke();
        }

        if (p > 0) {
            ctx.beginPath();
            ctx.arc(center, center, radius, startAngle, endAngle);
            ctx.strokeStyle = fillColor;
            ctx.stroke();
        }

        var el = document.getElementById(percentElId);
        if (el) {
            el.textContent = formatOtifPct(p);
            if (colors.text) {
                el.style.color = colors.text;
            } else if (p <= 95) {
                el.style.color = '#ff3b30';
            } else {
                el.style.color = '#34c759';
            }
        }
    };
})();
