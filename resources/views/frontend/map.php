<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>聯大美食地圖</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
</head>
<body>
    <div id="map" style="height: 600px;"></div>
    <script>
        var map = L.map('map').setView([24.5458273, 120.80979], 13); // 國立聯合大學二坪山校區 ,13為縮放程度
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        fetch('get_places.php') // 從後端 API 獲取地點資料
            .then(response => response.json())
            .then(data => {
                data.forEach(place => {
                    L.marker([place.lat, place.lng]).addTo(map)
                        .bindPopup(`<b>${place.name}</b><br>${place.description}`);
                });
            });
    </script>
</body>
</html>
