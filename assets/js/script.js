document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('.site-nav');

    if (!toggle || !navigation) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!isExpanded));
        navigation.classList.toggle('is-open', !isExpanded);
    });

    navigation.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.closest('a')) {
            toggle.setAttribute('aria-expanded', 'false');
            navigation.classList.remove('is-open');
        }
    });
});