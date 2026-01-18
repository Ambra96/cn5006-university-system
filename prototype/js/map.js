function initCampusMap() {
    const mapEl = document.getElementById('map');
    if (!mapEl || typeof L === 'undefined') return;

    const uniLatLng = [37.98011553498004, 23.735679190495745]; // HellenicTech University coordinates

    const map = L.map(mapEl, {
        scrollWheelZoom: false
    }).setView(uniLatLng, 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    L.marker(uniLatLng)
        .addTo(map)
        .bindPopup(`
            <strong>HellenicTech University</strong><br>
            Athens, Greece
        `);
}