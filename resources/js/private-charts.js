// Importe uniquement les éléments Chart.js nécessaires aux graphiques ABIWEIN.
import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    ArcElement,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

// Enregistre les composants une seule fois avant de créer les graphiques.
Chart.register(BarController, BarElement, CategoryScale, DoughnutController, ArcElement, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip);

// Les couleurs reprennent strictement la palette officielle ABIWEIN.
const chartColors = {
    primary: '#234B35',
    secondary: '#6F835D',
    accent: '#C99A2E',
    surface: '#FFFFFF',
    soft: '#E5E7E6',
    ink: '#161A17',
    muted: '#66706A',
};

/** Retourne les réglages visuels communs afin de garder tous les graphiques cohérents. */
const commonOptions = (currency = false) => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: { intersect: false, mode: 'index' },
    plugins: {
        legend: {
            position: 'top',
            align: 'end',
            labels: { color: chartColors.muted, usePointStyle: true, boxWidth: 7, boxHeight: 7, padding: 16 },
        },
        tooltip: {
            callbacks: currency ? { label: (context) => `${context.dataset.label}: ${Number(context.raw).toLocaleString('fr-FR')} FCFA` } : {},
        },
    },
    scales: {
        x: {
            border: { display: false },
            grid: { display: false },
            ticks: { color: chartColors.muted, font: { size: 11 } },
        },
        y: {
            beginAtZero: true,
            border: { display: false },
            grid: { color: 'rgba(22, 26, 23, 0.08)' },
            ticks: { color: chartColors.muted, font: { size: 11 } },
        },
    },
});

/** Lit les configurations JSON produites par Blade et crée chaque graphique visible. */
const initializePrivateCharts = () => {
    document.querySelectorAll('script[data-chart-config]').forEach((configurationNode) => {
        // L’attribut data-target désigne le canvas associé à ce bloc JSON.
        const canvas = document.getElementById(configurationNode.dataset.target);

        // L’absence de canvas arrête seulement ce graphique, sans perturber la page.
        if (!canvas) {
            return;
        }

        try {
            // Le JSON est généré par Laravel à partir des données déjà autorisées de la page.
            const configuration = JSON.parse(configurationNode.textContent);

            // La vue choisit le type, les séries et l’éventuel format monétaire.
            new Chart(canvas, {
                type: configuration.type,
                data: configuration.data,
                options: configuration.type === 'doughnut'
                    ? { ...commonOptions(configuration.currency), cutout: '68%', scales: {} }
                    : commonOptions(configuration.currency),
            });
        } catch (error) {
            // Une configuration invalide reste visible dans la console sans casser l’interface.
            console.error('Configuration de graphique ABIWEIN invalide.', error);
        }
    });
};

// Le module Vite est différé, mais ce contrôle reste robuste pendant les tests et rechargements.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePrivateCharts, { once: true });
} else {
    initializePrivateCharts();
}
