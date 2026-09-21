// Charge les réglages Axios/Laravel définis par le projet.
import './bootstrap';

// Charge les composants Bootstrap, notamment le menu responsive.
import * as bootstrap from 'bootstrap';

// Charge les graphiques uniquement lorsqu’une vue privée fournit une configuration.
import './private-charts';

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
} else {
    closeMobileNavigation();
}
