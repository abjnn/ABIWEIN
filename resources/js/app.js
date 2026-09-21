import './bootstrap';
import 'bootstrap';
import Chart from 'chart.js/auto';

const initializeBrandChart = () => {
    const canvas = document.querySelector('[data-abiwein-brand-chart]');

    if (!canvas) {
        return;
    }

    Chart.getChart(canvas)?.destroy();

    const styles = getComputedStyle(document.documentElement);
    const palette = [
        styles.getPropertyValue('--abiwein-olive').trim(),
        styles.getPropertyValue('--abiwein-sage').trim(),
        styles.getPropertyValue('--abiwein-beige-light').trim(),
        styles.getPropertyValue('--abiwein-beige-taupe').trim(),
        styles.getPropertyValue('--abiwein-beige-rose').trim(),
    ];

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: [
                'Vert olive foncé',
                'Vert sauge',
                'Beige clair',
                'Beige taupe',
                'Beige rosé',
            ],
            datasets: [
                {
                    data: [1, 1, 1, 1, 1],
                    backgroundColor: palette,
                    borderColor: '#ffffff',
                    borderWidth: 4,
                    hoverOffset: 8,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : undefined,
            cutout: '62%',
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.label} — couleur de la palette ABIWEIN`,
                    },
                },
            },
        },
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeBrandChart, { once: true });
} else {
    initializeBrandChart();
}
