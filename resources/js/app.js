// Charge les réglages Axios/Laravel définis par le projet.
import './bootstrap';

// Charge les composants Bootstrap, notamment le menu responsive.
import * as bootstrap from 'bootstrap';

// Charge les graphiques uniquement lorsqu’une vue privée fournit une configuration.
import './private-charts';

// Charge le comportement du formulaire de vente sans script intégré dans la page HTML.
import './sale-form';

/** Relie les boutons d’impression sans autoriser de JavaScript intégré dans le HTML. */
const initializePrintControls = () => {
    document.querySelectorAll('[data-print-page]').forEach((button) => {
        button.addEventListener('click', () => window.print());
    });
};

/**
 * Referme le menu mobile après la sélection d'un lien.
 * MODIFICATION : changez le sélecteur uniquement si l'identifiant du menu
 * change aussi dans resources/views/partials/public/header.blade.php.
 */
const closeMobileNavigation = () => {
    const navigation = document.querySelector('#navigationPublique');

    // Les futurs écrans privés pourront ne pas utiliser cette navigation.
    if (!navigation) {
        return;
    }

    navigation.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            const collapse = bootstrap.Collapse.getInstance(navigation);

            if (collapse && navigation.classList.contains('show')) {
                collapse.hide();
            }
        });
    });
};

// Attend que le HTML existe avant de rechercher le menu.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', closeMobileNavigation, { once: true });
    document.addEventListener('DOMContentLoaded', initializePrintControls, { once: true });
} else {
    closeMobileNavigation();
    initializePrintControls();
}
