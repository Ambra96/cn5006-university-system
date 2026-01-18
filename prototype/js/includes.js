//load parrtials in index.html
async function loadPartial(targetId, file) {
    try {
        const res = await fetch(file);
        if (!res.ok) throw new Error(file);
        document.getElementById(targetId).innerHTML = await res.text();
        return true;
    } catch (e) {
        console.error('Failed to load:', file);
        return false;
    }
}
//load all partials on DOMContentLoaded from partials folder
document.addEventListener('DOMContentLoaded', async () => {
    await loadPartial('header', 'partials/header.html');
    await loadPartial('auth', 'partials/auth_portal.html');
    await loadPartial('programs', 'partials/programs.html');

    const campusLoaded = await loadPartial('campus', 'partials/campus.html');
    if (campusLoaded) initCampusMap();

    await loadPartial('footer', 'partials/footer.html');
    await loadPartial('about', 'partials/about.html');
});



